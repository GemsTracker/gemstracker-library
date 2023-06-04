<?php

namespace Gems\Repository;

use Gems\Util\UtilDbHelper;

class SurveyRepository
{
    public function __construct(protected UtilDbHelper $utilDbHelper)
    {}

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
     *
     * @param array $allowedOrgIds Array of allowed organization ids
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array [Survey id => Survey name]
     */
    public function getAllSurveysForOrgs(array $allowedOrgIds): array
    {
        if ($allowedOrgIds) {
            $orgIn = "gto_id_organization IN (" . implode(',', $allowedOrgIds) . ")";
            $orgWhere = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", $allowedOrgIds) .
                "|') > 0)";
        } else {
            $orgIn = $orgWhere = "1 = 1";
        }
        $cacheId = __FUNCTION__ . '_' . implode('_', $allowedOrgIds);
        return $this->utilDbHelper->getSelectPairsCached($cacheId, 
          "(SELECT DISTINCT gsu_id_survey, gsu_survey_name
                    FROM gems__surveys INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gsu_active=1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        $orgWhere)

                UNION DISTINCT

                (SELECT DISTINCT gsu_id_survey, gsu_survey_name
                    FROM gems__tokens
                    INNER JOIN gems__surveys ON (gto_id_survey = gsu_id_survey AND gsu_active = 1)
                    INNER JOIN gems__tracks ON (gto_id_track = gtr_id_track AND gtr_active = 1)
                    WHERE
                        gto_id_round = 0 AND
                        $orgIn
                )
                ORDER BY gsu_survey_name");
    }
    
    /**
     * @return array mailId => description
     */
    public function getSurveyMailCodes(): array
    {
        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__mail_codes',
            'gmc_id',
            'gmc_mail_cause_target',
            ['mailcodes'],
            [
                'gmc_for_surveys' => 1,
                'gmc_active' => 1
            ],
            'ksort'
        );
    }
}