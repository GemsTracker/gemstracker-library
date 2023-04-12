<?php

namespace Gems\Fake;

class RespondentTrack extends \Gems\Tracker\RespondentTrack
{
    public function getCodeFields()
    {
        return [
            'exampleField' => 'exampleValue',
        ];
    }

    public function refresh(array $gemsData = null)
    {
        return $this;
    }
}