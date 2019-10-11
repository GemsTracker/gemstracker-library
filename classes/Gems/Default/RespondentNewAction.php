<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Screens\ConsentInterface;
use Gems\Screens\ProcessModelInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
abstract class Gems_Default_RespondentNewAction extends \Gems_Default_RespondentChildActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraSort'   => array('gr2o_opened' => SORT_DESC),
        'respondent'  => null,
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Respondent\\RespondentTableSnippet';

    /**
     * The parameters used for the change consent action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $changeConsentParameters = array(
        'editMailable'     => true,
        'menuShowSiblings' => true,
        'menuShowChildren' => true,
        'resetRoute'       => true,
        'useTabbedForm'    => false,
        );

    /**
     * The snippets used for the change consent action.
     *
     * @var mixed String or array of snippets name
     */
    protected $changeConsentSnippets = [
        'Respondent\\Consent\\RespondentConsentFormSnippet',
        'Respondent\\Consent\\RespondentConsentLogSnippet',
        ];

    /**
     * The parameters used for the change organization action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $changeOrganizationParameters = array(
        'keepConsent' => false,
        );

    /**
     * The snippets used for the change organization action.
     *
     * @var mixed String or array of snippets name
     */
    protected $changeOrganizationSnippets = 'Respondent\\ChangeRespondentOrganization';

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
        'menuShowSiblings' => true,
        'menuShowChildren' => true,
        'resetRoute'       => true,
        'useTabbedForm'    => true,
        );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippet names
     */
    protected $createEditSnippets = [
        'Respondent\\RespondentFormSnippet',
        'Respondent\\Consent\\RespondentConsentLogSnippet',
        ];

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = array('respondent' => null);

    /**
     *
     * @var \Gems_User_Organization
     */
    public $currentOrganization;

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = array('grc_success' => 1);

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deleteParameters = array(
        'baseUrl'        => 'getItemUrlArray',
        'forOtherOrgs'   => 'getOtherOrgs',
        'onclick'        => 'getEditLink',
        // 'respondentData' => 'getRespondentData',
        'showButtons'    => false,
        );

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    public $deleteSnippets = array('Respondent\\RespondentDetailsSnippet', 'Respondent\\DeleteRespondentSnippet');

    /**
     *
     * @var boolean En/disable group screen switching
     */
    protected $enableScreens = true;

    /**
     * The snippets used for the export action.
     *
     * @var mixed String or array of snippets name
     */
    public $exportSnippets = array('Respondent\\RespondentDetailsSnippet');

    /**
     * Array of the actions that use the model in form version.
     *
     * This determines the value of forForm().
     *
     * @var array $formActions Array of the actions that use the model with a form.
     */
    public $formActions = array('create', 'delete', 'edit', 'import', 'simpleApi');

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Respondent\\RespondentSearchSnippet');

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * The parameters used for the overview action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $overviewParameters = array();

    /**
     * The snippets used for the overview action.
     *
     * @var mixed String or array of snippets name
     */
    protected $overviewSnippets   = array('Respondent\\RespondentOverviewSnippet');

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array(
        'baseUrl'        => 'getItemUrlArray',
        'forOtherOrgs'   => 'getOtherOrgs',
        'onclick'        => 'getEditLink',
        // 'respondentData' => 'getRespondentData',
        '-run-once'      => 'openedRespondent',
    );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Respondent\\MultiOrganizationTab',
        'Respondent\\RespondentDetailsSnippet',
    	'Tracker\\AddTracksSnippet',
        'Token\\TokenTabsSnippet',
        'Token\\RespondentTokenSnippet',
    );

    /**
     *
     * @var \MUtil_Registry_SourceInterface
     */
    public $source;

    /**
     * The actions that should result in the survey return being set.
     *
     * @var array
     */
    protected $tokenReturnActions = array(
        'index',
        'show',
    );

    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        if ($resetMvc && $this->enableScreens) {
            $group = $this->currentUser->getGroup();

            if ($group) {
                $browse = $group->getRespondentBrowseScreen();

                if ($browse) {
                    // All are arrays, so easy to set
                    $this->autofilterParameters = $browse->getAutofilterParameters() + $this->autofilterParameters;

                    $autoSnippets = $browse->getAutofilterSnippets();
                    if (false !== $autoSnippets) {
                        $this->autofilterSnippets = $autoSnippets;
                    }
                }
            }
        }
        parent::autofilterAction($resetMvc);
    }

    /**
     * Action to change a users
     */
    public function changeConsentAction()
    {
        if ($this->enableScreens) {
            $edit = false;
            $org  = $this->getRespondent()->getOrganization();

            if ($org) {
                $edit = $org->getRespondentEditScreen();
            }

            if (! $edit) {
                $group = $this->currentUser->getGroup();
                if ($group) {
                    $edit = $group->getRespondentEditScreen();
                }
            }

            if ($edit) {
                if ($edit instanceof ProcessModelInterface) {
                    $edit->processModel($this->getModel());
                }

                // All are arrays, so easy to set
                $this->editParameters = $edit->getEditParameters() + $this->editParameters;
                if ($edit instanceof ConsentInterface) {
                    $this->changeConsentParameters = $edit->getConsentParameters() + $this->changeConsentParameters;
                    $changeSnippets = $edit->getConsentSnippets();
                    if (false !== $changeSnippets) {
                        $this->changeConsentSnippets =  $changeSnippets;
                    }
                }
            }
        }
        if ($this->changeConsentSnippets) {
            $params = $this->_processParameters(
                    $this->changeConsentParameters +
                    $this->editParameters +
                    $this->createEditParameters +
                    ['createData' => false]);

            $this->addSnippets($this->changeConsentSnippets, $params);
        }
    }

    /**
     * Action to change a users
     */
    public function changeOrganizationAction()
    {
        if ($this->changeOrganizationSnippets) {
            $params = $this->_processParameters(
                    $this->changeOrganizationParameters +
                    $this->editParameters +
                    $this->createEditParameters +
                    ['createData' => false]);

            $this->addSnippets($this->changeOrganizationSnippets, $params);
        }
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        if ($this->enableScreens) {
            $edit = false;
            $org  = $this->getRespondent()->getOrganization();
            if (! $org) {
                $org = $this->currentOrganization;
            }

            if ($org) {
                $edit = $org->getRespondentEditScreen();
            }

            if (! $edit) {
                $group = $this->currentUser->getGroup();
                if ($group) {
                    $edit = $group->getRespondentEditScreen();
                }
            }

            if ($edit ) {
                if ($edit instanceof ProcessModelInterface) {
                    $edit->processModel($this->getModel());
                }

                // All are arrays, so easy to set
                $this->createParameters = $edit->getCreateParameters() + $this->createParameters;
                $editSnippets = $edit->getSnippets();
                if (false !== $editSnippets) {
                    $this->createEditSnippets = $editSnippets;
                }
            }
        }

        parent::createAction();
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
    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->createRespondentModel();

        if (! $detailed) {
            $model->applyBrowseSettings();
        } else {
            switch ($action) {
                case 'create':
                case 'change-consent':
                case 'edit':
                case 'import':
                case 'simple-api':
                    $model->applyEditSettings($action == 'create');
                    break;

                case 'delete':
                default:
                    $model->applyDetailSettings();
                    break;
            }
        }

        return $model;
    }

    /**
     * Action for showing a delete item page
     */
    public function deleteAction()
    {
        $this->deleteParameters['formTitle'] = $this->_('Delete or stop respondent');

        parent::deleteAction();
    }

    /**
     * Action for showing a edit item page with extra title
     */
    public function editAction()
    {
        if ($this->enableScreens) {
            $edit = false;
            $org  = $this->getRespondent()->getOrganization();

            if ($org) {
                $edit = $org->getRespondentEditScreen();
            }

            if (! $edit) {
                $group = $this->currentUser->getGroup();
                if ($group) {
                    $edit = $group->getRespondentEditScreen();
                }
            }

            if ($edit) {
                if ($edit instanceof ProcessModelInterface) {
                    $edit->processModel($this->getModel());
                }

                // All are arrays, so easy to set
                $this->editParameters = $edit->getEditParameters() + $this->editParameters;
                $editSnippets = $edit->getSnippets();
                if (false !== $editSnippets) {
                    $this->createEditSnippets = $editSnippets;
                }
            }
        }

        parent::editAction();
    }

    /**
     * Action for dossier export
     */
    public function exportArchiveAction()
    {
        $params = $this->_processParameters($this->showParameters);

        $this->addSnippets($this->exportSnippets, $params);

        $this->html->h2($this->_('Export respondent archive'));

        //Now show the export form
        $export = $this->loader->getRespondentExport();
        $form   = $export->getForm();
        $div    = $this->html->div(array('id' => 'mainform'));
        $div[]  = $form;

        $request = $this->getRequest();

        $form->populate($request->getParams());

        if ($request->isPost()) {
            $respondent = $this->getRespondent();
            $patients   = array(
                array(
                    'gr2o_id_organization' => $respondent->getOrganizationId(),
                    'gr2o_patient_nr'      => $respondent->getPatientNumber()
                    )
                );
            $export->render($patients, $request->getParam('group'), $request->getParam('format'));
        }
    }

    /**
     * Get the link to edit respondent
     *
     * @return \MUtil_Html_HrefArrayAttribute
     */
    public function getEditLink()
    {
        $request = $this->getRequest();

        $item = $this->menu->find(array(
            $request->getControllerKey() => $request->getControllerName(),
            $request->getActionKey() => 'edit',
            'allowed' => true));

        if ($item) {
            return $item->toHRefAttribute($request);
        }
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle()
    {
        $respondent = $this->getRespondent();
        if ($respondent->exists) {
            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_last_name')) {
                return sprintf($this->_('Edit respondent nr %s'), $respondent->getPatientNumber());
            }
            return sprintf(
                    $this->_('Edit respondent nr %s: %s'),
                    $respondent->getPatientNumber(),
                    $respondent->getName()
                    );
        }
        return parent::getEditTitle();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Respondents');
    }

    /**
     * Return the array with items that should be used to find this item
     *
     * @return array
     */
    public function getItemUrlArray()
    {
        return array(
            \MUtil_Model::REQUEST_ID1 => $this->_getParam(\MUtil_Model::REQUEST_ID1),
            \MUtil_Model::REQUEST_ID2 => $this->_getParam(\MUtil_Model::REQUEST_ID2),
            );
    }

    /**
     * The organizations whose tokens are shown.
     *
     * When true: show tokens for all organizations, false: only current organization, array => those organizations
     * @return boolean|array
     */
    public function getOtherOrgs()
    {
        return $this->util->getOtherOrgsFor($this->getRespondent()->getOrganizationId());
    }

    /**
     * Retrieve the respondent data in advance
     * (So we don't need to repeat that for every snippet.)
     *
     * @return array
     */
    public function getRespondentData()
    {
        return $this->getRespondent()->getArrayCopy();
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        // The actions do not set an respondent id
        if (in_array($this->getRequest()->getActionName(), $this->summarizedActions)) {
            return null;
        }

        return parent::getRespondentId();
    }

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array
     */
    public function getSearchData($useRequest = true)
    {
        $data = parent::getSearchData($useRequest);

        if (isset($data[\MUtil_Model::REQUEST_ID2])) {
            $orgs = intval($data[\MUtil_Model::REQUEST_ID2]);
        } else {
            $orgs = $this->currentUser->getRespondentOrgFilter();
        }

        $activeTracks = $this->util->getTrackData()->getActiveTracks($orgs);

        // Cache used by RespondentSearchSnippet and $this->getSearchFilter()
        $data['__active_tracks'] = $activeTracks;

        return $data;
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults()
    {
        if (! isset($this->defaultSearchData[\MUtil_Model::REQUEST_ID2])) {
            if ($this->currentUser->hasPrivilege('pr.respondent.multiorg') &&
                    (! $this->currentOrganization->canHaveRespondents())) {
                $this->defaultSearchData[\MUtil_Model::REQUEST_ID2] = '';
            } else {
                $this->defaultSearchData[\MUtil_Model::REQUEST_ID2] = $this->currentOrganization->getId();
            }
        }

        $this->defaultSearchData['gr2t_id_track'] = 'show_all';

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        if (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track']) {
            switch ($filter['gr2t_id_track']) {
                case 'show_without_track':
                    $filter[] = "NOT EXISTS (SELECT * FROM gems__respondent2track
                           WHERE gr2o_id_user = gr2t_id_user AND gr2o_id_organization = gr2t_id_organization)";
                    // Intentional fall through
                case 'show_all':
                    unset($filter['gr2t_id_track']);
                    break;

                case 'show_with_track':
                default:
                    $model = $this->getModel();
                    if (! $model->hasAlias('gems__respondent2track')) {
                        $model->addTable(
                                'gems__respondent2track',
                                array('gr2o_id_user' => 'gr2t_id_user', 'gr2o_id_organization' => 'gr2t_id_organization')
                                );
                    }
                    if (! $model->hasAlias('gems__tracks')) {
                        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
                    }
                    if (! isset($filter['__active_tracks'], $filter['__active_tracks'][$filter['gr2t_id_track']])) {
                        unset($filter['gr2t_id_track']);
                    }

            }
        }

        if (! isset($filter['show_with_track'])) {
            $filter['show_with_track'] = 1;
        }

        unset($filter['__active_tracks']);

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
        return $this->plural('respondent', 'respondents', $count);;
    }

    /**
     * Overrule default index for the case that the current
     * organization cannot have users.
     */
    public function indexAction()
    {
        $group = $this->currentUser->getGroup();
        if ($group && $this->enableScreens) {
            $browse = $group->getRespondentBrowseScreen();

            if ($browse) {
                if ($browse instanceof ProcessModelInterface) {
                    $browse->processModel($this->getModel());
                }

                // All are arrays, so easy to set
                $this->autofilterParameters = $browse->getAutofilterParameters() + $this->autofilterParameters;
                $this->indexParameters      = $browse->getStartStopParameters() + $this->indexParameters;

                $autoSnippets = $browse->getAutofilterSnippets();
                if (false !== $autoSnippets) {
                    $this->autofilterSnippets = $autoSnippets;
                }
                $startSnippets = $browse->getStartSnippets();
                if (false !== $startSnippets) {
                    $this->indexStartSnippets = $startSnippets;
                }
                $stopSnippets = $browse->getStopSnippets();
                if (false !== $stopSnippets) {
                    $this->indexStopSnippets = $stopSnippets;
                }
            }
        }
        if ($this->currentUser->hasPrivilege('pr.respondent.multiorg') ||
                $this->currentOrganization->canHaveRespondents()) {
            parent::indexAction();
        } else {
            $this->addSnippet('Organization\\ChooseOrganizationSnippet');
        }
    }

    /**
     * Initialize translate and html objects
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $request = $this->getRequest();

        if (in_array($request->getActionName(), $this->tokenReturnActions)) {
            // Tell the system where to return to after a survey has been taken
            $this->currentUser->setSurveyReturn($request);
        }
    }

    /**
     *
     * @return \Gems_Default_RespondentNewAction
     */
    protected function openedRespondent()
    {
        $orgId     = $this->_getParam(\MUtil_Model::REQUEST_ID2);
        $patientNr = $this->_getParam(\MUtil_Model::REQUEST_ID1);

        if ($patientNr && $orgId) {
            $where['gr2o_patient_nr = ?']      = $patientNr;
            $where['gr2o_id_organization = ?'] = $orgId;

            $values['gr2o_opened']             = new \MUtil_Db_Expr_CurrentTimestamp();
            $values['gr2o_opened_by']          = $this->currentUser->getUserId();

            $this->db->update('gems__respondent2org', $values, $where);
        }

        return $this;
    }

    /**
     * Action for showing overview for a patient
     */
    public function overviewAction() {
        if ($this->overviewSnippets) {
            $params = $this->_processParameters($this->overviewParameters);

            $menuList          = $this->menu->getMenuList();
            $menuList->addParameterSources($this->request, $this->menu->getParameterSource());
            $menuList->addCurrentParent($this->_('Cancel'));
            $params['buttons'] = $menuList;
            $this->addSnippets($this->overviewSnippets, $params);
        }
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction()
    {
        if ($this->enableScreens) {
            $show = false;
            $org  = $this->getRespondent()->getOrganization();
            if ($org) {
                $show = $org->getRespondentShowScreen();
            }

            if (! $show) {
                $group = $this->currentUser->getGroup();
                if ($group) {
                    $show = $group->getRespondentShowScreen();
                }
            }

            if ($show) {
                if ($show instanceof ProcessModelInterface) {
                    $show->processModel($this->getModel());
                }

                // All are arrays, so easy to set
                $this->showParameters = $show->getParameters() + $this->showParameters;
                $showSnippets = $show->getSnippets();
                if (false !== $showSnippets) {
                    $this->showSnippets = $showSnippets;
                }
            }
        }

        parent::showAction();
    }

    /**
     * Action for a simple - usually command line - import
     */
    public function simpleApiAction()
    {
        $this->disableLayout();

        $data         = $this->getRequest()->getParams();
        $importLoader = $this->loader->getImportLoader();
        $model        = $this->getModel();
        $translator   = new \Gems_Model_Translator_RespondentTranslator($this->_('Direct import'));

        $this->source->applySource($translator);
        $translator->setTargetModel($model)
                ->startImport();

        $raw    = $translator->translateRowValues($data, 1);
        $errors = array();

        // First check if we need to merge
        if (array_key_exists('oldpid', $raw)) {
            $oldPid = $raw['oldpid'];
            if (array_key_exists('gr2o_patient_nr', $raw) && array_key_exists('gr2o_id_organization', $raw)) {
                $newPid = $raw['gr2o_patient_nr'];
                $orgId  = (int) $raw['gr2o_id_organization'];
                $result = $model->merge($newPid, $oldPid, $orgId);
                switch ($result) {
                    case \Gems\Model\MergeResult::BOTH:
                        echo sprintf("%s merged to %s\n", $oldPid, $newPid);
                        break;

                    case \Gems\Model\MergeResult::FIRST:
                        echo sprintf("%s not found, nothing to merge\n", $oldPid);
                        break;

                    case \Gems\Model\MergeResult::SECOND:
                        echo sprintf("%s renamed to %s\n", $oldPid, $newPid);
                        // After a rename, we need to refetch the ids
                        $raw    = $translator->translateRowValues($data, 1);
                        break;

                    default:
                        break;
                }
            } else {
                $errors[] = 'To merge you need at least oldpid, gr2o_patient_nr and gr2o_id_organization.';
            }
        }

        $row    = $translator->validateRowValues($raw, 1);
        $errors = array_merge($errors, $translator->getRowErrors(1));

        if ($errors) {
            echo "ERRORS Occured:\n" . implode("\n", $errors);
            exit(count($errors));

        } else {
            $output  = $model->save($row);
            $changed = $model->getChanged();
            // print_r($output);

            $patientId = $output['gr2o_patient_nr'];
            if ($changed) {
                echo "Changes to patient $patientId saved.";
            }  else {
                echo "No changes to patient $patientId.";
            }

            return;
        }
    }

    /**
     * Action for showing a delete item page
     */
    public function undeleteAction()
    {
        if ($this->deleteSnippets) {
            $params = $this->_processParameters($this->deleteParameters);

            $this->addSnippets($this->deleteSnippets, $params);
        }
    }
}
