<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

use Gems\Screens\SubscribeScreenInterface;
use Gems\Screens\UnsubscribeScreenInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.6 18-Mar-2019 16:02:12
 */
class Gems_Default_ParticipateAction extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * To initiate the use of $this->html from the code call $this->initHtml()
     *
     * Overrules $useRawOutput.
     *
     * @see $useRawOutput
     * @var boolean $useHtmlView
     */
    public $useHtmlView = true;

    protected function _getScreenOrgs($onfield)
    {
        $select = $this->db->select();
        $select->from('gems__organizations', ['gor_id_organization', 'gor_name'])
                ->where("LENGTH($onfield) > 0")
                ->order('gor_name');

        return $this->db->fetchPairs($select);
    }

    /**
     * Ask the user which organization to participate with
     *
     * @return void
     */
    public function subscribeAction()
    {
        $orgId = urldecode($this->getRequest()->getParam('org'));

        if ($orgId && ($orgId != $this->currentUser->getCurrentOrganizationId())) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
            if ((! $this->currentUser->isActive()) || isset($allowedOrganizations[$orgId])) {
                $this->currentUser->setCurrentOrganization($orgId);
            }
        }

        $this->html->h1($this->_('Subscribe'));

        $screen = $this->currentUser->getCurrentOrganization()->getSubscribeScreen();

        if ($screen instanceof SubscribeScreenInterface) {
            $params   = $screen->getSubscribeParameters();
            $snippets = $screen->getSubscribeSnippets();
        } else {
            $list = $this->_getScreenOrgs('gor_respondent_subscribe');
            if ($list) {
                $params   = [
                    'action' => 'subscribe',
                    'info'   => $this->_('Select an organization to subscribe to:'),
                    'orgs'   => $list,
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            } else {
                $params   = [];
                $snippets = ['Subscribe\\NoSubscriptionsSnippet'];
            }
        }

        $this->addSnippets($snippets, $params);
    }

    /**
     * Show the thanks screen
     *
     * @return void
     */
    public function subscribeThanksAction()
    {
        $this->addSnippets(['Subscribe\\ThankYouForSubscribingSnippet']);
    }

    /**
     * Ask the user which organization to unsubscribe from
     *
     * @return void
     */
    public function unsubscribeAction()
    {
        $orgId = urldecode($this->getRequest()->getParam('org'));

        if ($orgId && ($orgId != $this->currentUser->getCurrentOrganizationId())) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
            if ((! $this->currentUser->isActive()) || isset($allowedOrganizations[$orgId])) {
                $this->currentUser->setCurrentOrganization($orgId);
            }
        }

        $this->html->h1($this->_('Unsubscribe'));

        $screen = $this->currentUser->getCurrentOrganization()->getUnsubscribeScreen();

        if ($screen instanceof UnsubscribeScreenInterface) {
            $params   = $screen->getUnsubscribeParameters();
            $snippets = $screen->getUnsubscribeSnippets();
        } else {
            $list = $this->_getScreenOrgs('gor_respondent_unsubscribe');
            if ($list) {
                $params   = [
                    'action' => 'unsubscribe',
                    'info'   => $this->_('Select an organization to unsubscribe from:'),
                    'orgs'   => $list,
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            } else {
                $params   = [];
                $snippets = ['Unsubscribe\\NoUnsubscriptionsSnippet'];
            }
        }

        $this->addSnippets($snippets, $params);
    }

    /**
     * Show the thanks screen
     *
     * @return void
     */
    public function unsubscribeThanksAction()
    {
        $this->addSnippets(['Unsubscribe\\UnsubscribedSnippet']);
    }

    /**
     * Ask the user which organization to participate with
     *
     * @return void
     */
    public function unsubscribeToOrgAction()
    {
        $request = $this->getRequest();
        $orgId   = urldecode($request->getParam('org'));

        $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        if ((! $this->currentUser->isActive()) || isset($allowedOrganizations[$orgId])) {
            $this->currentUser->setCurrentOrganization($orgId);
        }

        $this->forward('unsubscribe');
    }
}
