<?php

namespace Gems\Agenda\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;

class AgendaStaffRepository
{
    public array $staffCacheTags = ['staff'];

    protected int $currentUserId;
    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        readonly CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUserId = $this->currentUserRepository->getCurrentUserId();
    }

    /**
     * Create a new activity
     *
     * @param string $name Activity name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createStaff(string $name, int $organizationId): int|null
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $table = new TableGateway('gems__agenda_staff', $this->cachedResultFetcher->getAdapter());
        $result = $table->insert([
            'gas_name' => $name,
            'gas_id_organization' => $organizationId,
            'gas_match_to' => $name,
            'gas_changed' => new Expression('NOW()'),
            'gas_changed_by' => $this->currentUserId,
            'gas_created' => new Expression('NOW()'),
            'gas_created_by' => $this->currentUserId,
        ]);

        $this->cachedResultFetcher->getCache()->invalidateTags($this->staffCacheTags);

        if ($result) {
            return $table->getLastInsertValue();
        }
        return null;
    }

    public function getActiveStaffData(int|null $organizationId = null): array
    {
        $allStaff = $this->getAllStaffData();

        return array_filter($allStaff, function($staff) use ($organizationId) {
            if ($organizationId) {
                return ($staff['gas_active']) && $staff['gas_id_organization'] == $organizationId;
            }
            return (bool)$staff['gas_active'];
        });
    }

    public function getAllStaffData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__agenda_staff');
        $select->columns([
            'gas_id_staff',
            'gas_name',
            'gas_function',
            'gas_id_organization',
            'gas_id_user',
            'gas_match_to',
            'gas_source',
            'gas_id_in_source',
            'gas_active',
            'gas_filter',
        ]);
        return $this->cachedResultFetcher->fetchAll('agendaStaff', $select, null, $this->staffCacheTags);
    }

    public function getAllStaffOptions(int|null $organizationId = null): array
    {
        $staff = $this->getActiveStaffData($organizationId);

        return array_column($staff, 'gas_name', 'gas_id_staff');
    }

    protected function getMatchList(): array
    {
        $activeStaff = $this->getActiveStaffData();

        $sortedStaff = [];
        foreach ($activeStaff as $staff) {
            if ($staff['gas_match_to'] !== null) {
                foreach (explode('|', $staff['gas_match_to']) as $match) {
                    $sortedStaff[$match][$staff['gas_id_organization']] = $staff['gas_id_staff'];
                }
            }
        }

        return $sortedStaff;
    }

    /**
     * Match an activity to one in the database
     *
     * @param $name string Activity name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null activity ID that was matched or null
     */
    public function matchStaff(string $name, int $organizationId, bool $create = true): int|null
    {
        $staffMembers = $this->getMatchList();

        if (isset($staffMembers[$name], $staffMembers[$name][$organizationId])) {
            return (int)$staffMembers[$name][$organizationId];
        }

        if ($create) {
            return $this->createStaff($name, $organizationId);
        }

        return null;
    }

    /**
     * @param $name string Staff member name
     * @param $sourceId string Source ID
     * @param $source string Source name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null Staff member ID that was matched or null
     */
    public function matchStaffByNameOrSourceId(string $name, string $sourceId, string $source, int $organizationId, bool $create = true): int|null
    {
        $activeStaffData = $this->getActiveStaffData($organizationId);

        foreach($activeStaffData as $staff) {
            if ($staff['gas_source'] === $source && $staff['gas_id_in_source'] === $sourceId) {
                return $staff['gas_id_staff'];
            }
        }

        return $this->matchStaff($name, $organizationId, $create);
    }
}
