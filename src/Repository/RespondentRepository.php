<?php


namespace Gems\Repository;


use Gems\Db\ResultFetcher;
use Gems\Model\MetaModelLoader;
use Gems\Model\Respondent\RespondentModel;
use Gems\Tracker\Respondent;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\TableGateway\TableGateway;
use Zalt\Loader\ProjectOverloader;

class RespondentRepository
{
    public const ORGANIZATION_SEPARATOR = '@';

    protected array $respondents = [];

    public function __construct(
        protected readonly MetaModelLoader $modelLoader,
        protected readonly ProjectOverloader $overLoader,
        protected readonly ResultFetcher $resultFetcher,
    )
    { }

    public function getOtherPatientNumbers(
        string $patientNr,
        int $organizationId,
        bool $pairs = true,
        bool $combined = false,
        bool $withOrganizationName = false,
        int|null $userOrganizationId = null,
    ): array
    {
        $subSelect = $this->resultFetcher->getSelect('gems__respondent2org')
            ->columns(['gr2o_id_user'])
            ->where([
                'gr2o_patient_nr' => $patientNr,
                'gr2o_id_organization' => $organizationId,
            ]);

        $currentOrganizationPredicate = new Predicate();
        $currentOrganizationPredicate->equalTo('gr2o_id_organization', $organizationId);

        $columns = [
            'organizationId' => 'gr2o_id_organization',
            'patientNr' => 'gr2o_patient_nr',
        ];

        if ($combined) {
            $columns['patientNr'] = new Expression('CONCAT(gr2o_patient_nr, "'.static::ORGANIZATION_SEPARATOR.'", gr2o_id_organization)');
        }

        $organizationColumns = [];
        if ($withOrganizationName) {
            $organizationColumns['organizationName'] = 'gor_name';
        }

        $accessibleById = $userOrganizationId ?? $organizationId;

        $select = $this->resultFetcher->getSelect('gems__respondent2org')
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', $organizationColumns)
            ->join('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', [])
            ->columns($columns)
            ->where([
                'grc_success' => 1,
                'gr2o_id_user' => $subSelect,
                new PredicateSet([
                    $currentOrganizationPredicate,
                    new Like('gor_accessible_by', '%'.$accessibleById.'%'),
                ], PredicateSet::COMBINED_BY_OR),
            ]);

        $patients = $this->resultFetcher->fetchAll($select);

        if ($pairs) {
            return array_column($patients, 'patientNr', 'organizationId');
        }

        return $patients;
    }

    public function getPatient(string $patientNr, ?int $organizationId=null): ?array
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2org');
        $select->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['gr2o_patient_nr' => $patientNr]);
        if ($organizationId !== null) {
            $select->where(['gr2o_id_organization' => $organizationId]);
        }
        return $this->resultFetcher->fetchRow($select);
    }

    public function getPatientByRespondentId(int $respondentId, ?int $organizationId): ?array
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2org');
        $select
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['grs_id_user' => $respondentId]);
        if ($organizationId !== null) {
            $select->where(['gr2o_id_organization' => $organizationId]);
        }
        return $this->resultFetcher->fetchRow($select);
    }

    public function getPatientNr(int $respondentId, ?int $organizationId): ?string
    {
        $patient = $this->getPatientByRespondentId($respondentId, $organizationId);

        return $patient['gr2o_patient_nr'] ?? null;
    }

    /**
     * Get all existing patients with a specific ssn
     *
     * @param $ssn
     * @return array
     */
    public function getPatientsBySsn(string $ssn, string $epdId): array
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2org');
        $select
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organi')
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['grs_ssn' => $ssn,]);

        return $this->resultFetcher->fetchAll($select);
    }

    public function getRespondent(?string $patientId, ?int $organizationId, ?int $respondentId = null): Respondent
    {
        if ($patientId) {
            if (isset($this->respondents[$organizationId][$patientId])) {
                return $this->respondents[$organizationId][$patientId];
            }
        }
        $newResp = $this->overLoader->create('Tracker\\Respondent', $patientId, $organizationId, $respondentId);

        if (! isset($this->respondents[$organizationId][$patientId])) {
            $this->respondents[$organizationId][$patientId] = $newResp;
        }

        return $this->respondents[$organizationId][$patientId];
    }
    
    /**
     *
     * @return array
     */
    public function getRespondentConsents(): array
    {
        $select = $this->resultFetcher->getSelect('gems__consents');
        $select->columns(['gco_description'])
            ->order('gco_order');
        $sql = "SELECT gco_description, gco_description FROM gems__consents ORDER BY gco_order";

        return $this->resultFetcher->fetchPairs($select);
    }

    public function getRespondentId(string $patientNr, ?int $organizationId=null): ?int
    {
        if ($patient = $this->getPatient($patientNr, $organizationId)) {
            if (array_key_exists('gr2o_id_user', $patient)) {
                return (int)$patient['gr2o_id_user'];
            }
        }
        return null;
    }

    public function getRespondentModel(bool $detailed = false): RespondentModel
    {
        /**
         * @var RespondentModel $model
         */
        $model = $this->modelLoader->createModel(RespondentModel::class);
        $model->applyStringAction('edit', true);

        return $model;
    }

    /**
     * Get RespondentId from SSN
     *
     * @param $ssn
     * @return ?int
     */
    public function getRespondentIdBySsn(string $ssn): ?int
    {
        $select = $this->resultFetcher->getSelect('gems__respondents');
        $select
            ->columns(['grs_id_user'])
            ->where(['grs_ssn' => $ssn]);


        return $this->resultFetcher->fetchOne($select);
    }

    public function setOpened(string $patientNr, int $organizationId, int $currentUserId): void
    {
        $table = new TableGateway('gems__respondent2org', $this->resultFetcher->getAdapter());
        $table->update([
            'gr2o_opened' => new Expression('NOW()'),
            'gr2o_opened_by' => $currentUserId,
        ],
        [
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
        ]);
    }
}
