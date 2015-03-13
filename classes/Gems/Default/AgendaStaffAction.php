<?php

/**
 * Copyright (c) 2013, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('staff');

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
        'Generic_ContentTitleSnippet',
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
        $params['contentTitle'] = $this->_('Cleanup existing appointments?');
        $params['filterOn']     = array('gap_id_attended_by', 'gap_id_referred_by');
        $params['filterWhen']   = 'gas_filter';

        $snippets = array(
            'Generic_ContentTitleSnippet',
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
                'description', $this->_('When true appointments with this staff member are not imported.'),
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
        return $this->_('Agenda healtcare provider');
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
