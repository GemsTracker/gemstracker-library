<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Prepares displays of respondent information
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class Gems_Snippets_RespondentDetailSnippetAbstract extends \Gems_Snippets_MenuSnippetAbstract
{
    /**
     * Add the children of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentChildren = false;

    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentParent = false;

    /**
     * Add the siblings of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentSiblings = false;

    /**
     * Add siblings of the current menu item with any parameters.
     *
     * Add only those with the same when false.
     *
     * @var boolean
     */
    protected $anyParameterSiblings = false;

    /**
     * Optional: array of buttons
     *
     * @var array
     */
    protected $buttons;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Model_RespondentModel
     */
    protected $model;

    /**
     * Optional: href for onclick
     *
     * @var \MUtil_Html_HrefArrayAttribute
     */
    protected $onclick;

    /**
     * Optional: repeater respondent data
     *
     * @var \MUtil_Lazy_RepeatableInterface
     */
    protected $repeater;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

    /**
     * Optional: set display of buttons on or off
     *
     * @var boolean
     */
    protected $showButtons = true;

    /**
     * Show a warning if informed consent has not been set
     *
     * @var boolean
     */
    protected $showConsentWarning = true;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function addButtons(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        if ($this->showButtons) {
            if ($this->buttons) {
                $bridge->tfrow($this->buttons, array('class' => 'centerAlign'));
            } else {
                $bridge->tfrow($this->getMenuList($bridge), array('class' => 'centerAlign'));
            }
        }
    }

    /**
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function addOnClick(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        if ($this->onclick) {
            $bridge->tbody()->onclick = array('location.href=\'', $this->onclick, '\';');
        }
    }

    /**
     * Place to set the data to display
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    abstract protected function addTableCells(\MUtil_Model_Bridge_VerticalTableBridge $bridge);

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if (! $this->model instanceof \Gems_Model_RespondentModel) {
            $this->model = $this->loader->getModels()->getRespondentModel(true);
        }
    }

    /**
     * Check if we have the 'Unknown' consent, and present a warning. The project default consent is
     * normally 'Unknown' but this can be overruled in project.ini so checking for default is not right
     *
     * @static boolean $warned We need only one warning in case of multiple consents
     * @param string $consent
     */
    public function checkConsent($consent)
    {
        static $warned;

        if ($warned) {
            return $consent;
        }

        $unknown = $this->util->getConsentUnknown();

        // Value is translated by now if in bridge
        if (($consent == $unknown) || ($consent == $this->_($unknown))) {

            $warned    = true;
            $msg       = $this->_('Please settle the informed consent form for this respondent.');
            $urlString = '';

            if ($this->view instanceof \Zend_View) {
                $url[$this->request->getControllerKey()] = 'respondent';
                $url[\MUtil_Model::REQUEST_ID1]          = $this->request->getParam(\MUtil_Model::REQUEST_ID1);
                $url[\MUtil_Model::REQUEST_ID2]          = $this->request->getParam(\MUtil_Model::REQUEST_ID2);

                // \MUtil_Echo::track($this->menu->findAllowedController('respondent', 'change-consent'), $this->menu->findAllowedController('respondent', 'edit'));
                if ($this->menu->findAllowedController('respondent', 'change-consent')) {
                    $url[$this->request->getActionKey()] = 'change-consent';
                    $urlString = $this->view->url($url);

                } elseif ($this->menu->findAllowedController('respondent', 'edit')) {
                    $url[$this->request->getActionKey()] = 'edit';
                    $urlString = $this->view->url($url) . '#tabContainer-frag-4';
                }
            }
            if ($urlString) {
                $this->addMessage(\MUtil_Html::create()->a($urlString, $msg));
            } else {
                $this->addMessage($msg);
            }
        }

        return $consent;
    }

    /**
     * Returns the caption for this table
     *
     * @param boolean $onlyNotCurrent Only return a string when the organization is different
     * @return string
     */
    protected function getCaption($onlyNotCurrent = false)
    {
        $orgId = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
        if ($orgId == $this->loader->getCurrentUser()->getCurrentOrganizationId()) {
            if ($onlyNotCurrent) {
                return;
            } else {
                return $this->_('Respondent information');
            }
        } else {
            return sprintf($this->_('%s respondent information'), $this->loader->getOrganization($orgId)->getName());
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $bridge = $this->model->getBridgeFor('itemTable', array('class' => 'displayer table table-condensed'));
        $bridge->setRepeater($this->repeater);
        $bridge->setColumnCount(2); // May be overruled

        $this->addTableCells($bridge);

        if ($this->model->has('row_class')) {
            // Make sure deactivated rounds are show as deleted
            foreach ($bridge->getTable()->tbody() as $tr) {
                foreach ($tr as $td) {
                    if ('td' === $td->tagName) {
                        $td->appendAttrib('class', $bridge->row_class);
                    }
                }
            }
        }

        $this->addButtons($bridge);
        $this->addOnClick($bridge);

        $container = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $container[] = $bridge->getTable();
        return $container;
    }

    /**
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function getMenuList(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        $menuList = $this->menu->getCurrentMenuList($this->request, $this->_('Cancel'));
        $menuList->addParameterSources($bridge);

        if ($this->addCurrentParent) {
            $menuList->addCurrentParent($this->_('Cancel'));
        }
        if ($this->addCurrentSiblings) {
            $menuList->addCurrentSiblings($this->anyParameterSiblings);
        }
        if ($this->addCurrentChildren) {
            $menuList->addCurrentChildren();
        }

        return $menuList;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->model) {
            $this->model->setIfExists('gr2o_email', 'itemDisplay', array('MUtil_Html_AElement', 'ifmail'));
            $this->model->setIfExists('gr2o_comments', 'rowspan', 2);

            if ($this->showConsentWarning && $this->model->has('gr2o_consent')) {
                $this->model->set('gr2o_consent', 'formatFunction', array($this, 'checkConsent'));
            }

            if (! $this->repeater) {
                if (! $this->respondent) {
                    $this->repeater = $this->model->loadRepeatable();
                } else {
                    $data = array($this->respondent->getArrayCopy());

                    $this->repeater = \MUtil_Lazy::repeat($data);
                }
            }

            return true;
        }

        return false;
    }
}
