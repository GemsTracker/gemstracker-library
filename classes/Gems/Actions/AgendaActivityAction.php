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
class AgendaActivityAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = [
        'columns'      => 'getBrowseColumns',
        'extraSort'    => ['gaa_name' => SORT_ASC],
        'searchFields' => 'getSearchFields',
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = ['activity', 'activities'];
    
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
        $params['filterOn']     = 'gap_id_activity';
        $params['filterWhen']   = 'gaa_filter';

        $snippets = [
            'Generic\\ContentTitleSnippet',
            'Agenda\\AppointmentCleanupSnippet',
            'Agenda\\CalendarTableSnippet',
        ];

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
        $model      = new \MUtil\Model\TableModel('gems__agenda_activities');

        \Gems\Model::setChangeFieldsByPrefix($model, 'gaa');

        $model->setDeleteValues('gaa_active', 0);

        $model->set('gaa_name',                    'label', $this->_('Activity'),
                'description', $this->_('An activity is a high level description about an appointment:
e.g. consult, check-up, diet, operation, physiotherapy or other.'),
                'required', true
                );

        $model->setIfExists('gaa_id_organization', 'label', $this->_('Organization'),
                'description', $this->_('Optional, an import match with an organization has priority over those without.'),
                'multiOptions', $this->translatedUtil->getEmptyDropdownArray() + $this->util->getDbLookup()->getOrganizations()
                );

        $model->setIfExists('gaa_name_for_resp',   'label', $this->_('Respondent explanation'),
                'description', $this->_('Alternative description to use with respondents.')
                );
        $model->setIfExists('gaa_match_to',        'label', $this->_('Import matches'),
                'description', $this->_("Split multiple import matches using '|'.")
                );

        $model->setIfExists('gaa_code',        'label', $this->_('Activity code'),
                'size', 10,
                'description', $this->_('Optional code name to link the activity to program code.'));

        $model->setIfExists('gaa_active',      'label', $this->_('Active'),
                'description', $this->_('Inactive means assignable only through automatich processes.'),
                'elementClass', 'Checkbox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );
        $model->setIfExists('gaa_filter',      'label', $this->_('Filter'),
                'description', $this->_('When checked appointments with these activities are not imported.'),
                'elementClass', 'Checkbox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );

        $model->addColumn("CASE WHEN gaa_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Agenda activities');
    }
    
    /**
     * Returns the fields for autosearch with 
     * 
     * @return array
     */
    public function getSearchFields()
    {
        return [
            'gaa_filter' => $this->_('(all filters)')
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
        return [
            \MUtil\Model::SORT_DESC_PARAM => 'gap_admission_time',
            'gap_id_activity' => $this->_getIdParam(),
            'limit' => 10,
        ];
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('activity', 'activities', $count);
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        parent::indexAction();
        
        $this->html->pInfo($this->getModel()->get('gaa_name', 'description'));
    }
}
