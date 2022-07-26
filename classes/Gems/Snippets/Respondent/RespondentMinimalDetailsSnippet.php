<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 16-Apr-2020 13:04:46
 */
class RespondentMinimalDetailsSnippet extends \Gems\Snippets\RespondentDetailSnippetAbstract
{
    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentParent = false;

    /**
     * Place to set the data to display
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @return void
     */
    protected function addTableCells(\MUtil\Model\Bridge\VerticalTableBridge $bridge)
    {
        $HTML = \MUtil\Html::create();

        // $bridge->caption($this->getCaption());

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
    }
}
