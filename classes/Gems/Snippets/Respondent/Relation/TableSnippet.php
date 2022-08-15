<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Relation;

/**
 * Ask Yes/No conformation for deletion and deletes respondent relation when confirmed.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class TableSnippet extends \Gems\Snippets\ModelTableSnippetGeneric {
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $editMenuItems = $this->getEditUrls($bridge);

            foreach ($editMenuItems as $menuItem) {
                $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $this->_('Edit')));
            }
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        if ($this->columns) {
            foreach ($this->columns as $column) {
                call_user_func_array(array($bridge, 'addMultiSort'), $column);
            }
        } elseif ($this->sortableLinks) {
            foreach($model->getItemsOrdered() as $name) {
                if ($label = $model->get($name, 'label')) {
                    $bridge->addSortable($name, $label);
                }
            }
        } else {
            foreach($model->getItemsOrdered() as $name) {
                if ($label = $model->get($name, 'label')) {
                    $bridge->add($name, $label);
                }
            }
        }

        if ($deleteButtons = $this->findUrls(['action'], $bridge)) {
            foreach($deleteButtons as $deleteButton) {
                $bridge->addItemLink(\Gems\Html::actionLink($deleteButton, $this->_('Delete')));
            }
        }
    }
}