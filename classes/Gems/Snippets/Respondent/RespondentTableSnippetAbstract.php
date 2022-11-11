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

use Gems\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:32:53 PM
 */
abstract class RespondentTableSnippetAbstract extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * @var \Zalt\Snippets\ModelBridge\TableBridge $bridge
     */
    protected $bridge;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * When true and the columns are specified, use those
     *
     * @var boolean
     */
    protected $useColumns = true;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function addBrowseColumn1(TableBridge $bridge, DataReaderInterface $dataModel)
    { }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function addBrowseColumn2(TableBridge $bridge, DataReaderInterface $dataModel)
    { }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function addBrowseColumn3(TableBridge $bridge, DataReaderInterface $dataModel)
    { }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function addBrowseColumn4(TableBridge $bridge, DataReaderInterface $dataModel)
    { }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function addBrowseColumn5(TableBridge $bridge, DataReaderInterface $dataModel)
    { }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function addBrowseTableColumnsColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $this->bridge = $bridge;

        if ($this->useColumns && $this->columns) {
            parent::addBrowseTableColumns($bridge, $dataModel);
            return;
        }

        $model = $dataModel->getMetaModel();
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->getShowUrls($bridge);

            foreach ($showMenuItems as $menuItem) {
                $bridge->addItemLink(Html::actionLink($menuItem, $this->_('Show')));
            }
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        $this->addBrowseColumn1($bridge, $dataModel);
        $this->addBrowseColumn2($bridge, $dataModel);
        $this->addBrowseColumn3($bridge, $dataModel);
        $this->addBrowseColumn4($bridge, $dataModel);
        $this->addBrowseColumn5($bridge, $dataModel);

        if ($this->showMenu) {
            $editMenuItems = $this->getEditUrls($bridge);

            foreach ($editMenuItems as $menuItem) {
                $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $this->_('Edit')));
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof \Gems\Model\RespondentModel) {
            $this->model = $this->loader->getModels()->createRespondentModel();
            $this->model->applyBrowseSettings();
        }
        return $this->model;
    }
}
