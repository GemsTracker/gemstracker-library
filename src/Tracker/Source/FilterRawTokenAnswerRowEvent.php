<?php

namespace Gems\Tracker\Source;

use Symfony\Contracts\EventDispatcher\Event;

class FilterRawTokenAnswerRowEvent extends Event
{
    public function __construct(
        public readonly int $surveyId,
        public readonly int $sourceSurveyId,
        public readonly array $metaFields,
        public array $rawTokenAnswerRow,
    )
    {}
}