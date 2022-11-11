<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Zalt\Html\AElement;
use Zalt\Html\Html;
use Zalt\Html\Raw;

/**
 * Displays a respondent's details
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentDetailsSnippet extends \Gems\Snippets\RespondentDetailSnippetAbstract
{
    /**
     * Place to set the data to display
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @return void
     */
    protected function addTableCells(\MUtil\Model\Bridge\VerticalTableBridge $bridge)
    {
        $HTML = Html::create();

        $bridge->caption($this->getCaption());

        $br = $HTML->br();

        // ROW 0
        $label = $this->model->get('gr2o_patient_nr', 'label'); // Try to read label from model...
        if (empty($label)) {
            $label = $this->_('Respondent nr: ');               // ...but have a fall-back
        }
        $bridge->addItem($bridge->gr2o_patient_nr, $label);
        if (! $this->currentUser->areAllFieldsMaskedWhole('grs_last_name', 'grs_first_name', 'grs_gender', 'grs_surname_prefix')) {
            $bridge->addItem(
                $HTML->spaced(
                        $bridge->itemIf('grs_last_name', array($bridge->grs_last_name, ',')),
                        $bridge->grs_gender,
                        $bridge->grs_first_name,
                        $bridge->grs_surname_prefix
                        ),
                $this->_('Respondent'));
        }
        // ROW 1
        if ($this->model->has('grs_birthday') && (! $this->currentUser->isFieldMaskedWhole('grs_birthday'))) {
            $bridge->addItem('grs_birthday');
        }
        if ($this->model->has('grs_phone_1') && (! $this->currentUser->isFieldMaskedWhole('grs_phone_1'))) {
            $bridge->addItem('grs_phone_1');
        }

        // ROW 2
        if ($this->model->has('gr2o_email') && (! $this->currentUser->isFieldMaskedWhole('gr2o_email'))) {
            $bridge->addItem('gr2o_email');
        }
        $address = [];
        if ($this->model->has('grs_address_1') && (! $this->currentUser->isFieldMaskedWhole('grs_address_1'))) {
            $address[] = $bridge->grs_address_1;
            $address[] = $br;
        }
        if ($this->model->has('grs_address_2') && (! $this->currentUser->isFieldMaskedWhole('grs_address_2'))) {
            $address[] = $bridge->grs_address_2;
            $address[] = $bridge->itemIf('grs_address_2', $br);
        }
        if ($this->model->has('grs_zipcode') && (! $this->currentUser->isFieldMaskedWhole('grs_zipcode'))) {
            $address[] = $bridge->grs_zipcode;
            $address[] = $bridge->itemIf('grs_zipcode', new Raw('&nbsp;&nbsp;'));
        }
        if ($this->model->has('grs_city') && (! $this->currentUser->isFieldMaskedWhole('grs_city'))) {
            $address[] = $bridge->grs_city;
        }
        if ($address) {
            $bridge->addItem($address, $this->_('Address'));
        }
    }
}
