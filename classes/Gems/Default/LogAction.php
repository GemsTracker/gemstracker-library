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
class Gems_Default_LogAction extends Gems_Controller_BrowseEditAction {

    public $sortKey = array('glua_created' => SORT_DESC);

    public $defaultPeriodEnd   = 1;
    public $defaultPeriodStart = -4;
    public $defaultPeriodType  = 'W';

    public $maxPeriod = 1;
    public $minPeriod = -15;

    public function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('edit', 'show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        $html = MUtil_Html::create();
        $br   = $html->br();

        $bridge->addSortable('glua_created');
        $bridge->addSortable('glac_name');
        $bridge->addSortable('glua_message');
        $bridge->addMultiSort('staff_name', $br, 'glua_organization');
        $bridge->addSortable('respondent_name');
    }

    public function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data) {
        $elements = parent::getAutoSearchElements($model, $data);

        if ($elements) {
            $elements[] = null; // break into separate spans
        }

        // Create date range elements
        $min  = -91;
        $max  = 91;
        $size = max(strlen($min), strlen($max));

        $element = new Zend_Form_Element_Text('period_start', array('label' => $this->_('from'), 'size' => $size - 1, 'maxlength' => $size, 'class' => 'rightAlign'));
        $element->addValidator(new Zend_Validate_Int());
        $element->addValidator(new Zend_Validate_Between($min, $max));
        $elements[] = $element;

        $element = new Zend_Form_Element_Text('period_end', array('label' => $this->_('until'), 'size' => $size - 1, 'maxlength' => $size, 'class' => 'rightAlign'));
        $element->addValidator(new Zend_Validate_Int());
        $element->addValidator(new Zend_Validate_Between($min, $max));
        $elements[] = $element;

        $options = array(
            'D' => $this->_('days'),
            'W' => $this->_('weeks'),
            'M' => $this->_('months'),
            'Y' => $this->_('years'),
            );
        $element = $this->_createSelectElement('date_type', $options);
        $element->class = 'minimal';
        $elements[] = $element;

        $joptions['change'] = new Zend_Json_Expr('function(e, ui) {
jQuery("#period_start").attr("value", ui.values[0]);
jQuery("#period_end"  ).attr("value", ui.values[1]).trigger("keyup");

}');
        $joptions['min']    = $this->minPeriod;
        $joptions['max']    = $this->maxPeriod;
        $joptions['range']  = true;
        $joptions['values'] = new Zend_Json_Expr('[jQuery("#period_start").attr("value"), jQuery("#period_end").attr("value")]');

        $element = new ZendX_JQuery_Form_Element_Slider('period', array('class' => 'periodSlider', 'jQueryParams' => $joptions));
        $elements[] = $element;

        $elements[] = null; // break into separate spans

        $elements[] = $this->_('Organization:');
        $sql = 'SELECT gor_id_organization, gor_name FROM gems__organizations';
        $elements[] = $this->_createSelectElement('glua_organization', $sql, $this->_('All organizations'));

        $elements[] = $this->_('Staff:');
        $sql = "SELECT glua_by, CONCAT(gsf_last_name, ', ', COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, '')) AS name "
             . "FROM gems__log_useractions "
             . "JOIN gems__staff ON glua_by = gsf_id_user";
        /*if (isset($data['glua_organization']) && !empty($data['glua_organization'])) {
            $sql .= ' WHERE glua_organization = ' . $this->db->quote($data['glua_organization']);
        }*/
        $elements[] = $this->_createSelectElement('glua_by', $sql, $this->_('All staff'));

        $elements[] = MUtil_Html::create('br');
        $elements[] = $this->_('Patient:');
        $sql = "SELECT glua_to, CONCAT(grs_last_name, ', ', COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')) AS name "
             . "FROM gems__log_useractions "
             . "JOIN gems__respondents ON glua_to = grs_id_user";
        /*if (isset($data['glua_organization']) && !empty($data['glua_organization'])) {
            $sql .= ' WHERE glua_organization = ' . $this->db->quote($data['glua_organization']);
        }*/
        $elements[] = $this->_createSelectElement('glua_to', $sql, $this->_('All patients'));

        $elements[] = $this->_('Action:');
        $sql = "SELECT glac_id_action, glac_name "
             . "FROM gems__log_actions ";
        $elements[] = $this->_createSelectElement('glua_action', $sql, $this->_('All actions'));

        return $elements;
    }

    public function getDataFilter(array $data) {
        $filter = array();


        // Check for period selected
        switch ($data['date_type']) {
            case 'W':
                $period_unit  = 'WEEK';
                break;
            case 'M':
                $period_unit  = 'MONTH';
                break;
            case 'Y':
                $period_unit  = 'YEAR';
                break;
            default:
                $period_unit  = 'DAY';
                break;
        }

        $date_field  = $this->db->quoteIdentifier('glua_created');
        $date_filter = "DATE_ADD(CURRENT_DATE, INTERVAL ? " . $period_unit . ")";
        $filter[] = $this->db->quoteInto($date_field . ' >= '.  $date_filter, intval($data['period_start']));
        $filter[] = $this->db->quoteInto($date_field . ' <= '.  $date_filter, intval($data['period_end']));

        return $filter;
    }

    public function getDefaultSearchData()
    {
        return array(
            'date_type'           => $this->defaultPeriodType,
            'period_start'        => $this->defaultPeriodStart,
            'period_end'          => $this->defaultPeriodEnd
        );
    }

    protected function createModel($detailed, $action) {
        //MUtil_Model::$verbose=true;
        $model = new Gems_Model_JoinModel('Log', 'gems__log_useractions');
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

    public function getTopic($count = 1) {
        return $this->_('Log');
    }

    public function getTopicTitle() {
        return $this->_('Logging');
    }

}