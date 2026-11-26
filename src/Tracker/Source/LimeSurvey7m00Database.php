<?php


namespace Gems\Tracker\Source;

class LimeSurvey7m00Database extends LimeSurvey3m00Database
{
    /**
     *
     * @var string class name for creating field maps
     */
    protected string $fieldMapClass = LimeSurvey7m00FieldMap::class;

    /**
     * @var string[] metadata fields that are included in a survey table
     */
    public static array $metaFields = [
        'id',
        'submitdate',
        'lastpage',
        'startlanguage',
        'token',
        'datestamp',
        'startdate',
        'seed',
        'quota_exit',
    ];

    public const SURVEY_TABLE       = 'responses_';

    protected function _filterAnswersOnly(int|string $sourceSurveyId, array $answers): array
    {
        return array_filter($answers, fn ($key) => (bool) preg_match('/^Q\d+/', (string) $key), ARRAY_FILTER_USE_KEY);
    }
}
