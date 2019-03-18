<?php

/**
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
     * The base filter to search with
     *
     * @var array
     */
    protected $searchFilter;

    /**
     *
     * @var array Every tokenData status displayed in table
     */
    protected $statiUsed = ['O', 'P', 'I', 'M', 'A'];

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

        $tUtil = $this->util->getTokenData();
        foreach ($this->statiUsed as $key) {
            $this->addField('stat_' . $key)
                    ->setLabel([$tUtil->getStatusIcon($key), ' ', $tUtil->getStatusDescription($key)])
                    ->setToSumWhen($tUtil->getStatusExpressionFor($key));
        }
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
        
        $forGroup = isset($filter['forgroup']) ? $filter['forgroup'] : null;
        unset($filter['forgroup']);
        if ($forGroup) {
            $filter[] = $this->db->quoteinto('(ggp_name = ? AND gto_id_relationfield IS NULL) or gtf_field_name = ?', $forGroup);
        }
        
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
        // $select->joinLeft('gems__rounds',      'gto_id_round = gro_id_round', array());
        // $select->join('gems__tracks',          'gto_id_track = gtr_id_track', array());
        $select->join('gems__surveys',         'gto_id_survey = gsu_id_survey', array());
        $select->join('gems__groups',          'gsu_id_primary_group = ggp_id_group', array());
        $select->join('gems__respondents',     'gto_id_respondent = grs_id_user', array());
        $select->join('gems__respondent2org',  '(gto_id_organization = gr2o_id_organization AND gto_id_respondent = gr2o_id_user)', array());
        $select->join('gems__respondent2track','gto_id_respondent_track = gr2t_id_respondent_track', array());        
        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array());
        $select->joinLeft('gems__respondent_relations', '(gto_id_relation = grr_id AND gto_id_respondent = grr_id_respondent)', array()); // Add relation
        $select->joinLeft('gems__track_fields',  '(gto_id_relationfield = gtf_id_field AND gtf_field_type = "relation")', array());       // Add relation fields     
    }

    protected function setTableBody(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        // $bridge->setAlternateRowClass('even', 'even', 'odd');

        parent::setTableBody($bridge, $repeater, $columnClass);
    }
}
