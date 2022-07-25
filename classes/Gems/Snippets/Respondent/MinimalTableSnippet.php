<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:47:43 PM
 */
class MinimalTableSnippet extends RespondentTableSnippetAbstract
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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn1(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $bridge->addSortable('gr2o_patient_nr');
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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn2(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $model->setIfExists('gr2o_opened', 'tableDisplay', 'small');
        $bridge->addSortable('gr2o_opened');
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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn3(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {

        if (isset($this->searchFilter['grc_success']) && (! $this->searchFilter['grc_success'])) {
            $model->set('grc_description', 'label', $this->_('Rejection code'));
            $bridge->addSortable('grc_description');

        } elseif (! isset($this->searchFilter[\MUtil\Model::REQUEST_ID2])) {
            $bridge->addSortable('gr2o_id_organization');
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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn4(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        if ($model->hasAlias('gems__respondent2track')) {
            $br = \MUtil\Html::create('br');

            $model->set('gtr_track_name',  'label', $this->_('Track'));
            $model->set('gr2t_track_info', 'label', $this->_('Track description'));

            $items = $this->findMenuItems('track', 'show-track');
            $track = 'gtr_track_name';
            if ($items) {
                $menuItem = reset($items);
                if ($menuItem instanceof \Gems\Menu\MenuAbstract) {
                    $href  = $menuItem->toHRefAttribute(
                            $this->request,
                            $bridge,
                            array('gr2t_id_respondent_track' => $bridge->gr2t_id_respondent_track)
                            );
                    $track = array();
                    $track[0] = \MUtil\Lazy::iif($bridge->gr2t_id_respondent_track,
                            \MUtil\Html::create()->a(
                                    $href,
                                    $bridge->gtr_track_name,
                                    array('onclick' => "event.cancelBubble = true;")
                                    )
                            );
                    $track[1] = $bridge->createSortLink('gtr_track_name');
                }
            }

            $bridge->addMultiSort($track, $br, 'gr2t_track_info');
        }
    }
}
