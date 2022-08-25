<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use Gems\Html;
use Gems\MenuNew\RouteHelper;
use MUtil\Model;

/**
 * Class that bundles information on tokens
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class TokenData extends \MUtil\Translate\TranslateableAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     *
     * @staticvar \Gems\Menu\SubMenuItem $menuItem
     * @return \Gems\Menu\SubMenuItem
     */
    protected function _getAnswerMenuItem()
    {
        static $menuItem;

        if (! $menuItem) {
            $menuItem = $this->menu->findController('track', 'answer');
        }

        return $menuItem;
    }

    /**
     *
     * @staticvar \Gems\Menu\SubMenuItem $menuItem
     * @return \Gems\Menu\SubMenuItem
     */
    protected function _getAskMenuItem()
    {
        static $menuItem;

        if (! $menuItem) {
            $menuItem = $this->menu->findController('ask', 'take');
        }

        return $menuItem;
    }

    /**
     *
     * @staticvar \Gems\Menu\SubMenuItem $menuItem
     * @return \Gems\Menu\SubMenuItem
     */
    protected function _getEmailMenuItem()
    {
        static $menuItem;

        if (! $menuItem) {
            $menuItem = $this->menu->findController('track', 'email');
        }

        return $menuItem;
    }

    /**
     *
     * @staticvar \Gems\Menu\SubMenuItem $menuItem
     * @return \Gems\Menu\SubMenuItem
     */
    protected function _getShowMenuItem()
    {
        static $menuItem;

        if (! $menuItem) {
            $menuItem = $this->menu->findController('track', 'show');
        }

        return $menuItem;
    }

    /**
     * Returns a status code => decription array
     *
     * @static $status array
     * @return array
     */
    public function getEveryStatus()
    {
        static $status;

        if ($status) {
            return $status;
        }

        $status = array(
            'U' => $this->_('Valid from date unknown'),
            'W' => $this->_('Valid from date in the future'),
            'O' => $this->_('Open - can be answered now'),
            'P' => $this->_('Open - partially answered'),
            'A' => $this->_('Answered'),
            'I' => $this->_('Incomplete - missed deadline'),
            'M' => $this->_('Missed deadline'),
            'D' => $this->_('Token does not exist'),
        );

        return $status;
    }

    /**
     * An expression for calculating the show status for answers
     *
     * @param int $groupId
     * @return \Zend_Db_Expr
     */
    public function getShowAnswersExpression($groupId)
    {
        return new \Zend_Db_Expr(sprintf(
            "CASE WHEN gsu_answers_by_group = 0 OR gsu_answer_groups LIKE '%%|%d|%%' THEN 1 ELSE 0 END",
            $groupId));
    }

    /**
     * Returns the class to display the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusClass($value)
    {
        switch ($value) {
            case 'A':
                return 'answered';
            case 'I':
                return 'incomplete';
            case 'M':
                return 'missed';
            case 'P':
                return 'partial';
            case 'O':
                return 'open';
            case 'U':
                return 'unknown';
            case 'W':
                return 'waiting';
            default:
                return 'empty';
        }
    }

    /**
     * Returns the description to add to the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusDescription($value)
    {
        $status = $this->getEveryStatus();

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status['D'];
    }

    /**
     * An expression for calculating the token status
     *
     * @return \Zend_Db_Expr
     */
    public function getStatusExpression()
    {
        return new \Zend_Db_Expr("
            CASE
            WHEN gto_id_token IS NULL OR grc_success = 0 THEN 'D'
            WHEN gto_completion_time IS NOT NULL         THEN 'A'
            WHEN gto_valid_from IS NULL                  THEN 'U'
            WHEN gto_valid_from > CURRENT_TIMESTAMP      THEN 'W'
            WHEN gto_in_source = 1 AND gto_valid_until < CURRENT_TIMESTAMP THEN 'I'
            WHEN gto_valid_until < CURRENT_TIMESTAMP     THEN 'M'
            WHEN gto_in_source = 1                       THEN 'P'
            ELSE 'O'
            END
            ");
    }

    /**
     * Returns the SQL Expression
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusExpressionFor($value)
    {
        switch ($value) {
            case 'D':
                return 'gto_id_token IS NULL OR grc_success = 0';
            case 'A':
                return 'grc_success = 1 AND gto_completion_time IS NOT NULL';
            case 'U':
                return 'grc_success = 1 AND gto_valid_from IS NULL';
            case 'W':
                return 'grc_success = 1 AND gto_valid_from > CURRENT_TIMESTAMP';
            case 'I':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND gto_in_source = 1 AND gto_valid_until < CURRENT_TIMESTAMP';
            case 'M':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND gto_in_source = 0 AND gto_valid_until < CURRENT_TIMESTAMP';
            case 'P':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1';
            case 'O':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 0';
        }
    }

    /**
     * Returns the decription to add to the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusIcon($value)
    {
        $status = $this->getStatusIcons();

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status['D'];
    }

    /**
     * Returns the status icons in an array
     *
     * @return array
     */
    public function getStatusIcons()
    {
        static $status;

        if (is_null($status)) {
            $spanU = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanU->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanU->i(array('class' => 'fa fa-question fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanW = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanW->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanW->i(array('class' => 'fa fa-ellipsis-h fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanO = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanO->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanO->i(array('class' => 'fa fa-play fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanA = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanA->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanA->i(array('class' => 'fa fa-check fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanP = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanP->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanP->i(array('class' => 'fa fa-pause fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanI = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanI->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanI->i(array('class' => 'fa fa-stop fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanM = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanM->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanM->i(array('class' => 'fa fa-lock fa-stack-1x fa-inverse', 'renderClosingTag' => true));

            $spanD = \MUtil\Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanD->i(array('class' => 'fa fa-times fa-stack-2x', 'renderClosingTag' => true));

            $status = array(
                'U' => $spanU,
                'W' => $spanW,
                'O' => $spanO,
                'A' => $spanA,
                'P' => $spanP,
                'I' => $spanI,
                'M' => $spanM,
                'D' => $spanD,
            );

            foreach ($status as $val => $stat) {
                $stat->appendAttrib('class', $this->getStatusClass($val));
            }
        }

        return $status;
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $keepCaps Keep the capital letters in the label
     * @param boolean $showAnswers
     * @return \MUtil\Html\AElement
     */
    public function getTokenAnswerLink($patientNr, $organizationId, $tokenId, $tokenStatus, $keepCaps, $showAnswers = true)
    {
        if ('A' == $tokenStatus || 'P' == $tokenStatus || 'I' == $tokenStatus) {
            $routeName = 'respondent.tracks.answer';
            $label = $this->_('Answer');

            $link = Html::actionLink($this->routeHelper->getRouteUrl($routeName, [
                'id' => $tokenId,
                Model::REQUEST_ID1 => $patientNr,
                Model::REQUEST_ID2 => $organizationId,
            ]), $label);

            if ($link) {
                $link->title = sprintf($this->_('See answers for token %s'), strtoupper($tokenId));

                return $link;
            }

        }
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return \MUtil\Lazy\Call
     */
    public function getTokenAnswerLinkForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge, $keepCaps = false)
    {
        if (! $this->currentUser->hasPrivilege('pr.answer')) {
            //return null;
        }

        return \MUtil\Lazy::method($this, 'getTokenAnswerLink',
            $bridge->getLazy('gr2o_patient_nr'),
            $bridge->getLazy('gto_id_organization'),
            $bridge->getLazy('gto_id_token'),
            $bridge->getLazy('token_status'),
            $keepCaps,
            $bridge->getLazy('show_answers')
        );
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $staffToken Is token answerable by staff
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return \MUtil\Html\AElement
     */
    public function getTokenAskButton($patientNr, $organizationId, $tokenId, $tokenStatus, $staffToken, $keepCaps)
    {
        if ('O' == $tokenStatus || 'P' == $tokenStatus) {
            if ($staffToken) {
                $routeName = 'respondent.tracks.answer';
                $label = $this->_('Fill in');

                if ('P' == $tokenStatus) {
                    $label = $this->_('Continue');
                }

                $link = Html::actionLink($this->routeHelper->getRouteUrl($routeName, [
                    'id' => $tokenId,
                    Model::REQUEST_ID1 => $patientNr,
                    Model::REQUEST_ID2 => $organizationId,
                ]), $label);

                if ($link) {
                    $link->title = sprintf($this->_('Answer token %s'), strtoupper($tokenId));

                    return $link;
                }
            }

            return $this->getTokenCopyLink($tokenId, $tokenStatus);
        }
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @param boolean $forceButton Always show a button
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return \MUtil\Lazy\Call
     */
    public function getTokenAskButtonForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge, $forceButton = false, $keepCaps = false)
    {
        if (! $this->currentUser->hasPrivilege('pr.ask')) {
            return null;
        }

        if ($forceButton) {
            return \MUtil\Lazy::method($this, 'getTokenAskButton',
                $bridge->getLazy('gto_id_token'), $bridge->getLazy('token_status'), true, $keepCaps
            );
        }

        return \MUtil\Lazy::method($this, 'getTokenAskButton',
            $bridge->getLazy('gr2o_patient_nr'),
            $bridge->getLazy('gto_id_organization'),
            $bridge->getLazy('gto_id_token'),
            $bridge->getLazy('token_status'),
            $bridge->getLazy('ggp_staff_members'),
            $keepCaps
        );
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @param boolean $forceButton Always show a button
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return \MUtil\Lazy\Call
     */
    public function getTokenAskLinkForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge, $forceButton = false, $keepCaps = false)
    {
        $method = $this->getTokenAskButtonForBridge($bridge, $forceButton, $keepCaps);

        if (! $method) {
            $method = \MUtil\Lazy::method($this, 'getTokenCopyLink',
                $bridge->getLazy('gto_id_token'), $bridge->getLazy('token_status')
            );
        }

        return [
            $method,
            'class' => \MUtil\Lazy::method($this, 'getTokenCopyLinkClass',
                $bridge->getLazy('token_status'), $bridge->getLazy('ggp_staff_members')
            ),
        ];
    }

    /**
     * Generate a token item with (in the future) a copy to clipboard button
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @return string
     */
    public function getTokenCopyLink($tokenId, $tokenStatus)
    {
        if ('O' == $tokenStatus || 'P' == $tokenStatus) {
            return $tokenId . ' ';
        }
    }

    /**
     * Generate a token item with (in the future) a copy to clipboard button
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $staffToken Is token answerable by staff
     * @return string
     */
    public function getTokenCopyLinkClass($tokenStatus, $staffToken)
    {
        if (('O' == $tokenStatus || 'P' == $tokenStatus) && ! $staffToken) {
            return 'token';
        }
    }

    /**
     * Generate a menu link for email screen
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $canMail
     * @return \MUtil\Html\AElement
     */
    public function getTokenEmailLink($tokenId, $tokenStatus, $canMail)
    {
        if ($canMail && ('O' == $tokenStatus || 'P' == $tokenStatus)) {
            $menuItem = $this->_getEmailMenuItem();

            $link = $menuItem->toActionLinkLower([
                'gto_id_token' => $tokenId,
                'can_be_taken' => 1,
                'can_email'    => 1,
                \Gems\Model::ID_TYPE => 'token',
            ]);

            if ($link) {
                $link->title = sprintf($this->_('Send email for token %s'), strtoupper($tokenId));

                return $link;
            }
        }
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @return \MUtil\Lazy\Call
     */
    public function getTokenEmailLinkForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge)
    {
        if (! $this->currentUser->hasPrivilege($this->_getEmailMenuItem()->getPrivilege())) {
            return null;
        }

        return \MUtil\Lazy::method($this, 'getTokenEmailLink',
            $bridge->getLazy('gto_id_token'), $bridge->getLazy('token_status'), $bridge->getLazy('can_email')
        );
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param boolean $plusLabel Show plus instead of label
     * @return \MUtil\Html\AElement
     */
    public function getTokenShowLink($patientNr, $organizationId, $tokenId, $plusLabel)
    {
        $routeName = 'respondent.tracks.show';
        $label = $this->_('Show');

        if ($plusLabel) {
            $label = $this->_('+');
        }

        $link = Html::actionLink($this->routeHelper->getRouteUrl($routeName, [
            Model::REQUEST_ID1 => $patientNr,
            Model::REQUEST_ID2 => $organizationId,
            Model::REQUEST_ID => $tokenId,
        ]), $label);

        if ($link) {
            $link->title = sprintf($this->_('Inspect token %s'), strtoupper($tokenId));
        }

        return $link;
    }

    /**
     * De a lazy show link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @param boolean $plusLabel Show plus instead of label
     * @return \MUtil\Lazy\Call
     */
    public function getTokenShowLinkForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge, $plusLabel = true)
    {
        if (! $this->currentUser->hasPrivilege('respondent.track.show')) {
            //return null;
        }

        return \MUtil\Lazy::method($this, 'getTokenShowLink',
            $bridge->getLazy('gr2o_patient_nr'),
            $bridge->getLazy('gto_id_organization'),
            $bridge->getLazy('gto_id_token'),
            $plusLabel
        );
    }

    /**
     * De a lazy status description text for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @param boolean $addDescription Add the description after the icon
     * @return \MUtil\Lazy\Call
     */
    public function getTokenStatusDescriptionForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge, $addDescription = false)
    {
        return \MUtil\Lazy::method($this, 'getStatusDescription', $bridge->getLazy('token_status'));
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param string $patientNr
     * @param string $roundDescr
     * @param string $surveyName
     * @param string $result
     * @return \MUtil\Html\AElement
     */
    public function getTokenStatusLink($tokenId, $tokenStatus, $patientNr, $roundDescr, $surveyName, $result)
    {
        $menuItem = $this->_getShowMenuItem();

        if ($tokenId && $menuItem) {
            $href = $menuItem->toHRefAttribute([
                'gto_id_token' => $tokenId,
                \Gems\Model::ID_TYPE => 'token',
            ]);

            $link = \MUtil\Html::create('a', $href, [
                'onclick' => 'event.cancelBubble = true;',
            ]);

            // $link->title = sprintf($this->_('Inspect token %s'), strtoupper($tokenId));
        } else {
            $link = \MUtil\Html::create('span', [
                'onclick' => 'event.cancelBubble = true;',
            ]);
        }

        if ($link) {
            $link->append($this->getStatusIcon($tokenStatus));
            $link->title = $this->getTokenStatusTitle($tokenId, $tokenStatus, $patientNr, $roundDescr, $surveyName, $result);

            return $link;
        }
    }

    /**
     * De a lazy status show link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @return \MUtil\Lazy\Call
     */
    public function getTokenStatusLinkForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge)
    {
        if (! $this->currentUser->hasPrivilege('pr.respondent.track.show')) {
            return $this->getTokenStatusShowForBridge($bridge);
        }

        return \MUtil\Lazy::method($this, 'getTokenStatusLink',
            $bridge->getLazy('gto_id_token'), $bridge->getLazy('token_status'),
            $bridge->getLazy('gr2o_patient_nr'), $bridge->getLazy('gto_round_description'),
            $bridge->getLazy('gsu_survey_name'), $bridge->getLazy('gto_result')
        );
    }

    /**
     * De a lazy status show link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @return \MUtil\Lazy\Call
     */
    public function getTokenStatusLinkForTokenId($tokenId)
    {
        $token = $tokenId ? $this->loader->getTracker()->getToken($tokenId) : null;

        if (! ($tokenId && $token->exists)) {
            return $this->getStatusIcon('D');
        }

        return $this->getTokenStatusLink(
            $tokenId, $token->getStatusCode(),
            $token->getPatientNumber(), $token->getRoundDescription(),
            $token->getSurveyName(), $token->getResult()
        );
    }

    /**
     * De a lazy status show link for bridges
     *
     * @param \MUtil\Model\Bridge\TableBridgeAbstract $bridge
     * @return \MUtil\Lazy\Call
     */
    public function getTokenStatusShowForBridge(\MUtil\Model\Bridge\TableBridgeAbstract $bridge)
    {
        return \MUtil\Lazy::method($this, 'getStatusIcon', $bridge->getLazy('token_status'));
    }

    /**
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param string $patientNr
     * @param string $roundDescr
     * @param string $surveyName
     * @param string $result
     * @return string
     */
    public function getTokenStatusTitle($tokenId, $tokenStatus, $patientNr, $roundDescr, $surveyName, $result)
    {
        $title = sprintf($this->_('Token %s: %s'), strtoupper($tokenId), $this->getStatusDescription($tokenStatus));
        if ($roundDescr) {
            $title .= sprintf("\n" . $this->_('Round') . ': %s', $roundDescr);
        }
        if ($surveyName) {
            $title .= "\n" . $surveyName;
        }
        if (!empty($patientNr)) {
            $title .= sprintf("\n" . $this->_('%s: %s'), $this->_('Respondent nr'), $patientNr);
        }
        if ((!empty($result)) && $this->currentUser->hasPrivilege('pr.respondent.result')) {
            $title .= sprintf("\n" . $this->_('%s: %s'), $this->_('Result'), $result);
        }

        return $title;
    }
}
