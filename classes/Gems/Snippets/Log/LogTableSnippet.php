<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Log;

use Gems\Model;
use Gems\Model\LogModel;
use Gems\Snippets\ModelTableSnippetAbstract;
use Zalt\Html\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:17:48
 */
class LogTableSnippet extends ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['gla_created' => SORT_DESC];

    /**
     * @var Model
     */
    protected $modelLoader;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

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
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        if (! $this->columns) {
            $this->columns = [];
            $br   = Html::create('br');

            $this->columns[10] = ['gla_created', $br, 'gls_name'];
            $this->columns[20] = ['gla_message'];
            $this->columns[30] = ['staff_name', $br, 'gla_role'];
            $this->columns[40] = ['respondent_name', $br, 'gla_organization'];
        }

        parent::addBrowseTableColumns($bridge, $model);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof LogModel) {
            $this->model = $this->modelLoader->createLogModel();
            $this->model->applyBrowseSettings();
        }

        return $this->model;
    }
}
