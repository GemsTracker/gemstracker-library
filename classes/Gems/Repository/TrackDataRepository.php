<?php

namespace Gems\Repository;

use Gems\Db\ResultFetcher;
use Gems\Util\UtilDbHelper;
use Laminas\Db\Sql\Predicate\Predicate;

class TrackDataRepository
{
    public function __construct(protected UtilDbHelper $utilDbHelper, protected ResultFetcher $resultFetcher)
    {}

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