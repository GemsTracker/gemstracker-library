<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Track\FieldUpdate;

use Gems\Agenda\Agenda;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\TrackEvent\TrackFieldUpdateEventInterface;
use MUtil\Translate\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 19-okt-2014 18:32:02
 */
class HideWhenNoAppointment implements TrackFieldUpdateEventInterface
{

    public function __construct(protected Translator $translator, protected Agenda $agenda)
    {}

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Skip rounds without a valid appointment, hiding them for the user.');
    }

    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param RespondentTrack $respTrack \Gems respondent track object
     * @param int   $userId The current userId
     * @return void
     */
    public function processFieldUpdate(RespondentTrack $respTrack, $userId): void
    {
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
                    $appointment = $this->agenda->getAppointment($appId);
                } else {
                    $appointment = null;
                }

                if ($appointment && $appointment->isActive()) {
                    $newCode = ReceptionCodeRepository::RECEPTION_OK;
                    $newText = null;
                } else {
                    $newCode = 'skip';
                    $newText = $this->translator->_('Skipped until appointment is set');
                }
                $oldCode = ReceptionCodeRepository::RECEPTION_OK === $newCode ? 'skip' : ReceptionCodeRepository::RECEPTION_OK;
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
