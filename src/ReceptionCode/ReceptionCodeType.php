<?php

namespace Gems\ReceptionCode;

enum ReceptionCodeType: string
{
    case RESPONDENT = 'respondent';
    case SURVEY = 'survey';
    case TRACK = 'track';

    public function getDatabaseField()
    {
        return match($this) {
            self::RESPONDENT => 'grc_for_respondents',
            self::SURVEY => 'grc_for_surveys',
            self::TRACK => 'grc_for_tracks',
        };
    }

    public static function createFromData(array $data): ?self
    {
        if ($data[self::RESPONDENT->getDatabaseField()] > 0) {
            return self::RESPONDENT;
        }
        if ($data[self::SURVEY->getDatabaseField()] > 0) {
            return self::SURVEY;
        }
        if ($data[self::TRACK->getDatabaseField()] > 0) {
            return self::TRACK;
        }
        return null;
    }
}
