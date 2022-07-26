<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 * Generic controller class for showing and editing organizations
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class OrganizationAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    public $accesslog;

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
     * @var \Gems\User\User
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
     * @var \Gems\Loader
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
                // Check for organization id in url, but not when a patient id is stated
                if (strpos($origUrl, '/' . \MUtil\Model::REQUEST_ID1 . '/') === false) {
                    foreach ($this->currentUser->possibleOrgIds as $key) {
                        $finds[]    = '/' . $key. '/' . $oldOrg;
                        $replaces[] = '/' . $key. '/' . $orgId;
                    }
                    $correctUrl = str_replace($finds, $replaces, $origUrl);
                } else {
                    $correctUrl = $origUrl;
                }
                // \MUtil\EchoOut\EchoOut::track($origUrl, $correctUrl);
                $this->getResponse()->setRedirect($correctUrl);
            } else {
                $this->currentUser->gotoStartPage($this->menu, $request);
            }
            return;
        }

        throw new \Gems\Exception(
                $this->_('Inaccessible or unknown organization'),
                403, null,
                sprintf(
                        $this->_('Access to this page is not allowed for current role: %s.'),
                        $this->currentUser->getRole()
                        )
                );
    }

    /**
     * Check a single organization
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTaskRunnerBatch('orgCheckAll');
        if (! $batch->isLoaded()) {

            $sql = "SELECT gr2o_id_user, gr2o_id_organization
                        FROM gems__respondent2org INNER JOIN gems__reception_codes ON gr2o_reception_code = grc_id_reception_code
                        WHERE grc_success = 1
                        ORDER BY gr2o_id_organization, gr2o_created";

            // \MUtil\EchoOut\EchoOut::track($sql);

            $rows = $this->db->fetchAll($sql);

            foreach ($rows as $row) {
                $batch->addTask('Respondent\\UpdateRespondentTask', $row['gr2o_id_user'], $row['gr2o_id_organization']);
            }
        }

        $title = $this->_("Checking all respondents.");
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Organization\\CheckOrganizationInformation');
    }

    /**
     * Check a single organization
     */
    public function checkOrgAction()
    {
        $go    = true;
        $orgId = $this->_getIdParam();
        $org   = $this->loader->getOrganization($orgId);

        if (! $org->getRespondentChangeEventClass()) {
            $go = false;
            $this->addMessage(sprintf(
                   $this->_('Nothing to do: respondent change event for %s not set.'),
                   $org->getName()
                   ));
        }
        if (! $org->canHaveRespondents()) {
            $go = false;
            $this->addMessage(sprintf(
                   $this->_('Nothing to do: %s has no respondents.'),
                   $org->getName()
                   ));
        }

        if ($go) {
            $batch = $this->loader->getTaskRunnerBatch('orgCheck' . $orgId);
            if (! $batch->isLoaded()) {

                $sql = "SELECT gr2o_id_user
                            FROM gems__respondent2org INNER JOIN gems__reception_codes ON gr2o_reception_code = grc_id_reception_code
                            WHERE gr2o_id_organization = ? AND grc_success = 1
                            ORDER BY gr2o_created";

                // \MUtil\EchoOut\EchoOut::track($sql);

                $respIds = $this->db->fetchCol($sql, $orgId);

                foreach ($respIds as $respId) {
                    $batch->addTask('Respondent\\UpdateRespondentTask', $respId, $orgId);
                }
            }

            $title = sprintf($this->_("Checking respondents for '%s'."), $org->getName());
            $this->_helper->BatchRunner($batch, $title, $this->accesslog);
       }

        $this->addSnippet('Organization\\CheckOrganizationInformation');
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
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        if ($this->escort instanceof \Gems\Project\Layout\MultiLayoutInterface) {
            $styles = \MUtil\Lazy::call(array($this->escort, 'getStyles'));
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
        $data    = $this->getModel()->loadFirst();
        $subject = $data['gor_name'];

        //Add location to the subject when not empty
        if (!empty($data['gor_location'])) {
            $subject .= ' - ' . $data['gor_location'];
        }

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
