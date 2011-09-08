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
 * File description of TokenDateSelector
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.1
 * @version 1.1
 * @package Gems
 * @subpackage Selector
 */

/**
 * Class description of TokenDateSelector
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @package Gems
 * @subpackage Selector
 */
class Gems_Selector_TokenByGroupDateSelector extends Gems_Selector_DateSelectorAbstract
{
    /**
     * The name of the database table to use as the main table.
     *
     * @var string
     */
    protected $dataTableName = 'gems__tokens';

    /**
     * The name of the field where the date is calculated from
     *
     * @var string
     */
    protected $dateFrom = 'gto_valid_from';

    /**
     *
     * @param string $name
     * @return Gems_Selector_SelectorField
     */
    public function addSubField($name)
    {
        $field = $this->addField($name);
        $field->setClass('smallTime');
        $field->setLabelClass('indentLeft smallTime');

        return $field;
    }

    /**
     * Tells the models which fields to expect.
     */
    protected function loadFields()
    {
        $forResp  = $this->_('for respondents');
        $forStaff = $this->_('for staff');

        $this->addField('tokens')
                ->setLabel($this->_('Tokens'))
                ->setToCount("gto_id_token");
        $this->addSubField('rtokens')
                ->setLabel($forResp)
                ->setToSum("ggp_respondent_members")
                ->setFilter("ggp_respondent_members = 1");
        $this->addSubField('stokens')
                ->setLabel($forStaff)
                ->setToSum("ggp_staff_members")
                ->setFilter("ggp_staff_members = 1");

        $this->addField('todo')
                ->setLabel($this->_('Todo'))
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)");
        $this->addSubField('rtodo')
                ->setLabel($forResp)
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)", 'ggp_respondent_members');
        $this->addSubField('stodo')
                ->setLabel($forStaff)
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)", 'ggp_staff_members');

        $this->addField('going')
                ->setLabel($this->_('Partially completed'))
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1");
        $this->addSubField('rgoing')
                ->setLabel($forResp)
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1", 'ggp_respondent_members');
        $this->addSubField('sgoing')
                ->setLabel($forStaff)
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1", 'ggp_staff_members');

        $this->addField('adleft')
                ->setLabel($this->_('Time left in days - average'))
                ->setToAverage("CASE WHEN gto_valid_until IS NOT NULL AND gto_valid_until >= CURRENT_TIMESTAMP THEN DATEDIFF(gto_valid_until, CURRENT_TIMESTAMP) ELSE NULL END", 2);
        $this->addSubField('midleft')
                ->setLabel($this->_('least time left'))
                ->setToMinimum("CASE WHEN gto_valid_until IS NOT NULL AND gto_valid_until >= CURRENT_TIMESTAMP THEN DATEDIFF(gto_valid_until, CURRENT_TIMESTAMP) ELSE NULL END", 2);
        $this->addSubField('madleft')
                ->setLabel($this->_('most time left'))
                ->setToMaximum("CASE WHEN gto_valid_until IS NOT NULL AND gto_valid_until >= CURRENT_TIMESTAMP THEN DATEDIFF(gto_valid_until, CURRENT_TIMESTAMP) ELSE NULL END", 2);

        $this->addField('missed')
                ->setLabel($this->_('Missed'))
                ->setToSumWhen("gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP");
        $this->addSubField('rmissed')
                ->setLabel($forResp)
                ->setToSumWhen("gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP", 'ggp_respondent_members');
        $this->addSubField('smissed')
                ->setLabel($forStaff)
                ->setToSumWhen("gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP", 'ggp_staff_members');

        $this->addField('done')
                ->setLabel($this->_('Answered'))
                ->setToSumWhen("gto_completion_time IS NOT NULL");
        $this->addSubField('rdone')
                ->setLabel($forResp)
                ->setToSumWhen("gto_completion_time IS NOT NULL", 'ggp_respondent_members');
        $this->addSubField('sdone')
                ->setLabel($forStaff)
                ->setToSumWhen("gto_completion_time IS NOT NULL", 'ggp_staff_members');

        $this->addField('adur')
                ->setLabel($this->_('Answer time in days - average'))
                ->setToAverage("gto_completion_time - gto_valid_from", 2);
        $this->addSubField('midur')
                ->setLabel($this->_('fastest answer'))
                ->setToMinimum("gto_completion_time - gto_valid_from", 2);
        $this->addSubField('madur')
                ->setLabel($this->_('slowest answer'))
                ->setToMaximum("gto_completion_time - gto_valid_from", 2);
    }

    /**
     * Stud function to allow extension of standard one table select.
     *
     * @param Zend_Db_Select $select
     */
    protected function processSelect(Zend_Db_Select $select)
    {
        $select->join('gems__surveys', 'gto_id_survey = gsu_id_survey');
        $select->join('gems__groups', 'gsu_id_primary_group = ggp_id_group');
    }

    protected function setTableBody(MUtil_Model_TableBridge $bridge, MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        $bridge->setAlternateRowClass('even', 'odd', 'odd');

        parent::setTableBody($bridge, $repeater, $columnClass);
    }
}
