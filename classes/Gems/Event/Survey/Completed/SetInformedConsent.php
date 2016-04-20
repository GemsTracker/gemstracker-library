<?php

/**
 * Copyright (c) 2016, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Completed;

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Apr 6, 2016 11:21:11 AM
 */
class SetInformedConsent extends \MUtil_Translate_TranslateableAbstract
    implements \Gems_Event_SurveyCompletedEventInterface
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_("Use the 'informedconsent' answer to set the informed consent.");
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(\Gems_Tracker_Token $token)
    {
        if (! $token->getReceptionCode()->isSuccess()) {
            return;
        }
        $answers = $token->getRawAnswers();

        if (isset($answers['informedconsent'])) {
            $consent = $this->util->getConsent($answers['informedconsent']);

            if ($consent->exists) {
                // Is existing consent description as answer
                $consentCode = $consent->getDescription();
            } else {
                if ($answers['informedconsent']) {
                    // Uses start of consent description as answer (LS has only 5 chars for an answer option)
                    $consentCode = $this->db->fetchOne(
                            "SELECT gco_description FROM gems__consents WHERE gco_description LIKE ? ORDER BY gco_order",
                            $answers['informedconsent'] . '%'
                            );
                } else {
                    $consentCode = false;
                }

                if (! $consentCode) {
                    if ($answers['informedconsent']) {
                        // Code not found, use first positive consent
                        $consentCode = $this->db->fetchOne(
                                "SELECT gco_description FROM gems__consents WHERE gco_code != ? ORDER BY gco_order",
                                $this->util->getConsentRejected()
                                );
                    } else {
                        // Code not found, use first negative consent
                        $consentCode = $this->db->fetchOne(
                                "SELECT gco_description FROM gems__consents WHERE gco_code = ? ORDER BY gco_order",
                                $this->util->getConsentRejected()
                                );
                    }
                }
            }

            $respondent = $token->getRespondent();
            $values = array(
                'gr2o_patient_nr'      => $respondent->getPatientNumber(),
                'gr2o_id_organization' => $respondent->getOrganizationId(),
                'gr2o_consent'         => $consentCode,
            );
            $respondent->getRespondentModel()->save($values);
        }

        return false;
    }
}
