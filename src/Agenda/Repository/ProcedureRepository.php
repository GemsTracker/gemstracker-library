<?php

namespace Gems\Agenda\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;

class ProcedureRepository
{
    public array $proceduresCacheTags = ['procedure', 'procedures'];

    protected int $currentUserId;

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        readonly CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUserId = $this->currentUserRepository->getCurrentUserId();
    }

    public function changeProcedureOrganization(int $oldProcedureId, int $newOrganizationId): int|null
    {
        $procedureName = $this->getProcedureName($oldProcedureId);
        return $this->matchProcedure($procedureName, $newOrganizationId, true);
    }

    /**
     * Create a new procedure
     *
     * @param string $name procedure name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createProcedure(string $name, int $organizationId): int|null
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $table = new TableGateway('gems__agenda_procedures', $this->cachedResultFetcher->getAdapter());
        $result = $table->insert([
            'gapr_name' => $name,
            'gapr_id_organization' => $organizationId,
            'gapr_match_to' => $name,
            'gapr_changed' => new Expression('NOW()'),
            'gapr_changed_by' => $this->currentUserId,
            'gapr_created' => new Expression('NOW()'),
            'gapr_created_by' => $this->currentUserId,
        ]);

        $this->cachedResultFetcher->getCache()->invalidateTags($this->proceduresCacheTags);

        if ($result) {
            return $table->getLastInsertValue();
        }
        return null;
    }

    public function getProcedureData(int $procedureId): array|null
    {
        $allProcedures = $this->getAllProceduresData();
        foreach($allProcedures as $procedure) {
            if ($procedure['gapr_id_procedure'] === $procedureId) {
                return $procedure;
            }
        }

        return null;
    }

    public function getProcedureName(int $procedureId): string|null
    {
        $procedure = $this->getProcedureData($procedureId);
        if ($procedure) {
            return $procedure['gapr_name'];
        }
        return null;
    }

    public function getActiveProceduresData(int|null $organizationId = null): array
    {
        $allProcedures = $this->getAllProceduresData();

        return array_filter($allProcedures, function($procedure) use ($organizationId) {
            if ($organizationId) {
                return ($procedure['gapr_active']) && $procedure['gapr_id_organization'] == $organizationId;
            }
            return (bool)$procedure['gapr_active'];
        });
    }

    public function getAllProceduresData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__agenda_procedures');
        $select->columns([
            'gapr_id_procedure',
            'gapr_name',
            'gapr_id_organization',
            'gapr_name_for_resp',
            'gapr_match_to',
            'gapr_code',
            'gapr_active',
            'gapr_filter',
        ]);
        return $this->cachedResultFetcher->fetchAll('agendaProcedures', $select, null, $this->proceduresCacheTags) ?? [];
    }

    public function getProcedureOptions(int|null $organizationId = null): array
    {
        $activeProcedures = $this->getActiveProceduresData($organizationId);

        $options = array_column($activeProcedures, 'gapr_name', 'gapr_id_procedure');
        asort($options);
        return $options;
    }

    protected function getMatchList(): array
    {
        $activeProcedures = $this->getActiveProceduresData();

        $sortedProcedures = [];
        foreach ($activeProcedures as $procedure) {
            if ($procedure['gapr_match_to'] !== null) {
                foreach (explode('|', $procedure['gapr_match_to']) as $match) {
                    $sortedProcedures[$match][$procedure['gapr_id_organization']] = $procedure['gapr_id_procedure'];
                }
            }
        }

        return $sortedProcedures;
    }

    /**
     * Match a procedure to one in the database
     *
     * @param $name string Procedure name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null procedure ID that was matched or null
     */
    public function matchProcedure(string $name, int $organizationId, bool $create = true): int|null
    {
        $procedures = $this->getMatchList();

        if (isset($procedures[$name], $procedures[$name][$organizationId])) {
            return (int)$procedures[$name][$organizationId];
        }

        if ($create) {
            return $this->createProcedure($name, $organizationId);
        }

        return null;
    }
}