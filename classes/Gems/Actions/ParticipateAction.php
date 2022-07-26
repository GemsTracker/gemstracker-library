<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Actions;

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
class ParticipateAction extends \Gems\Controller\Action
{
    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * The parameters used for the subscribe-thanks action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $subscribeThanksParameters = [];

    /**
     * The snippets used for the subscribe-thanks action, usually called after unsubscribe
     *
     * @var mixed String or array of snippets name
     */
    protected $subscribeThanksSnippets = ['Subscribe\\ThankYouForSubscribingSnippet'];

    /**
     * The parameters used for the unsubscribe-thanks action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $unsubscribeThanksParameters = [];

    /**
     * The snippets used for the unsubscribe-thanks action, usually called after unsubscribe
     *
     * @var mixed String or array of snippets name
     */
    protected $unsubscribeThanksSnippets = ['Unsubscribe\\UnsubscribedSnippet'];

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
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input)
    {
        $output = array();

        foreach ($input as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value($key);

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }

        return $output;
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
        if ($this->subscribeThanksSnippets) {
            $params = $this->_processParameters($this->subscribeThanksParameters);

            $this->addSnippets($this->subscribeThanksSnippets, $params);
        }
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
        if ($this->unsubscribeThanksSnippets) {
            $params = $this->_processParameters($this->unsubscribeThanksParameters);

            $this->addSnippets($this->unsubscribeThanksSnippets, $params);
        }
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
