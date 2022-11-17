<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Completed;

use Gems\Db\ResultFetcher;
use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use Gems\Util;
use MUtil\Translate\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Apr 6, 2016 11:21:11 AM
 */
class SetInformedConsent implements SurveyCompletedEventInterface
{
    public function __construct(protected Translator $translator, protected ResultFetcher $resultFetcher, protected Util $util)
    {}

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_("Use the 'informedconsent' answer to set the informed consent.");
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(Token $token): array
    {
        if (! $token->getReceptionCode()->isSuccess()) {
            return [];
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
                    $consentCode = $this->resultFetcher->fetchOne(
                            "SELECT gco_description FROM gems__consents WHERE gco_description LIKE ? ORDER BY gco_order",
                            [$answers['informedconsent'] . '%']
                    );
                } else {
                    $consentCode = false;
                }

                if (! $consentCode) {
                    if ($answers['informedconsent']) {
                        // Code not found, use first positive consent
                        $consentCode = $this->resultFetcher->fetchOne(
                                "SELECT gco_description FROM gems__consents WHERE gco_code != ? ORDER BY gco_order",
                                [$this->util->getConsentRejected()]
                                );
                    } else {
                        // Code not found, use first negative consent
                        $consentCode = $this->resultFetcher->fetchOne(
                                "SELECT gco_description FROM gems__consents WHERE gco_code = ? ORDER BY gco_order",
                                [$this->util->getConsentRejected()]
                                );
                    }
                }
            }

            $respondent = $token->getRespondent();
            $values = array(
                'gr2o_patient_nr'      => $respondent->getPatientNumber(),
                'gr2o_id_user'         => $respondent->getId(),
                'gr2o_id_organization' => $respondent->getOrganizationId(),
                'gr2o_consent'         => $consentCode,
                'old_gr2o_consent'     => $respondent->getConsent()->getDescription(),
            );
            $model = $respondent->getRespondentModel();
            $model->save($values);

            if ($model->getChanged()) {
                // Refresh only the consent in the token
                $token->refreshConsent();

                // Make sure the NEW consent is applied to this survey itself
                $survey = $token->getSurvey();
                $survey->copyTokenToSource($token, '');
            }
        }

        return [];
    }
}
