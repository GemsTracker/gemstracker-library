<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\RespondentModel;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Screens\ConsentInterface;
use Gems\Screens\ProcessModelInterface;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class RespondentHandler extends RespondentChildHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gr2o_opened' => SORT_DESC],
        'respondent'  => null,
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        'Respondent\\RespondentTableSnippet',
    ];

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
    protected array $changeConsentParameters = [
        'editMailable'     => true,
        'menuShowSiblings' => true,
        'menuShowChildren' => true,
        'resetRoute'       => true,
        'useTabbedForm'    => false,
    ];

    /**
     * The snippets used for the change consent action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $changeConsentSnippets = [
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
    protected array $changeOrganizationParameters = [
        'keepConsent' => false,
    ];

    /**
     * The snippets used for the change organization action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $changeOrganizationSnippets = [
        'Respondent\\ChangeRespondentOrganization'
    ];

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
    protected array $createEditParameters = [
        'menuShowSiblings' => true,
        'menuShowChildren' => true,
        'resetRoute'       => true,
        'useTabbedForm'    => false,
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippet names
     */
    protected array $createEditSnippets = [
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
    protected array $createParameters = ['respondent' => null];

    protected User $currentUser;

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected array $defaultSearchData = ['grc_success' => 1];

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
    protected array $deleteParameters = [
        'baseUrl'        => 'getItemUrlArray',
        'forOtherOrgs'   => 'getOtherOrgs',
        'onclick'        => 'getEditLink',
        // 'respondentData' => 'getRespondentData',
        'showButtons'    => false,
    ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    public array $deleteSnippets = ['Respondent\\RespondentDetailsSnippet', 'Respondent\\DeleteRespondentSnippet'];

    /**
     *
     * @var boolean En/disable group screen switching
     */
    protected bool $enableScreens = true;

    /**
     * The snippets used for the export action.
     *
     * @var mixed String or array of snippets name
     */
    public array $exportSnippets = ['Respondent\\RespondentDetailsSnippet'];

    /**
     * Array of the actions that use the model in form version.
     *
     * This determines the value of forForm().
     *
     * @var array $formActions Array of the actions that use the model with a form.
     */
    public array $formActions = ['create', 'delete', 'edit', 'import', 'simpleApi'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Respondent\\RespondentSearchSnippet'];

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
    protected array $overviewParameters = [];

    /**
     * The snippets used for the overview action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $overviewSnippets   = ['Respondent\\RespondentOverviewSnippet'];

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
    protected array $showParameters = [
        'addCurrentParent' => true,
        'baseUrl'          => 'getItemUrlArray',
        'forOtherOrgs'     => 'getOtherOrgs',
        'onclick'          => 'getEditLink',
        // 'respondentData'   => 'getRespondentData',
        '-run-once'        => 'openedRespondent',
        'tag'              => 'show-respondent',
        'vueOptions'       => [
            ':show-respondent-info' => 1,
        ],
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'Respondent\\MultiOrganizationTab',
        //'Respondent\\RespondentDetailsSnippet',
    	//'Tracker\\AddTracksSnippet',
        'Vue\\PatientVueSnippet',
    ];

    /**
     * The actions that should result in the survey return being set.
     *
     * @var array
     */
    protected array $tokenReturnActions = [
        'index',
        'show',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        RespondentRepository $respondentRepository,
        CurrentUserRepository $currentUserRepository,
        protected Model $modelLoader,
        protected OrganizationRepository $organizationRepository,
        protected ResultFetcher $resultFetcher,
        protected TrackDataRepository $trackDataRepository,
    ) {
        parent::__construct($responder, $translate, $respondentRepository, $currentUserRepository);
    }

    /**
     * The automatically filtered result
     *
     * @param $resetMvc bool When true only the filtered resulsts
     */
    public function autofilterAction(bool $resetMvc = true): void
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
    public function changeConsentAction(): void
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
    public function changeOrganizationAction(): void
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
    public function createAction(): void
    {
        if ($this->enableScreens) {
            $edit = false;
            $org  = $this->getRespondent()->getOrganization();
            if (! $org) {
                $org = $this->currentUser->getCurrentOrganization();
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
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(bool $detailed, string $action): RespondentModel
    {
        $model = $this->modelLoader->createRespondentModel();

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
     * Action for showing a delete-item page
     */
    public function deleteAction(): void
    {
        $this->deleteParameters['formTitle'] = $this->_('Delete or stop respondent');

        parent::deleteAction();
    }

    /**
     * Action for showing a edit item page with extra title
     */
    public function editAction(): void
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
        $params = $this->_processParameters(['addCurrentParent' => false] + $this->showParameters);

        $this->addSnippets($this->exportSnippets, $params);

        $this->html->h2($this->_('Export respondent archive'));

        //Now show the export form
        $export = $this->loader->getRespondentExport();
        $form   = $export->getForm();
        $div    = $this->html->div(['id' => 'mainform']);
        $div[]  = $form;

        $params = $this->request->getQueryParams() + $this->request->getParsedBody();

        $form->populate($params);

        if ($this->requestInfo->isPost()) {
            $respondent = $this->getRespondent();
            $patients   = [
                [
                    'gr2o_id_organization' => $respondent->getOrganizationId(),
                    'gr2o_patient_nr'      => $respondent->getPatientNumber()
                ]
            ];

            $group = null;
            if (isset($params['group'])) {
                $group = $params['group'];
            }
            $format = null;
            if (isset($params['format'])) {
                $format = $params['format'];
            }

            $export->render($patients, $group, $format);
        }
    }

    /**
     * Helper function to get the title for the create action.
     *
     * @return string
     */
    public function getCreateTitle(): string
    {
        return $this->_('New respondent...');
    }

    /**
     * Get the link to edit respondent
     *
     * @return \MUtil\Html\HrefArrayAttribute
     */
    public function getEditLink()
    {
        /*$item = $this->menu->find(array(
            'controller' => $this->requestHelper->getControllerName(),
            $request->getActionKey() => 'edit',
            'allowed' => true));

        if ($item) {
            return $item->toHRefAttribute($request);
        }*/
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return string
     */
    public function getEditTitle(): string
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
    public function getIndexTitle(): string
    {
        return $this->_('Respondents');
    }

    /**
     * Return the array with items that should be used to find this item
     *
     * @return array
     */
    public function getItemUrlArray(): array
    {
        $queryParams = $this->request->getQueryParams();
        return [
            \MUtil\Model::REQUEST_ID1 => isset($queryParams[\MUtil\Model::REQUEST_ID1]) ? $queryParams[\MUtil\Model::REQUEST_ID1] : null,
            \MUtil\Model::REQUEST_ID2 => isset($queryParams[\MUtil\Model::REQUEST_ID2]) ? $queryParams[\MUtil\Model::REQUEST_ID2] : null,
        ];
    }

    /**
     * The organizations whose tokens are shown.
     *
     * When true: show tokens for all organizations, false: only current organization, array => those organizations
     * @return boolean|array
     */
    public function getOtherOrgs()
    {
        return $this->organizationRepository->getAllowedOrganizationsFor($this->getRespondent()->getOrganizationId());
    }

    /**
     * Retrieve the respondent data in advance
     * (So we don't need to repeat that for every snippet.)
     *
     * @return array
     */
    public function getRespondentData(): array
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
        if (in_array($this->requestInfo->getCurrentAction(), $this->summarizedActions)) {
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
    public function getSearchData(bool $useRequest = true): array
    {
        $data = parent::getSearchData($useRequest);

        if (isset($data[\MUtil\Model::REQUEST_ID2])) {
            $organizationIds = [intval($data[\MUtil\Model::REQUEST_ID2])];
        } else {
            $organizationIds = $this->currentUser->getRespondentOrgFilter();
        }

        $activeTracks = $this->trackDataRepository->getActiveTracksForOrgs($organizationIds);

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
    public function getSearchDefaults(): array
    {
        if (! isset($this->defaultSearchData[\MUtil\Model::REQUEST_ID2])) {
            $currentOrganization = $this->currentUser->getCurrentOrganization();
            if ($this->currentUser->hasPrivilege('pr.respondent.multiorg') &&
                    (! $currentOrganization->canHaveRespondents())) {
                $this->defaultSearchData[\MUtil\Model::REQUEST_ID2] = '';
            } else {
                $this->defaultSearchData[\MUtil\Model::REQUEST_ID2] = $currentOrganization->getId();
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
    public function getSearchFilter(bool $useRequest = true): array
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
                                ['gr2o_id_user' => 'gr2t_id_user', 'gr2o_id_organization' => 'gr2t_id_organization']
                                );
                    }
                    if (! $model->hasAlias('gems__tracks')) {
                        $model->addTable('gems__tracks', ['gr2t_id_track' => 'gtr_id_track']);
                    }
                    if (! isset($filter['__active_tracks'], $filter['__active_tracks'][$filter['gr2t_id_track']])) {
                        unset($filter['gr2t_id_track']);
                    }

            }
        }
        
        if (isset($filter['locations'])) {
            $filter[] = sprintf(
                "(gr2o_id_user, gr2o_id_organization) IN (
                    SELECT gr2t_id_user, gr2t_id_organization FROM gems__respondent2track INNER JOIN gems__respondent2track2field ON gr2t_id_respondent_track = gr2t2f_id_respondent_track 
                    WHERE gr2t2f_value = %s AND gr2t2f_id_field IN (SELECT gtf_id_field FROM gems__track_fields WHERE gtf_field_type = 'location'))",
                $this->resultFetcher->getPlatform()->quoteValue($filter['locations'])
            );
            unset($filter['locations']);
        }
        // dd($filter);
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
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('respondent', 'respondents', $count);
    }

    /**
     * Overrule default index for the case that the current
     * organization cannot have users.
     */
    public function indexAction(): void
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
                $this->currentUser->getCurrentOrganization()->canHaveRespondents()) {
            parent::indexAction();
        } else {
            $this->addSnippet('Organization\\ChooseOrganizationSnippet');
        }
    }

    /**
     *
     * @return self
     */
    protected function openedRespondent(): self
    {
        $queryParams = $this->request->getQueryParams();
        $orgId = null;
        if (isset($queryParams[\MUtil\Model::REQUEST_ID2])) {
            $orgId = $queryParams[\MUtil\Model::REQUEST_ID2];
        }
        $patientNr = null;
        if (isset($queryParams[\MUtil\Model::REQUEST_ID1])) {
            $patientNr = $queryParams[\MUtil\Model::REQUEST_ID1];
        }

        if ($patientNr && $orgId) {
            $this->respondentRepository->setOpened($patientNr, $orgId, $this->currentUserId);
        }

        return $this;
    }

    /**
     * Action for showing overview for a patient
     */
    public function overviewAction() {
        if ($this->overviewSnippets) {
            $params = $this->_processParameters($this->overviewParameters);

            /*$menuList          = $this->menu->getMenuList();
            $menuList->addParameterSources($this->request, $this->menu->getParameterSource());
            $menuList->addCurrentParent($this->_('Cancel'));
            $params['buttons'] = $menuList;*/
            $this->addSnippets($this->overviewSnippets, $params);
        }
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction(): void
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
    public function simpleApiAction(): void
    {
        /*$data         = $this->getRequest()->getParams();
        $importLoader = $this->loader->getImportLoader();
        $model        = $this->getModel();
        $translator   = new \Gems\Model\Translator\RespondentTranslator($this->_('Direct import'));

        $this->source->applySource($translator);
        $translator->setTargetModel($model)
                ->startImport();

        $raw    = $translator->translateRowValues($data, 1);
        $errors = [];

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
        }*/
    }

    /**
     * Action for showing a delete-item page
     */
    public function undeleteAction(): void
    {
        if ($this->deleteSnippets) {
            $params = $this->_processParameters($this->deleteParameters);

            $this->addSnippets($this->deleteSnippets, $params);
        }
    }
}
