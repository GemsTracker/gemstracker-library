<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model;
use Gems\User\UserLoader;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Generic controller class for showing and editing organizations
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class OrganizationHandler extends ModelSnippetLegacyHandlerAbstract
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
    protected array $autofilterSnippets = ['Organization\\OrganizationTableSnippet'];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['organization', 'organizations'];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = ['Organization\\OrganizationEditSnippet'];

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
    protected array $indexStartSnippets = [
        'Generic\\ContentTitleSnippet',
        'Organization\\OrganizationSearchSnippet'
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected UserLoader $userLoader,
        protected Model $modelLoader,
    )
    {
        parent::__construct($responder, $translate);
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
        $org   = $this->userLoader->getOrganization($orgId);

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
    public function createModel(bool $detailed, string $action): ModelAbstract
    {
        $styles = [];

        $model = $this->modelLoader->getOrganizationModel($styles);

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

    public function getEditTitle(): string
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
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Participating organizations');
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
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
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('organization', 'organizations', $count);
    }
}
