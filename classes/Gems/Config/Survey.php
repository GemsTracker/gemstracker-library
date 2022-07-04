<?php

namespace Gems\Config;

class Survey
{
    public function __invoke(): array
    {
        return [
            'ask' => $this->getAskSettings(),

            /* Optional default TrackId */
            //'defaultTrackId' => 700

        ];
    }


    protected function getAskSettings()
    {
        return [
            /* When no askDelay is specified or is -1 the user will see
            greeting screen were he or she will a have to click
            on a button to fill in a survey.

            With the askDelay is > 0 then greeting screen will
            be shown (with the button) but after the specified
            number of seconds the survey will load automatically.

            With an askDelay of 0 seconds the survey will load
            automatically. */
            'askDelay' => -1,

            /* askNextDelay works the same but applies to the wait
            after the user completed a survey while another survey
            is available. */
            'askNextDelay' => -1,

            /* Sets values that control the throttling (slowdowns to
            combat brute-force attacks) of the ask / token
            controller.
            askThrottle.period
            Look for failed token attempts in from now to
            X seconds ago.

            askThrottle.threshold
            If the number of failed token attempts exceeds this
            number, starting throttling.

            askThrottle.delay
            Throttle by delaying each request by X seconds. */
            'askThrottle' => [
                'period' => 900,
                'threshold' => 300,
                'delay' => 10,
            ],
        ];
    }
}