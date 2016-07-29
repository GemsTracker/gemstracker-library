<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Tracker_Snippets_ShowTokenLoopAbstract extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * General date format
     * @var string
     */
    protected $dateFormat = 'd MMMM yyyy';

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Switch for showing the duration.
     *
     * @var boolean
     */
    protected $showDuration = true;

    /**
     * Required, the current token, possibly already answered
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * Required
     *
     * @var \Zend_View
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
        if ($this->token instanceof \Gems_Tracker_Token) {

            $this->wasAnswered = $this->token->isCompleted();

            return ($this->request instanceof \Zend_Controller_Request_Abstract) &&
                    ($this->view instanceof \Zend_View) &&
                    parent::checkRegistryRequestsAnswers();
        } else {
            return false;
        }
    }

    /**
     * Formats an completion date for this display
     *
     * @param \MUtil_Date $dateTime
     * @return string
     */
    public function formatCompletion(\MUtil_Date $dateTime)
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
     * Returns the duration if it should be displayed.
     *
     * @param string $duration
     * @return string
     */
    public function formatDuration($duration)
    {
        if ($duration && $this->showDuration) {
            return sprintf($this->_('Takes about %s to answer.'),  $duration) . ' ';
        }
    }

    /**
     * Formats an until date for this display
     *
     * @param \MUtil_Date $dateTime
     * @return string
     */
    public function formatUntil(\MUtil_Date $dateTime = null)
    {
        if (null === $dateTime) {
            return $this->_('Survey has no time limit.');
        }

        $days = $dateTime->diffDays();

        switch ($days) {
            case 0:
                return array(
                    \MUtil_Html::create('strong', $this->_('Warning!!!')),
                    ' ',
                    $this->_('This survey must be answered today!')
                    );

            case 1:
                return array(
                    \MUtil_Html::create('strong', $this->_('Warning!!')),
                    ' ',
                    $this->_('This survey can only be answered until tomorrow!')
                    );

            case 2:
                return $this->_('Warning! This survey can only be answered for another 2 days!');

            default:
                if ($days <= 14) {
                    return sprintf($this->_('Please answer this survey within %d days.'), $days);
                }

                if ($days <= 0) {
                    return $this->_('This survey can no longer be answered.');
                }

                return sprintf($this->_('Please answer this survey before %s.'), $dateTime->toString($this->dateFormat));
        }
    }

    /**
     * Get the href for a token
     *
     * @param \Gems_Tracker_Token $token
     * @return \MUtil_Html_HrefArrayAttribute
     */
    protected function getTokenHref(\Gems_Tracker_Token $token)
    {
        /***************
         * Get the url *
         ***************/
        $params = array(
            $this->request->getActionKey() => 'to-survey',
            \MUtil_Model::REQUEST_ID        => $token->getTokenId(),
            'RouteReset'                   => false,
            );

        return new \MUtil_Html_HrefArrayAttribute($params);
    }
}
