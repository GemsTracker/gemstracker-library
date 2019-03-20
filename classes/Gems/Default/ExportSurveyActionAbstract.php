<?php

/**
 *
 * @package    GemsTracker
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 44, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

/**
 *
 * @package    GemsTracker
 * @subpackage Default
 * @copyright  Expression copyright is undefined on line 56, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.2 04-Jul-2017 18:57:11
 */
class Gems_Default_ExportSurveyActionAbstract extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Object for export model source
     *
     * @var \Gems_Export_ModelSource_ExportModelSourceAbstract
     */
    private $_exportModelSource;

    /**
     *
     * @var array
     */
    private $_searchFilter;

    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialisation
     */
    protected $autofilterParameters = array(
        'exportModelSource' => 'getExportModelSource',
        'extraSort'         => 'gto_start_time ASC',
        );

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
    // protected $indexStopSnippets = array();

    /**
     * Class for export model source
     *
     * @var string
     */
    protected $exportModelSourceClass = 'AnswerExportModelSource';

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
        $basicArray = array('gto_id_survey', 'gto_id_track', 'gto_round_description', 'gto_id_organization', 'gto_start_date', 'gto_end_date', 'gto_valid_from', 'gto_valid_until');

        $model = new \Gems_Model_PlaceholderModel('nosurvey', $basicArray);

        return $model;
    }

    /**
     * Function to get a model source for this export
     *
     * @return \Gems_Export_ModelSource_ExportModelSourceAbstract
     */
    public function getExportModelSource()
    {
        if (! $this->_exportModelSource) {
            $this->_exportModelSource = $this->loader->getExportModelSource($this->exportModelSourceClass);
        }

        return $this->_exportModelSource;
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
        $this->defaultSearchData = [
            'gto_id_organization' => array_keys($this->currentUser->getRespondentOrganizations()),
            'step' => 'batch',
            'subquestions' => 'show_parent'
            ];
        return $this->defaultSearchData;
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = false)
    {
        if (null !== $this->_searchFilter) {
            return $this->_searchFilter;
        }

        $this->_searchFilter = parent::getSearchFilter($useRequest);

        $this->_searchFilter[] = 'gto_start_time IS NOT NULL';
        if (!isset($this->_searchFilter['incomplete']) || !$this->_searchFilter['incomplete']) {
            $this->_searchFilter[] = 'gto_completion_time IS NOT NULL';
        }

        if (isset($this->_searchFilter['dateused']) && $this->_searchFilter['dateused']) {
            $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($this->_searchFilter, $this->db);
            if ($where) {
                $this->_searchFilter[] = $where;
            }
        }

        $this->_searchFilter['gco_code'] = 'consent given';
        $this->_searchFilter['grc_success'] = 1;

        if (isset($this->_searchFilter['ids'])) {
            $idStrings = $this->_searchFilter['ids'];

            $idArray = preg_split('/[\s,;]+/', $idStrings, -1, PREG_SPLIT_NO_EMPTY);

            if ($idArray) {
                $this->_searchFilter['gto_id_respondent'] = $idArray;
            }
        }

        return $this->_searchFilter;
    }
}
