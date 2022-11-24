<?php

namespace Gems\Repository;

use Gems\Db\ResultFetcher;
use Gems\Util\UtilDbHelper;

class TrackDataRepository
{
    public function __construct(protected UtilDbHelper $utilDbHelper, protected ResultFetcher $resultFetcher)
    {}

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

        $test = $this->resultFetcher->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys');

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
}