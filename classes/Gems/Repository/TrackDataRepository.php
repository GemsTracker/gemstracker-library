<?php

namespace Gems\Repository;

use Gems\Db\ResultFetcher;
use Gems\Util\UtilDbHelper;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;

class TrackDataRepository
{
    public function __construct(protected UtilDbHelper $utilDbHelper, protected ResultFetcher $resultFetcher)
    {}

    public function getActiveTracksForOrgs(array $organizationIds)
    {
        $where = new Predicate();

        $whereNest = $where->equalTo('gtr_active', 1)->and->nest();
        foreach($organizationIds as $key => $organizationId) {
            $whereNest->like('gtr_organizations', "%|$organizationId|%");
            $whereNest = $whereNest->or;
        }
        $whereNest = $whereNest->and;
        $where = $whereNest->unnest();

        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__tracks',
            'gtr_id_track',
            'gtr_track_name',
            ['tracks'],
            [$where],
            'asort'
        );
    }

    /**
     * Returns array (description => description) of all round descriptions in all tracks, sorted by name
     *
     * @return array
     */
    public function getAllRoundDescriptions()
    {
        $select = $this->resultFetcher->getSelect('gems__rounds');
        $select->columns([
            'gro_round_description',
            'gro_round_description',
        ])->where
            ->isNotNull('gro_round_description')
            ->notEqualTo('gro_round_description', '')
            ->notEqualTo('gro_id_round', 0);
        $select->group(['gro_round_description']);

        return $this->utilDbHelper->getSelectPairsCached(__FUNCTION__, $select, null, ['tracks']);
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * @param  boolean $active Only show active surveys Default: False
     * @return array of survey ID and survey name pairs
     */
    public function getAllSurveys(bool $active = false): array
    {
        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__surveys',
            'gsu_id_survey',
            'gsu_survey_name',
            ['surveys'],
            $active ? ['gsu_active' => 1] : null,
            'asort'
        );
    }

    public function getAllTrackRoundsForOrgs(array $allowedOrgIds)
    {
        $where = new Predicate();
        $where->notEqualTo('gtr_track_class', 'SingleSurveyEngine');
        
    }

    /**
     * Returns array (id => name) of all tracks, sorted alphabetically
     *
     * @return array
     * @throws \Zend_Cache_Exception
     */
    public function getAllTracks()
    {
        $where = new Predicate();
        $where->notEqualTo('gtr_track_class', 'SingleSurveyEngine');

        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__tracks',
            'gtr_id_track',
            'gtr_track_name',
            ['tracks'],
            [$where],
            'asort'
        );
    }

    public function getRespondersForTrack(int $trackId): array
    {
        $select1 = $this->resultFetcher->getSelect('gems__groups');
        $select1->columns(['ggp_name'])
            ->join('gems__surveys', 'ggp_id_group = gsu_id_primary_group', [])
            ->join('gems__rounds', 'gsu_id_survey = gro_id_survey', [])
            ->join('gems__tracks', 'gro_id_track = gtr_id_track', [])
            ->where(['gro_active' => 1, 'gtr_active' => 1, 'gtr_id_track' => $trackId]);
        $result1 = $this->resultFetcher->fetchCol($select1) ?: [];

        $select2 = $this->resultFetcher->getSelect('gems__track_fields');
        $select2->columns(['gtf_field_name'])
            ->where(['gtf_field_type' => 'relation', 'gtf_id_track' => $trackId]);
        $result2 = $this->resultFetcher->fetchCol($select2) ?: [];
        
        $output = array_unique(array_merge($result1, $result2));
        
        return array_combine($output, $output);
    }
    
    /**
     * Returns all available languages used in surveys
     *
     * @return array
     */
    public function getSurveyLanguages(): array
    {
        $return = [];
        $sql = "SELECT DISTINCT gsu_survey_languages
                    FROM gems__surveys
                    ORDER BY gsu_survey_languages";

        $result = $this->resultFetcher->fetchCol($sql) ?: [];

        foreach ($result as $value) {
            if (strpos($value, ', ') !== false) {
                $results = explode(', ', $value);
                foreach ($results as $values) {
                    $return[$values] = $values;
                }
            } else {
                $return[$value] = $value;
            }
        }

        return $return;
    }

    /**
     * @param array $organizations As $organizationId => $organizationName
     * @return array
     */
    public function getTracksForOrgs(array $organizations)
    {
        $whereNest = new Predicate([], PredicateSet::COMBINED_BY_OR);

        foreach($organizations as $organizationId => $organizationName) {
            $whereNest->like('gtr_organizations', "%|$organizationId|%");
        }
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($whereNest->, true) . "\n", FILE_APPEND);

        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__tracks',
            'gtr_id_track',
            'gtr_track_name',
            ['tracks'],
            [$whereNest],
            'asort'
        );
    }

    /**
     * Returns title of the track.
     *
     * @param int $trackId
     * @return string
     */
    public function getTrackTitle(int $trackId)
    {
        $tracks = $this->getAllTracks();

        if ($tracks && isset($tracks[$trackId])) {
            return $tracks[$trackId];
        }
    }
}