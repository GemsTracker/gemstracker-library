<?php


namespace Gems\Repository;


use Gems\Model;
use Gems\Model\RespondentModel;
use Gems\Tracker\Respondent;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Zalt\Loader\ProjectOverloader;

class RespondentRepository
{
    protected array $respondents = [];

    public function __construct(protected Adapter $db, protected Model $modelLoader, protected ProjectOverloader $overLoader)
    {
    }

    public function getRespondent(?string $patientId, int $organizationId, ?int $respondentId = null): Respondent
    {
        if ($patientId) {
            if (isset($this->respondents[$organizationId][$patientId])) {
                return $this->respondents[$organizationId][$patientId];
            }
        }
        $newResp = $this->overLoader->create('Tracker\\Respondent', $patientId, $organizationId, $respondentId);
        $patientId = $newResp->getPatientNumber();

        if (! isset($this->respondents[$organizationId][$patientId])) {
            $this->respondents[$organizationId][$patientId] = $newResp;
        }

        return $this->respondents[$organizationId][$patientId];
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
        return $this->modelLoader->getRespondentModel($detailed);
    }

    public function getPatient(string $patientNr, ?int $organizationId=null): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['gr2o_patient_nr' => $patientNr]);
        if ($organizationId !== null) {
            $select->where(['gr2o_id_organization' => $organizationId]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            return $result->current();
        }
        return null;
    }

    public function getPatientByRespondentId(int $respondentId, int $organizationId): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['grs_id_user' => $respondentId]);
        if ($organizationId !== null) {
            $select->where(['gr2o_id_organization' => $organizationId]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            return $result->current();
        }
        return null;
    }

    /**
     * Get all existing patients with a specific ssn
     *
     * @param $ssn
     * @return array|null
     */
    public function getPatientsBySsn(string $ssn, string $epdId): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organi')
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['grs_ssn' => $ssn,]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $patients = iterator_to_array($result);

        if (count($patients) === 0) {
            return null;
        }

        return $patients;
    }

    /**
     * Get RespondentId from SSN
     *
     * @param $ssn
     * @return ?int
     */
    public function getRespondentIdBySsn(string $ssn): ?int
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondents')
            ->columns(['grs_id_user'])
            ->where(['grs_ssn' => $ssn]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $user = $result->current();
            return (int)$user['grs_id_user'];
        }
        return null;
    }

    public function getOtherPatientNumbers(string $patientNr, int $organizationId, bool $combined=false): array
    {
        $sql = new Sql($this->db);
        $subSelect = $sql->select('gems__respondent2org')
            ->columns(['gr2o_id_user'])
            ->where([
                'gr2o_patient_nr' => $patientNr,
                'gr2o_id_organization' => $organizationId,
            ]);

        $currentOrganizationPredicate = new Predicate();
        $currentOrganizationPredicate->equalTo('gr2o_id_organization', $organizationId);

        $select = $sql->select('gems__respondent2org')
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->join('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', [])
            ->columns(['gr2o_id_organization', 'gr2o_patient_nr'])
            ->where([
                'grc_success' => 1,
                'gr2o_id_user' => $subSelect,
                new PredicateSet([
                    $currentOrganizationPredicate,
                    new Like('gor_accessible_by', '%'.(int)$organizationId.'%'),
                ], PredicateSet::COMBINED_BY_OR),
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $patients = iterator_to_array($result);

        if ($combined) {
            $combinedPatients = [];
            foreach($patients as $patient) {
                $combinedPatients[] = $patient['gr2o_patient_nr'] . '@' . $patient['gr2o_id_organization'];
            }
            return $combinedPatients;
        }

        $pairs = array_column($patients, 'gr2o_patient_nr', 'gr2o_id_organization');
        return $pairs;
    }

    public function getRespondentIdFromRequest(ServerRequestInterface $request)
    {

    }

    public function setOpened(string $patientNr, int $organizationId, int $currentUserId): void
    {
        $table = new TableGateway('gems__respondent2org', $this->db);
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
