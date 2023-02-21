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

use Gems\Html;
use Gems\Snippets\ModelTableSnippet;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 * Ask Yes/No conformation for deletion and deletes respondent relation when confirmed.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class TableSnippet extends ModelTableSnippet {
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
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();
        $keys      = $this->getRouteMaps($metaModel);

        if ($metaModel->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            foreach ($this->getEditUrls($bridge, $keys) as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Show');
                }
                $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
            }
        }

        if ($this->columns) {
            foreach ($this->columns as $column) {
                call_user_func_array(array($bridge, 'addMultiSort'), $column);
            }
        } elseif ($this->sortableLinks) {
            foreach($metaModel->getItemsOrdered() as $name) {
                if ($metaModel->has($name, 'label')) {
                    $label = $metaModel->get($name, 'label');
                    $bridge->addSortable($name, $label);
                }
            }
        } else {
            foreach($metaModel->getItemsOrdered() as $name) {
                if ($metaModel->has($name, 'label')) {
                    $label = $metaModel->get($name, 'label');
                    $bridge->add($name, $label);
                }
            }
        }

        if ($this->showMenu) {
            $deleteButtons = $this->menuHelper->getLateRouteUrl('respondent.relations.delete', $keys, $bridge);
            foreach($deleteButtons as $deleteButton) {
                $bridge->addItemLink(\Gems\Html::actionLink($deleteButton, $this->_('Delete')));
            }
        }
    }
}