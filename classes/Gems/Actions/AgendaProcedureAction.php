<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

use Gems\Util\Translated;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AgendaProcedureAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gapr_name' => SORT_ASC],
        'searchFields' => 'getSearchFields',
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = ['procedure', 'procedures'];
    
    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Agenda\\AutosearchFormSnippet'];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showParameters = [
        'calSearchFilter' => 'getShowFilter',
        'caption'         => 'getShowCaption',
        'onEmpty'         => 'getShowOnEmpty',
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Agenda\\CalendarTableSnippet',
    ];

    /**
     * @var Translated
     */
    public $translatedUtil;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * Cleanup appointments
     */
    public function cleanupAction()
    {
        $params = $this->_processParameters($this->showParameters);
        $params['contentTitle'] = $this->_('Clean up existing appointments?');
        $params['filterOn']     = 'gap_id_procedure';
        $params['filterWhen']   = 'gap_filter';

        $snippets = array(
            'Generic\\ContentTitleSnippet',
            'Agenda\\AppointmentCleanupSnippet',
            'Agenda\\CalendarTableSnippet',
            );

        $this->addSnippets($snippets, $params);
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
    protected function createModel($detailed, $action)
    {
        $model      = new \MUtil\Model\TableModel('gems__agenda_procedures');

        \Gems\Model::setChangeFieldsByPrefix($model, 'gapr');

        $model->setDeleteValues('gapr_active', 0);

        $model->set('gapr_name',                    'label', $this->_('Procedure'),
                'description', $this->_('A procedure describes an appointments effects on a respondent:
e.g. an excercise, an explanantion, a massage, mindfullness, a (specific) operation, etc...'),
                'required', true
                );

        $model->setIfExists('gapr_id_organization', 'label', $this->_('Organization'),
                'description', $this->_('Optional, an import match with an organization has priority over those without.'),
                'multiOptions', $this->translatedUtil->getEmptyDropdownArray() + $this->util->getDbLookup()->getOrganizations()
                );

        $model->setIfExists('gapr_name_for_resp',   'label', $this->_('Respondent explanation'),
                'description', $this->_('Alternative description to use with respondents.')
                );
        $model->setIfExists('gapr_match_to',        'label', $this->_('Import matches'),
                'description', $this->_("Split multiple import matches using '|'.")
                );

        $model->setIfExists('gapr_code',        'label', $this->_('Procedure code'),
                'size', 10,
                'description', $this->_('Optional code name to link the procedure to program code.'));

        $model->setIfExists('gapr_active',      'label', $this->_('Active'),
                'description', $this->_('Inactive means assignable only through automatich processes.'),
                'elementClass', 'Checkbox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );
        $model->setIfExists('gapr_filter',      'label', $this->_('Filter'),
                'description', $this->_('When checked appointments with these procedures are not imported.'),
                'elementClass', 'Checkbox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );

        $model->addColumn("CASE WHEN gapr_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Agenda procedures');
    }
    
    /**
     * Returns the fields for autosearch with 
     * 
     * @return array
     */
    public function getSearchFields()
    {
        return [
            'gapr_filter' => $this->_('(all filters)')
        ];
    }

    /**
     *
     * @return string
     */
    public function getShowCaption()
    {
        return $this->_('Example appointments');
    }

    /**
     *
     * @return string
     */
    public function getShowOnEmpty()
    {
        return $this->_('No example appointments found');

    }
    /**
     * Get an agenda filter for the current shown item
     *
     * @return array
     */
    public function getShowFilter()
    {
        return array(
            \MUtil\Model::SORT_DESC_PARAM => 'gap_admission_time',
            'gap_id_procedure' => $this->_getIdParam(),
            'limit' => 10,
            );
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('procedure', 'procedures', $count);
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        parent::indexAction();

        $this->html->pInfo($this->getModel()->get('gapr_name', 'description'));
    }
}
