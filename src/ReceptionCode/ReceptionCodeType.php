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

    public static function createFromData(array $data): array|null
    {
        $types = [];
        if ($data[self::RESPONDENT->getDatabaseField()] > 0) {
            $types[self::RESPONDENT->value] = self::RESPONDENT;
        }
        if ($data[self::SURVEY->getDatabaseField()] > 0) {
            $types[self::SURVEY->value] = self::SURVEY;
        }
        if ($data[self::TRACK->getDatabaseField()] > 0) {
            $types[self::TRACK->value] = self::TRACK;
        }
        if ($types) {
            return $types;
        }
        return null;
    }
}
