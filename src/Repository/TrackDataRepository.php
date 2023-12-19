<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Util\UtilDbHelper;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Zalt\Base\TranslatorInterface;

class TrackDataRepository
{
    protected array $cacheTags = [
        'track',
        'tracks',
    ];

    public function __construct(
        protected UtilDbHelper $utilDbHelper,
        protected ResultFetcher $resultFetcher,
        protected CachedResultFetcher $cachedResultFetcher,
        protected TranslatorInterface $translator,
    )
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
            $this->cacheTags,
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

        return $this->utilDbHelper->getSelectPairsCached(__FUNCTION__, $select, null, $this->cacheTags);
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

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name plus gsu_survey_description
     *
     * @return array
     */
    public function getAllSurveysAndDescriptions(): array
    {
        $select = 'SELECT gsu_id_survey,
            	CONCAT(
            		SUBSTR(CONCAT_WS(
            			" - ", gsu_survey_name, CASE WHEN LENGTH(TRIM(gsu_survey_description)) = 0 THEN NULL ELSE gsu_survey_description END
            		), 1, 50),
        			CASE WHEN gsu_active = 1 THEN " (' . $this->translator->_('Active') . ')" ELSE " (' . $this->translator->_('Inactive') . ')" END
    			)
            	FROM gems__surveys ORDER BY gsu_survey_name';

        return $this->cachedResultFetcher->fetchPairs(__CLASS__ . '_' . __FUNCTION__, $select, null, ['surveys']);
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
     */
    public function getAllTracks(): array
    {
        $where = new Predicate();
        $where->notEqualTo('gtr_track_class', 'SingleSurveyEngine');

        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__tracks',
            'gtr_id_track',
            'gtr_track_name',
            $this->cacheTags,
            [$where],
            'asort'
        );
    }

    public function getAllActiveTrackOptions(): array
    {
        return array_column($this->getAllActiveTrackData(), 'gtr_track_name', 'gtr_id_track');
    }

    public function getAllActiveTrackData(): array
    {
        $trackData = $this->getAllTrackData();

        return array_filter($trackData, function($track) {
            return (bool)$track['gtr_active'];
        });
    }

    public function getAllTrackData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__tracks');
        $select->order('gtr_track_name');
        $select->where->notEqualTo('gtr_track_class', 'SingleSurveyEngine');

        return $this->cachedResultFetcher->fetchAll('allTracks', $select, null, $this->cacheTags);
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * that are active and are insertable
     *
     * @param int $organizationId Optional organization id
     * @return array
     */
    public function getInsertableSurveys(int $organizationId = null)
    {
        $where = new Predicate();
        $where->equalTo('gsu_active', 1)->and->equalTo('gsu_insertable', 1);
        if ($organizationId !== null) {
            $orgId = (int) $organizationId;
            $where->and->like('gsu_insert_organizations', "%|$organizationId|%");
        }

        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__surveys',
            'gsu_id_survey',
            'gsu_survey_name',
            ['surveys'],
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
        $result1 = $this->resultFetcher->fetchCol($select1);

        $select2 = $this->resultFetcher->getSelect('gems__track_fields');
        $select2->columns(['gtf_field_name'])
            ->where(['gtf_field_type' => 'relation', 'gtf_id_track' => $trackId]);
        $result2 = $this->resultFetcher->fetchCol($select2);
        
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

        $result = $this->resultFetcher->fetchCol($sql);

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
            $this->cacheTags,
            [$whereNest],
            'asort'
        );
    }

    /**
     * Returns title of the track.
     *
     * @param int $trackId
     * @return string|null
     */
    public function getTrackTitle(int $trackId)
    {
        $tracks = $this->getAllTracks();

        if ($tracks && isset($tracks[$trackId])) {
            return $tracks[$trackId];
        }
        return null;
    }
}
