<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_OptionAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('staff');

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The parameters used for the reset action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $changePasswordParameters = array(
        'askOld'           => true,
        'menuShowSiblings' => true,
        'routeAction'      => 'edit',
        'user'             => 'getCurrentUser',
        );

    /**
     * Snippets for reset
     *
     * @var mixed String or array of snippets name
     */
    protected $changePasswordSnippets = array('User\\PasswordResetSnippet');

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array(
        'menuShowChildren' => true,
        'onlyUsedElements' => true,
        'routeAction'      => 'edit',
        );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'User\\OwnAccountEditSnippet';

    /**
     * The parameters used for the reset action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $overviewParameters = array(
        'browse'          => true,
        'contentTitle'    => 'getShowLogOverviewTitle',
        'explanationText' => 'getShowLogOverviewExplanation',
        'extraFilter'     => 'getShowLogOverviewFilter',
        'menuEditActions' => false,
        'menuShowActions' => array('show-log'),
        );

    /**
     * Snippets for reset
     *
     * @var mixed String or array of snippets name
     */
    protected $overviewSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Generic\\TextExplanationSnippet',
        'Log\\LogTableSnippet',
        'Generic\\CurrentSiblingsButtonRowSnippet',
        );

    /**
     * The parameters used for the showLog action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showLogParameters = array(
        'contentTitle' => 'getShowLogItemTitle',
        );

    /**
     * Snippets for showLog
     *
     * @var mixed String or array of snippets name
     */
    protected $showLogSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Log\\LogShowSnippet',
        'Generic\\CurrentButtonRowSnippet',
        );

    /**
     * Allow a user to change his / her password.
     */
    public function changePasswordAction()
    {
        if ($this->changePasswordSnippets) {
            $params = $this->_processParameters($this->changePasswordParameters);

            $this->addSnippets($this->changePasswordSnippets, $params);
        }
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getStaffModel(false);

        $model->applyOwnAccountEdit();

        return $model;
    }

    /**
     *
     * @return \Gems_User_User
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle()
    {
        return $this->_('Options');
    }

    /**
     *
     * @return string Title for show log item
     */
    public function getShowLogItemTitle()
    {
        return $this->_('Show activity');
    }

    /**
     *
     * @return string Explanation for show log overview
     */
    public function getShowLogOverviewExplanation()
    {
        return $this->_('This overview provides information about the last login activity on your account.');
    }

    /**
     * Get a filter for the show log snippet
     */
    public function getShowLogOverviewFilter()
    {
        return array('gla_by' => $this->currentUser->getUserId());
    }

    /**
     *
     * @return string Title for show log overview
     */
    public function getShowLogOverviewTitle()
    {
        return $this->_('Activity overview');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('your setup', 'your setup', $count);
    }

    /**
     * Show log overview for the current user
     */
    public function overviewAction()
    {
        if ($this->overviewSnippets) {
            $params = $this->_processParameters($this->overviewParameters);

            $this->addSnippets($this->overviewSnippets, $params);
        }
    }

    /**
     * Show a log item
     */
    public function showLogAction()
    {
        if ($this->showLogSnippets) {
            $params = $this->_processParameters($this->showLogParameters);

            $this->addSnippets($this->showLogSnippets, $params);
        }
    }
}
