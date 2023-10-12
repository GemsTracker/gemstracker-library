<?php


namespace Gems\Repository;


use Gems\Exception;
use Gems\Locale\Locale;
use Gems\Tracker;
use Gems\Tracker\Survey;

class SurveyQuestionsRepository
{
    protected array $questionTypeTranslations = [
        '!' => 'dropdown-list',
        'L' => 'list',
        'O' => 'list-comment',
        '1' => null,
        'H' => null,
        'F' => null,
        'R' => null,
        'A' => 'array-5',
        'B' => 'array-10',
        ':' => 'multi-array-10',
        '5' => '5-point-radio',
        'N' => 'number',
        'K' => 'multi-number',
        'Q' => 'multi-short-text',
        ';' => 'multi-array',
        'S' => 'short-text',
        'T' => 'long-text',
        'U' => 'huge-text',
        'M' => 'multiple-choice',
        'P' => 'multiple-choice-comment',
        'D' => 'date',
        '*' => 'equation',
        'I' => 'language',
        '|' => 'file-upload',
        'X' => 'empty',
        'G' => 'gender',
        'Y' => 'yes-no',
        'C' => 'yes-uncertain-no',
        'E' => 'increase-same-decrease',
    ];


    public function __construct(
        protected readonly Tracker $tracker,
        protected readonly Locale $locale,
    )
    {
    }

    public function getQuestionType(string $type): string|null
    {
        if (array_key_exists($type, $this->questionTypeTranslations)) {
            return $this->questionTypeTranslations[$type];
        }
        return null;
    }

    public function getSurvey(int $surveyId): Survey
    {
        return $this->tracker->getSurvey($surveyId);
    }

    /**
     * Get Survey Question information
     *
     * @param $surveyId
     * @return array
     */
    public function getSurveyQuestions(int $surveyId): array
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new Exception('No existing survey ID selected');
        }

        $questionInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        return $questionInformation;
    }

    /**
     * Get Survey Question List
     *
     * @param $surveyId
     * @return array
     */
    public function getSurveyList(int $surveyId): array
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new Exception('No existing survey ID selected');
        }

        $questionInformation = $survey->getQuestionList($this->locale->getLanguage());

        return $questionInformation;
    }

    /**
     * @param $surveyId
     * @return array
     */
    public function getSurveyListAndAnswers(int $surveyId, bool $addTypes = false): array
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new Exception('No existing survey ID selected');
        }

        $surveyInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        $questionList = [];
        foreach($surveyInformation as $questionCode=>$questionInformation) {
            $questionList[$questionCode] = [
                'question' => $questionInformation['question'],
            ];
            if (array_key_exists('answers', $questionInformation) && is_array($questionInformation['answers']) && !empty($questionInformation['answers'])) {
                $questionList[$questionCode]['answers'] = $questionInformation['answers'];
            }
            if ($addTypes && array_key_exists('type', $questionInformation)) {
                $questionList[$questionCode]['type'] = $this->getQuestionType($questionInformation['type']);
            }
        }

        return $questionList;
    }


}