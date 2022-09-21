<?php


namespace Gems\Repository;


use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class RespondentRepository
{
    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function getRespondentId($patientNr, $organizationId=null)
    {
        if ($patient = $this->getPatient($patientNr, $organizationId)) {
            if (array_key_exists('gr2o_id_user', $patient)) {
                return $patient['gr2o_id_user'];
            }
        }
        return false;
    }

    public function getPatient($patientNr, $organizationId=null)
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
        return false;
    }

    public function getOtherPatientNumbers($patientNr, $organizationId, $combined=false)
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
}
