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

use Gems\Html;
use Zalt\Html\Raw;
use Zalt\Late\Late;
use Zalt\Snippets\ModelBridge\DetailTableBridge;

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
     * @param DetailTableBridge $bridge
     * @return void
     */
    protected function addTableCells(DetailTableBridge $bridge)
    {
        $metaModel = $this->model->getMetaModel();

        $HTML = Html::create();

        $bridge->getTable()->caption($this->getCaption());

        $br = $HTML->br();

        // ROW 0
        $label = $metaModel->get('gr2o_patient_nr', 'label'); // Try to read label from model...
        if (empty($label)) {
            $label = $this->_('Respondent nr: ');               // ...but have a fall-back
        }
        $bridge->addItem('gr2o_patient_nr', $label);
        if (! $this->maskRepository->areAllFieldsMaskedWhole('grs_last_name', 'grs_first_name', 'grs_gender', 'grs_surname_prefix')) {
            $bridge->addOther(
                $HTML->spaced(
                    Late::iif($bridge->grs_last_name, [$bridge->grs_last_name, ',']),
                    $bridge->grs_gender,
                    $bridge->grs_first_name,
                    $bridge->grs_surname_prefix
                ),
                $this->_('Respondent'));
        }
        // ROW 1
        if ($metaModel->has('grs_birthday') && (! $this->maskRepository->isFieldMaskedWhole('grs_birthday'))) {
            $bridge->addItem('grs_birthday');
        }
        if ($metaModel->has('grs_phone_1') && (! $this->maskRepository->isFieldMaskedWhole('grs_phone_1'))) {
            $bridge->addItem('grs_phone_1');
        }

        // ROW 2
        if ($metaModel->has('gr2o_email') && (! $this->maskRepository->isFieldMaskedWhole('gr2o_email'))) {
            $bridge->addItem('gr2o_email');
        }
        $address = [];
        if ($metaModel->has('grs_address_1') && (! $this->maskRepository->isFieldMaskedWhole('grs_address_1'))) {
            $address[] = $bridge->grs_address_1;
            $address[] = $br;
        }
        if ($metaModel->has('grs_address_2') && (! $this->maskRepository->isFieldMaskedWhole('grs_address_2'))) {
            $address[] = $bridge->grs_address_2;
            $address[] = Late::iif($bridge->grs_address_2, $br);
        }
        if ($metaModel->has('grs_zipcode') && (! $this->maskRepository->isFieldMaskedWhole('grs_zipcode'))) {
            $address[] = $bridge->grs_zipcode;
            $address[] = Late::iif($bridge->grs_zipcode, new Raw('&nbsp;&nbsp;'));
        }
        if ($metaModel->has('grs_city') && (! $this->maskRepository->isFieldMaskedWhole('grs_city'))) {
            $address[] = $bridge->grs_city;
        }
        if ($address) {
            $bridge->addOther($address, $this->_('Address'));
        }
    }
}
