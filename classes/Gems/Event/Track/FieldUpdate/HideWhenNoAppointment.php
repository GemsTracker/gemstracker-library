<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Track\FieldUpdate;

/**
 *
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 19-okt-2014 18:32:02
 */
class HideWhenNoAppointment extends \MUtil\Translate\TranslateableAbstract
    implements \Gems\Event\TrackFieldUpdateEventInterface
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Skip rounds without a valid appointment, hiding them for the user.');
    }

    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack \Gems respondent track object
     * @param int   $userId The current userId
     * @return void
     */
    public function processFieldUpdate(\Gems\Tracker\RespondentTrack $respTrack, $userId)
    {
        $agenda = $this->loader->getAgenda();
        $change = false;
        $token  = $respTrack->getFirstToken();

        if (! $token) {
            return;
        }

        do {
            if ($token->isCompleted()) {
                continue;
            }

            $appId = $respTrack->getRoundAfterAppointmentId($token->getRoundId());

            // Not a round without appointment id
            if ($appId !== false) {
                if ($appId) {
                    $appointment = $agenda->getAppointment($appId);
                } else {
                    $appointment = null;
                }

                if ($appointment && $appointment->isActive()) {
                    $newCode = \Gems\Escort::RECEPTION_OK;
                    $newText = null;
                } else {
                    $newCode = 'skip';
                    $newText = $this->_('Skipped until appointment is set');
                }
                $oldCode = \Gems\Escort::RECEPTION_OK === $newCode ? 'skip' : \Gems\Escort::RECEPTION_OK;
                $curCode = $token->getReceptionCode()->getCode();
                // \MUtil\EchoOut\EchoOut::track($token->getTokenId(), $curCode, $oldCode, $newCode);
                if (($oldCode === $curCode) && ($curCode !== $newCode)) {
                    $change = true;
                    $token->setReceptionCode($newCode, $newText, $userId);
                }
            }
        } while ($token = $token->getNextToken());

        if ($change) {
            $respTrack->refresh();
        }
    }
}
