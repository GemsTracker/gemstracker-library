<?php

namespace Gems\Agenda\Event;

class AppointmentChangedEvent extends AppointmentEvent
{
    protected int $tokensChanged = 0;

    /**
     * @return int
     */
    public function getTokensChanged(): int
    {
        return $this->tokensChanged;
    }

    /**
     * @param int $tokensChanged
     */
    public function setTokensChanged(int $tokensChanged): void
    {
        $this->tokensChanged = $tokensChanged;
    }
}