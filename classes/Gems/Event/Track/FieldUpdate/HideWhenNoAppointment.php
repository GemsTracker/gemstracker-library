<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: HideWhenNoAppointment.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 19-okt-2014 18:32:02
 */
class Gems_Event_Track_FieldUpdate_HideWhenNoAppointment extends \MUtil_Translate_TranslateableAbstract
    implements \Gems_Event_TrackFieldUpdateEventInterface
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Skip rounds whose appointment is not yet set, hiding them from the user.');
    }

    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack Gems respondent track object
     * @param int   $userId The current userId
     * @return void
     */
    public function processFieldUpdate(\Gems_Tracker_RespondentTrack $respTrack, $userId)
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
                    $newCode = GemsEscort::RECEPTION_OK;
                    $newText = null;
                } else {
                    $newCode = 'skip';
                    $newText = $this->_('Skipped until appointment is set');
                }
                $oldCode = GemsEscort::RECEPTION_OK === $newCode ? 'skip' : GemsEscort::RECEPTION_OK;
                $curCode = $token->getReceptionCode()->getCode();
                // MUtil_Echo::track($token->getTokenId(), $curCode, $oldCode, $newCode);
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
