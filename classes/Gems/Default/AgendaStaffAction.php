<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_AgendaStaffAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraSort'   => array('gas_name' => SORT_ASC),
        'searchFields' => 'getSearchFields',
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('staff');
    
    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Agenda\\AutosearchFormSnippet');

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showParameters = array(
        'calSearchFilter' => 'getShowFilter',
        'caption'         => 'getShowCaption',
        'onEmpty'         => 'getShowOnEmpty',
        );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Agenda_CalendarTableSnippet',
        );

    /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     * Cleanup appointments
     */
    public function cleanupAction()
    {
        $params = $this->_processParameters($this->showParameters);
        $params['contentTitle'] = $this->_('Clean up existing appointments?');
        $params['filterOn']     = array('gap_id_attended_by', 'gap_id_referred_by');
        $params['filterWhen']   = 'gas_filter';

        $snippets = array(
            'Generic\\ContentTitleSnippet',
            'Agenda\\AppointmentCleanupSnippet',
            'Agenda_CalendarTableSnippet',
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
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $dblookup   = $this->util->getDbLookup();
        $translated = $this->util->getTranslated();
        $model      = new \MUtil_Model_TableModel('gems__agenda_staff');

        \Gems_Model::setChangeFieldsByPrefix($model, 'gas');

        $model->setDeleteValues('gas_active', 0);

        $model->set('gas_name',                    'label', $this->_('Name'),
                'required', true
                );
        $model->set('gas_function',                'label', $this->_('Function'));


        $model->setIfExists('gas_id_organization', 'label', $this->_('Organization'),
                'multiOptions', $dblookup->getOrganizations(),
                'required', true
                );

        $model->setIfExists('gas_id_user',         'label', $this->_('GemsTracker user'),
                'description', $this->_('Optional: link this health care provider to a GemsTracker Staff user.'),
                'multiOptions', $translated->getEmptyDropdownArray() + $dblookup->getStaff()
                );
        $model->setIfExists('gas_match_to',        'label', $this->_('Import matches'),
                'description', $this->_("Split multiple import matches using '|'.")
                );

        $model->setIfExists('gas_active',      'label', $this->_('Active'),
                'description', $this->_('Inactive means assignable only through automatich processes.'),
                'elementClass', 'Checkbox',
                'multiOptions', $translated->getYesNo()
                );
        $model->setIfExists('gas_filter',      'label', $this->_('Filter'),
                'description', $this->_('When checked appointments with this staff member are not imported.'),
                'elementClass', 'Checkbox',
                'multiOptions', $translated->getYesNo()
                );

        $model->addColumn("CASE WHEN gas_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Agenda healthcare provider');
    }
    
    /**
     * Returns the fields for autosearch with 
     * 
     * @return array
     */
    public function getSearchFields()
    {
        return [
            'gas_filter' => $this->_('(all filters)')
        ];
    }

    /**
     *
     * @return type
     */
    public function getShowCaption()
    {
        return $this->_('Example appointments');
    }

    /**
     *
     * @return type
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
        $id = intval($this->_getIdParam());
        return array(
            \MUtil_Model::SORT_DESC_PARAM => 'gap_admission_time',
            "gap_id_referred_by = $id OR gap_id_attended_by = $id",
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
        return $this->plural('healthcare staff', 'healthcare staff', $count);
    }
}
