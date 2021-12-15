<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This events look for a previous copy of a survey with the same code
 *
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class Gems_Event_Survey_BeforeAnswering_GetPreviousAnswersByCode extends \MUtil_Registry_TargetAbstract
    implements \Gems_Event_SurveyBeforeAnsweringEventInterface
{
    /**
     * Set as this is a \MUtil_Registry_TargetInterface
     *
     * @var \Zend_Translate $translate
     */
    protected $translate;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->translate->_('Lookup answers in previous survey with the same survey code in track.');
    }

    /**
     * Process the data and return the answers that should be filled in beforehand.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return array Containing the changed values
     */
    public function processTokenInsertion(\Gems_Tracker_Token $token)
    {
        if ($token->getReceptionCode()->isSuccess() && (! $token->isCompleted())) {
            // Preparation for a more general object class
            $code = $token->getSurvey()->getCode();

            $prev = $token;
            while ($prev = $prev->getPreviousToken()) {

                if ($prev->getReceptionCode()->isSuccess() && $prev->isCompleted()) {
                    // Check first on survey id and when that does not work by name.
                    if ($prev->getSurvey()->getCode() == $code) {
                        return $prev->getRawAnswers();
                    }
                }
            }
        }
    }
}
