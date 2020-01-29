<?php

class Gems_Tracker_Source_LimeSurvey3m00Database extends \Gems_Tracker_Source_LimeSurvey2m00Database
{
    /**
     * @var array meta data fields that are included in a survey table
     */
    public static $metaFields = [
        'id',
        'submitdate',
        'lastpage',
        'startlanguage',
        'token',
        'datestamp',
        'startdate',
        'seed',
    ];

    /**
     * Replaces hyphen with underscore so LimeSurvey won't choke on it
     *
     * @param string $token
     * @param boolean $reverse  Reverse the action to go from limesurvey to GemsTracker token (default is false)
     * @return string
     */
    protected function _getToken($tokenId, $reverse = false)
    {
        $tokenId = strtolower($tokenId);
        if ($reverse) {
            return strtr($tokenId, '_', '-');
        } else {
            return strtr($tokenId, '-', '_');
        }
    }
}
