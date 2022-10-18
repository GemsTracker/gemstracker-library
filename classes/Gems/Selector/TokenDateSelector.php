<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
     * Stub function to allow extension of model with extra columns
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processModel(\MUtil_Model_ModelAbstract $model)
    {  
        $model->set('gto_id_token');
        $model->set('gsu_id_primary_group');
        $model->set('gsu_active');
        $model->set('grc_success');
    }

    /**
     * Stub function to allow extension of standard one table select.
     *
     * @param \Zend_Db_Select $select
     */
    protected function processSelect(\Zend_Db_Select $select)
    {
        $select->join('gems__surveys', 'gto_id_survey = gsu_id_survey', []);
        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', []);
    } // */
}
