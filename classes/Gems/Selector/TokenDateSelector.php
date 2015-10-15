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
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Selector_TokenDateSelector extends \Gems_Selector_DateSelectorAbstract
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
     * @return \Gems_Selector_SelectorField
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
                ->setLabel($this->_('Activated surveys'))
                ->setToCount("gto_id_token");

        $this->addField('todo')
                ->setLabel($this->_('Unanswered surveys'))
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 0");

        $this->addField('going')
                ->setLabel($this->_('Partially completed'))
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1");
        /*
        $this->addField('adleft')
                ->setLabel($this->_('Time left in days - average'))
                ->setToAverage("CASE WHEN gto_valid_until IS NOT NULL AND gto_valid_until >= CURRENT_TIMESTAMP THEN DATEDIFF(gto_valid_until, CURRENT_TIMESTAMP) ELSE NULL END", 2);
        // */

        $this->addField('missed')
                ->setLabel($this->_('Expired surveys'))
                ->setToSumWhen("gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP");

        $this->addField('done')
                ->setLabel($this->_('Answered surveys'))
                ->setToSumWhen("gto_completion_time IS NOT NULL");
        /*
        $this->addField('adur')
                ->setLabel($this->_('Answer time in days - average'))
                ->setToAverage("gto_completion_time - gto_valid_from", 2);
        // */
    }

    /**
     * Processing of filter, can be overriden.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param array $filter
     * @return array
     */
    protected function processFilter(\Zend_Controller_Request_Abstract $request, array $filter)
    {
        // \MUtil_Echo::r($filter, __CLASS__ . '->' . __FUNCTION__);

        $mainFilter = isset($filter['main_filter']) ? $filter['main_filter'] : null;
        unset($filter['main_filter']);

        $output = parent::processFilter($request, $filter);

        if ($mainFilter) {
            $output['main_filter'] = $mainFilter;
        }

        return $output;
    }

    /**
     * Stub function to allow extension of standard one table select.
     *
     * @param \Zend_Db_Select $select
     */
    protected function processSelect(\Zend_Db_Select $select)
    {
        $select->joinLeft('gems__rounds',      'gto_id_round = gro_id_round', array());
        $select->join('gems__tracks',          'gto_id_track = gtr_id_track', array());
        $select->join('gems__surveys',         'gto_id_survey = gsu_id_survey', array());
        $select->join('gems__groups',          'gsu_id_primary_group = ggp_id_group', array());
        $select->join('gems__respondents',     'gto_id_respondent = grs_id_user', array());
        $select->join('gems__respondent2track','gto_id_respondent_track = gr2t_id_respondent_track', array());
        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array());
    }

    protected function setTableBody(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        // $bridge->setAlternateRowClass('even', 'even', 'odd');

        parent::setTableBody($bridge, $repeater, $columnClass);
    }
}
