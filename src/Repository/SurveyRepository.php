<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Tracker\Survey;
use Gems\Translate\CachedDbTranslationRepository;
use Gems\Translate\DbTranslationRepository;
use Gems\Util\UtilDbHelper;
use Zalt\Loader\ProjectOverloader;

class SurveyRepository
{
    protected array $cacheTags = [
        'survey',
        'surveys'
    ];

    protected array $defaultData = [
        'gsu_active' => 0,
        'gsu_code' => null,
        'gsu_valid_for_length' => 6,
        'gsu_valid_for_unit' => 'M',
    ];

    /**
     * @var int Counter for new surveys, negative value used as temp survey id
     */
    public static int $newSurveyCount = 0;

    public function __construct(
        protected UtilDbHelper $utilDbHelper,
        protected CachedResultFetcher $cachedResultFetcher,
        protected CachedDbTranslationRepository $cachedDbTranslationRepository,
        protected ProjectOverloader $projectOverloader,
        protected GroupRepository $groupRepository,
    )
    {}

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * @param  boolean $active Only show active surveys Default: False
     * @return array of survey ID and survey name pairs
     */
    public function getAllSurveyOptions(bool $active = false): array
    {
        if ($active) {
            $surveyData = $this->getAllActiveSurveyData();
        } else {
            $surveyData = $this->getAllSurveyData();
        }

        return array_column($surveyData, 'gsu_survey_name', 'gsu_id_survey');
    }

    public function getAllActiveSurveyData(): array
    {
        $surveys = $this->getAllSurveyData();
        return array_filter($surveys, function($survey) {
            return (bool)$survey['gsu_active'];
        });
    }

    public function getAllSurveyData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__surveys');
        $select->order('gsu_survey_name');

        return $this->cachedResultFetcher->fetchAll('allSurveyData', $select, null, $this->cacheTags);
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

    public function getSurvey(array|int|null $surveyData): Survey
    {
        $surveyData = $this->getSurveyData($surveyData);

        return $this->projectOverloader->create('Tracker\\Survey', $surveyData);
    }

    /**
     * Get all the surveys for a certain code
     *
     * @param string $code
     * @return array survey id => survey name
     */
    public function getSurveysByCode(string $code): array
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $code;

        $select = $this->cachedResultFetcher->getSelect('gems__surveys');
        $select->columns(['gsu_id_survey', 'gsu_survey_name'])
            ->where([
                "gsu_code" => $code,
                "gsu_active" => 1,
            ])
            ->order(['gsu_survey_name']);

        return $this->cachedResultFetcher->fetchPairs($cacheId, $select, [], ['surveys']);
    }

    public function getSurveyData(array|int|null $surveyData): array
    {
        $data = null;
        $surveyId = $surveyData;

        if (is_array($surveyData) && isset($surveyData['gsu_id_survey'])) {
            $data = $surveyData;
            if (!isset($data['ggp_member_type']) && isset($data['gsu_id_primary_group'])) {
                $data['ggp_member_type'] = $this->groupRepository->getGroupMemberType($data['gsu_id_primary_group']);
            }
            $surveyId = $surveyData['gsu_id_survey'];
        }

        $cacheId = 'getSurveyData' . $surveyId;

        if (!$data) {
            if ($surveyId !== null) {
                $select = $this->cachedResultFetcher->getSelect('gems__surveys');
                $select->join('gems__groups', 'ggp_id_group = gsu_id_primary_group', [
                    'ggp_member_type',
                ], $select::JOIN_LEFT)
                    ->where(['gsu_id_survey' => $surveyId]);

                $data = $this->cachedResultFetcher->fetchRow(
                    'getSurveyData' . $surveyId,
                    $select,
                    null,
                    $this->cacheTags
                );
            }

            if (!$data) {
                self::$newSurveyCount++;
                return ['gsu_id_survey' => -self::$newSurveyCount] + $this->defaultData;
            }
        }

        return $this->cachedDbTranslationRepository->translateTable($cacheId, 'gems__surveys', 'gsu_id_survey', $data);
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

    public function getTrackSurveyOptions(int $trackId): array
    {
        $resultFetcher = $this->cachedResultFetcher->getResultFetcher();
        $select = $resultFetcher->getSelect('gems__rounds');
        $select->columns(['gro_id_survey', 'gro_survey_name'])
            ->where([
                'gro_id_track' => $trackId,
                'gro_active' => 1,
            ])
            ->group('gro_id_survey');

        return $resultFetcher->fetchPairs($select);
    }
}