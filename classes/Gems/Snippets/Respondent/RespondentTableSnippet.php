<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 16, 2016 4:54:15 PM
 */
class RespondentTableSnippet extends RespondentTableSnippetAbstract
{
    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn1(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');

        if (isset($this->searchFilter['grc_success']) && (! $this->searchFilter['grc_success'])) {
            $model->set('grc_description', 'label', $this->_('Rejection code'));
            $column2 = 'grc_description';

        } elseif (isset($this->searchFilter[\MUtil_Model::REQUEST_ID2])) {
            $model->setIfExists('gr2o_opened', 'tableDisplay', 'small');
            $column2 = 'gr2o_opened';

        } else {
            $column2 = 'gr2o_id_organization';
        }

        $bridge->addMultiSort('gr2o_patient_nr', $br, $column2);
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn2(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($this->currentUser->isFieldMaskedWhole('name') && $this->currentUser->isFieldMaskedWhole('gr2o_email')) {
            return;
        }

        $br = \MUtil_Html::create('br');

        $model->setIfExists('gr2o_email', 'formatFunction', array('MUtil_Html_AElement', 'ifmail'));

        $bridge->addMultiSort('name', $br, 'gr2o_email');
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn3(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');

        if ($model->hasAlias('gems__respondent2track')) {
            $model->set('gtr_track_name',  'label', $this->_('Track'));
            $model->set('gr2t_track_info', 'label', $this->_('Track description'));

            $items = $this->findMenuItems('track', 'show-track');
            $track = 'gtr_track_name';
            if ($items) {
                $menuItem = reset($items);
                if ($menuItem instanceof \Gems_Menu_MenuAbstract) {
                    $href  = $menuItem->toHRefAttribute(
                            $this->request,
                            $bridge,
                            array('gr2t_id_respondent_track' => $bridge->gr2t_id_respondent_track)
                            );
                    $track = array();
                    $track[0] = \MUtil_Lazy::iif($bridge->gr2t_id_respondent_track,
                            \MUtil_Html::create()->a(
                                    $href,
                                    $bridge->gtr_track_name,
                                    array('onclick' => "event.cancelBubble = true;")
                                    )
                            );
                    $track[1] = $bridge->createSortLink('gtr_track_name');
                }
            }

            $bridge->addMultiSort($track, $br, 'gr2t_track_info');
        } else {
            $maskAddress = $this->currentUser->isFieldMaskedWhole('grs_address_1');
            $maskZip     = $this->currentUser->isFieldMaskedWhole('grs_zipcode');
            $maskCity    = $this->currentUser->isFieldMaskedWhole('grs_city');

            if ($maskAddress && $maskZip && $maskCity) {
                return;
            }

            if ($maskAddress && $maskCity) {
                $bridge->addMultiSort('grs_zipcode');
                return;
            }

            if ($maskAddress && $maskZip) {
                $bridge->addMultiSort('grs_city');
                return;
            }
            if ($maskAddress) {
                $bridge->addMultiSort('grs_zipcode', $br, 'grs_city');
                return;
            }

            $citysep  = \MUtil_Html::raw('&nbsp;&nbsp;');

            $bridge->addMultiSort('grs_address_1', $br, 'grs_zipcode', $citysep, 'grs_city');
        }
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn4(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $maskBirthday = $this->currentUser->isFieldMaskedWhole('grs_birthday');
        $maskPhone    = $this->currentUser->isFieldMaskedWhole('grs_phone_1');

        if ($maskBirthday && $maskPhone) {
            return;
        }

        if ($maskPhone && ! $maskBirthday)  {
            $bridge->addMultiSort('grs_birthday');
            return;
        }

        if (! $maskPhone)  {

            // Display separator and phone sign only if phone exist.
            $phonesep = \MUtil_Html::raw('&#9743; '); // $bridge->itemIf($bridge->grs_phone_1, \MUtil_Html::raw('&#9743; '));
        }
        if ($maskBirthday) {
            $bridge->addMultiSort($phonesep, 'grs_phone_1');
        }

        $br = \MUtil_Html::create('br');
        $bridge->addMultiSort('grs_birthday', $br, $phonesep, 'grs_phone_1');
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn5(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    { }
}
