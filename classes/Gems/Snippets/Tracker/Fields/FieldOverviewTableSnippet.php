<?php

namespace Gems\Snippets\Tracker\Fields;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class FieldOverviewTableSnippet extends \Gems\Snippets\ModelTableSnippetGeneric
{
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $menuItem = $this->menu->find(array('controller' => 'respondent', 'action' => 'show', 'allowed' => true));
        if ($menuItem instanceof \Gems\Menu\SubMenuItem) {
            $href = $menuItem->toHRefAttribute($bridge);

            if ($href) {
                $aElem = new \MUtil\Html\AElement($href);
                $aElem->setOnEmpty('');

                // Make sure org is known
                $model->get('gr2o_id_organization');

                $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
                $model->set('respondent_name', 'itemDisplay', $aElem);
            }
        }
        
        parent::addBrowseTableColumns($bridge, $model);
    }
}
