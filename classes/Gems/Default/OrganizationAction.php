<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Generic controller class for showing and editing organizations
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_OrganizationAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Organization\\OrganizationTableSnippet';

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('organization', 'organizations');

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Organization\\OrganizationEditSnippet';

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
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Organization\\OrganizationSearchSnippet');

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * Switch the active organization
     */
    public function changeUiAction()
    {
        $request  = $this->getRequest();
        $orgId    = urldecode($request->getParam('org'));
        $oldOrg   = $this->currentUser->getCurrentOrganizationId();
        $origUrl  = base64_decode($request->getParam('current_uri'));

        $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        if (isset($allowedOrganizations[$orgId])) {
            $this->currentUser->setCurrentOrganization($orgId);

            if ($origUrl) {
                // Check for organisation id in url, but not when a patient id is stated
                if (strpos($origUrl, '/' . \MUtil_Model::REQUEST_ID1 . '/') === false) {
                    foreach ($this->currentUser->possibleOrgIds as $key) {
                        $finds[]    = '/' . $key. '/' . $oldOrg;
                        $replaces[] = '/' . $key. '/' . $orgId;
                    }
                    $correctUrl = str_replace($finds, $replaces, $origUrl);
                } else {
                    $correctUrl = $origUrl;
                }
                // \MUtil_Echo::track($origUrl, $correctUrl);
                $this->getResponse()->setRedirect($correctUrl);
            } else {
                $this->currentUser->gotoStartPage($this->menu, $request);
            }
            return;
        }

        throw new \Gems_Exception(
                $this->_('Inaccessible or unknown organization'),
                403, null,
                sprintf(
                        $this->_('Access to this page is not allowed for current role: %s.'),
                        $this->currentUser->getRole()
                        )
                );
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
        if ($this->escort instanceof \Gems_Project_Layout_MultiLayoutInterface) {
            $styles = \MUtil_Lazy::call(array($this->escort, 'getStyles'));
        } else {
            $styles = array();
        }
        $model = $this->loader->getModels()->getOrganizationModel($styles);

        if ($detailed) {
            if (('create' == $action) || ('edit' == $action)) {
                $model->applyEditSettings();
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    public function getEditTitle()
    {
        $data = $this->getModel()->loadFirst();

        //Add location to the subject
        $subject = sprintf('%s - %s', $data['gor_name'], $data['gor_location']);

        return sprintf($this->_('Edit %s %s'), $this->getTopic(1), $subject);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Participating organizations');
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter();

        if (isset($filter['respondentstatus'])) {
            switch ($filter['respondentstatus']) {
                case 'maybePatient':
                    $filter[] = '(gor_add_respondents = 1 OR gor_has_respondents = 1)';
                    break;

                case 'createPatient':
                    $filter['gor_add_respondents'] = 1;
                    break;
                case 'noNewPatient':
                    $filter['gor_add_respondents'] = 0;
                    // Intentional fall through

                case 'hasPatient':
                    $filter['gor_has_respondents'] = 1;
                    break;

                case 'noPatient':
                    $filter['gor_add_respondents'] = 0;
                    $filter['gor_has_respondents'] = 0;
                    break;

            }
            unset($filter['respondentstatus']);
        }
        if (isset($filter['accessible_by'])) {
            $filter[] = $this->db->quoteInto('gor_accessible_by LIKE ?', '%:' . intval($filter['accessible_by']) . ':%');

            unset($filter['accessible_by']);
        }

        return $filter;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('organization', 'organizations', $count);
    }
}
