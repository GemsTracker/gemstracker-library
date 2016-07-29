<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Respondent;

/**
 * Displays a respondent's details
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentDetailsSnippet extends \Gems_Snippets_RespondentDetailSnippetAbstract
{
    /**
     * Place to set the data to display
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function addTableCells(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        $HTML = \MUtil_Html::create();

        $bridge->caption($this->getCaption());

        $br = $HTML->br();
        $address[] = $bridge->grs_address_1;
        $address[] = $br;
        if ($this->model->has('grs_address_2')) {
            $address[] = $bridge->grs_address_2;
            $address[] = $bridge->itemIf('grs_address_2', $br);
        }
        $address[] = $bridge->grs_zipcode;
        $address[] = $bridge->itemIf('grs_zipcode', new \MUtil_Html_Raw('&nbsp;&nbsp;'));
        $address[] = $bridge->grs_city;

        // ROW 0
        $label = $this->model->get('gr2o_patient_nr', 'label'); // Try to read label from model...
        if (empty($label)) {
            $label = $this->_('Respondent nr: ');               // ...but have a fall-back
        }
        $bridge->addItem($bridge->gr2o_patient_nr, $label);
        $bridge->addItem(
            $HTML->spaced($bridge->itemIf('grs_last_name', array($bridge->grs_last_name, ',')), $bridge->grs_gender, $bridge->grs_first_name, $bridge->grs_surname_prefix),
            $this->_('Respondent'));

        // ROW 1
        $bridge->addItem('grs_birthday');
        $bridge->addItem('grs_phone_1');

        // ROW 2
        $bridge->addItem('grs_email');
        $bridge->addItem($address, $this->_('Address'));
    }
}
