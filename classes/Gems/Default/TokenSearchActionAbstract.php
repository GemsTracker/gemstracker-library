<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 22-apr-2015 17:53:02
 */
abstract class Gems_Default_TokenSearchActionAbstract extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'multiTracks'  => 'isMultiTracks',
        'surveyReturn' => 'setSurveyReturn',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Token\\PlanTokenSnippet';

    /**
     * En/disable the checking for answers on load.
     *
     * @var boolean
     */
    protected $checkForAnswersOnLoad = true;

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
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Token\\PlanSearchSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Tracker_TokenStatusLegenda');

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

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
        // \MUtil_Model::$verbose = true;
        $model = $this->loader->getTracker()->getTokenModel();
        $model->setCreate(false);

        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        return $model;
    }

    /**
     * Bulk email action
     */
    public function emailAction()
    {
        $model   = $this->getModel();

        $model->setFilter($this->getSearchFilter(false));

        $sort = array(
            'grs_email'          => SORT_ASC,
            'grs_first_name'     => SORT_ASC,
            'grs_surname_prefix' => SORT_ASC,
            'grs_last_name'      => SORT_ASC,
            'gto_valid_from'     => SORT_ASC,
            'gto_round_order'    => SORT_ASC,
            'gsu_survey_name'    => SORT_ASC,
        );

        if ($tokensData = $model->load(true, $sort)) {
            $params['mailTarget']           = 'token';
            $params['menu']                 = $this->menu;
            $params['model']                = $model;
            $params['identifier']           = $this->_getIdParam();
            $params['view']                 = $this->view;
            $params['routeAction']          = 'index';
            $params['formTitle']            = sprintf($this->_('Send mail to: %s'), $this->getTopic());
            $params['templateOnly']         = ! $this->currentUser->hasPrivilege('pr.token.mail.freetext');
            $params['multipleTokenData']    = $tokensData;

            $this->addSnippet('Mail_TokenBulkMailFormSnippet', $params);
        } else {
            $this->addMessage($this->_('No tokens found.'));
        }
    }

    /**
     * Is multi tracks enabled in this project
     *
     * @return boolean
     */
    public function getMultiTracks()
    {
        return $this->escort instanceof \Gems_Project_Tracks_MultiTracksInterface;
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
        if (! $this->defaultSearchData) {
            $inFormat = \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
            $now      = new \MUtil_Date();
            $today    = $now->toString($inFormat);

            $this->defaultSearchData = array(
                'datefrom'    => $today,
                'dateused'    => '_gto_valid_from gto_valid_until',
                'dateuntil'   => $today,
                'main_filter' => '',
            );
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        unset($filter['AUTO_SEARCH_TEXT_BUTTON']);

        $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db, null, 'yyyy-MM-dd HH:mm:ss');
        if ($where) {
            $filter[] = $where;
        }

        if (! isset($filter['gto_id_organization'])) {
            $filter['gto_id_organization'] = $this->currentUser->getRespondentOrgFilter();
        }
        $filter['gsu_active']  = 1;

        if (isset($filter['filter_status']) && $filter['filter_status']) {
            // $filter[] = $this->util->getTokenData()->getStatusExpressionFor($filter['filter_status']);

            // unset($filter['filter_status']);
        } else {
            $filter['grc_success'] = 1; // Delete unless overruled by filter status
        }

        if (isset($filter['main_filter'])) {
            switch ($filter['main_filter']) {
                case 'hasnomail':
                    $filter[] = sprintf(
                            "(grs_email IS NULL OR grs_email = '' OR grs_email NOT RLIKE '%s') AND
                                ggp_respondent_members = 1",
                            str_replace('\'', '\\\'', trim(\MUtil_Validate_SimpleEmail::EMAIL_REGEX, '/'))
                            );
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'notmailed':
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'tomail':
                    $filter[] = sprintf(
                            "grs_email IS NOT NULL AND
                                grs_email != '' AND
                                grs_email RLIKE '%s' AND
                                ggp_respondent_members = 1",
                            str_replace('\'', '\\\'', trim(\MUtil_Validate_SimpleEmail::EMAIL_REGEX, '/'))
                            );
                    //$filter[] = "grs_email IS NOT NULL AND grs_email != '' AND ggp_respondent_members = 1";
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'toremind':
                    // $filter['can_email'] = 1;
                    $filter[] = 'gto_mail_sent_date < CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                default:
                    break;
            }
            unset($filter['main_filter']);
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
        return $this->plural('token', 'tokens', $count);
    }

    /**
     * Default overview action
     */
    public function indexAction()
    {
        if ($this->checkForAnswersOnLoad) {
            $this->loader->getTracker()->processCompletedTokens(
                    null,
                    $this->currentUser->getUserId(),
                    $this->currentUser->getCurrentOrganizationId(),
                    true
                    );
        }

        parent::indexAction();
    }

    /**
     *
     * @return boolean
     */
    protected function isMultiTracks()
    {
        return ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
    }

    /**
     * Make we return to this screen after completion
     *
     * @return void
     */
    public function setSurveyReturn()
    {
        $this->currentUser->setSurveyReturn($this->getRequest());
    }
}
