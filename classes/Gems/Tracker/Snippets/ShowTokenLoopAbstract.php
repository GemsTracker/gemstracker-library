<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ShowTokenLoopAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Basic class for creating forward loop snippets
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Snippets_ShowTokenLoopAbstract extends MUtil_Snippets_SnippetAbstract
{
    /**
     * General date format
     * @var string
     */
    protected $dateFormat = 'd MMMM yyyy';

    /**
     * Required
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Required, the current token, possibly already answered
     *
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     * Required
     *
     * @var Zend_View
     */
    protected $view;

    /**
     * Was this token already answered? Calculated from $token
     *
     * @var boolean
     */
    protected $wasAnswered;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->token instanceof Gems_Tracker_Token) {

            $this->wasAnswered = $this->token->isCompleted();

            return ($this->request instanceof Zend_Controller_Request_Abstract) &&
                    ($this->view instanceof Zend_View) &&
                    parent::checkRegistryRequestsAnswers();
        } else {
            return false;
        }
    }

    /**
     * Formats an completion date for this display
     *
     * @param MUtil_Date $dateTime
     * @return string
     */
    public function formatCompletion(MUtil_Date $dateTime)
    {
        $days = abs($dateTime->diffDays());

        switch ($days) {
            case 0:
                return $this->_('We have received your answers today. Thank you!');

            case 1:
                return $this->_('We have received your answers yesterday. Thank you!');

            case 2:
                return $this->_('We have received your answers 2 days ago. Thank you.');

            default:
                if ($days <= 14) {
                    return sprintf($this->_('We have received your answers %d days ago. Thank you.'), $days);
                }
                return sprintf($this->_('We have received your answers on %s. '), $dateTime->toString($this->dateFormat));
        }
    }

    /**
     * Formats an until date for this display
     *
     * @param MUtil_Date $dateTime
     * @return string
     */
    public function formatUntil(MUtil_Date $dateTime = null)
    {
        if (null === $dateTime) {
            return $this->_('This survey has no set time limit.');
        }

        $days = $dateTime->diffDays();

        switch ($days) {
            case 0:
                return array(MUtil_Html::create('strong', $this->_('Warning!!!')), ' ', $this->_('This survey must be answered today!'));

            case 1:
                return array(MUtil_Html::create('strong', $this->_('Warning!!')), ' ', $this->_('This survey must be answered tomorrow!'));

            case 2:
                return $this->_('Warning! This survey must be answered over 2 days!');

            default:
                if (abs($days) <= 14) {
                    if ($days >= 0) {
                        return sprintf($this->_('This survey must be answered in %d days.'), $days);
                    } else {
                        return $this->_('This survey can no longer be answered.');
                    }
                }
                return sprintf($this->_('This survey can be answered until %s.'), $dateTime->toString($this->dateFormat));
        }
    }

    /**
     * Get the href for a token
     *
     * @param Gems_Tracker_Token $token
     * @return MUtil_Html_HrefArrayAttribute
     */
    protected function getTokenHref(Gems_Tracker_Token $token)
    {
        /***************
         * Get the url *
         ***************/
        $params = array(
            $this->request->getActionKey() => 'to-survey',
            MUtil_Model::REQUEST_ID        => $token->getTokenId(),
            'RouteReset'                   => false,
            );

        return new MUtil_Html_HrefArrayAttribute($params);
    }
}
