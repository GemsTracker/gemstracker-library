<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TableSnippet
 *
 * @author 175780
 */
class Gems_Snippets_Respondent_Relation_TableSnippet extends Gems_Snippets_ModelTableSnippetGeneric {
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_Bridge_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_Bridge_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($editMenuItem = $this->getEditMenuItem()) {
            $bridge->addItemLink($editMenuItem->toActionLinkLower($this->request, $bridge));
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

        if ($deleteMenuItem = $this->findMenuItem($this->request->getControllerName(), 'delete')) {
            $bridge->addItemLink($deleteMenuItem->toActionLinkLower($this->request, $bridge));
        }
    }
}