<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Show the action log
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_LogAction extends \Gems_Controller_ModelSnippetActionAbstract
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
        'columns'   => 'getBrowseColumns',
        'extraSort' => array(
            'glua_created' => SORT_DESC,
            ),
        );

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'Log\\LogSearchSnippet');

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        $html = \MUtil_Html::create();
        $br   = $html->br();

        $columns[10] = array('glua_created', $br, 'glac_name');
        // $columns[15] = array('glua_message');
        $columns[20] = array('staff_name', $br, 'glua_organization');
        $columns[30] = array('respondent_name');

        return $columns;
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
        //\MUtil_Model::$verbose=true;
        $model = new \Gems_Model_JoinModel('Log', 'gems__log_useractions');
        $model->addLeftTable('gems__log_actions', array('glua_action'=>'glac_id_action'));
        $model->addLeftTable('gems__respondents', array('glua_to'=>'grs_id_user'));
        $model->addLeftTable('gems__staff', array('glua_by'=>'gsf_id_user'));
        $model->addColumn("CONCAT(gsf_last_name, ', ', COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, ''))", 'staff_name');
        $model->addColumn("CONCAT(grs_last_name, ', ', COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, ''))", 'respondent_name');
        $model->resetOrder();
        $model->set('glua_created', 'label', $this->_('Date'));
        $model->set('glac_name', 'label', $this->_('Action'));
        $model->set('glua_message', 'label', $this->_('Message'));
        $model->set('staff_name', 'label', $this->_('Staff'));

        //Not only active, we want to be able to read the log for inactive organizations too
        $orgs = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations');
        $model->set('glua_organization', 'label', $this->_('Organization'), 'multiOptions', $orgs);
        $model->set('respondent_name', 'label', $this->_('Respondent'));

        if ($detailed) {
            $model->set('glua_role', 'label', $this->_('Role'));
            $model->set('glua_remote_ip', 'label', $this->_('IP address'));
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Logging');
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
            $from = new \MUtil_Date();
            $from->subWeek(2);

            $this->defaultSearchData = array(
                'glua_organization' => $this->loader->getOrganization()->getId(),
                'datefrom'          => $from,
                'dateuntil'         => new \MUtil_Date(),
                );
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @return array or false
     */
    public function getSearchFilter()
    {
        $filter = parent::getSearchFilter();

        if (isset($filter[\Gems_Snippets_AutosearchFormSnippet::PERIOD_DATE_USED])) {
            $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db);

            if ($where) {
                $filter[] = $where;
            }

            unset($filter[\Gems_Snippets_AutosearchFormSnippet::PERIOD_DATE_USED], $filter['datefrom'], $filter['dateuntil']);
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
        return $this->plural('log', 'log', $count);
    }
}