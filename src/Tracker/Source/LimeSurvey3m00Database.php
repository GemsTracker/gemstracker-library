<?php

namespace Gems\Tracker\Source;

class LimeSurvey3m00Database extends \Gems\Tracker\Source\LimeSurvey2m00Database
{
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
