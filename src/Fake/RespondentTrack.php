<?php

namespace Gems\Fake;

class RespondentTrack extends \Gems\Tracker\RespondentTrack
{
    public function getCodeFields(): array
    {
        return [
            'exampleField' => 'exampleValue',
        ];
    }

    public function refresh(array $gemsData = null): self
    {
        return $this;
    }
}