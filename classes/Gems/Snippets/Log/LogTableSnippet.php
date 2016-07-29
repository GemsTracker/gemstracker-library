<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LogTableSnippet.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Snippets\Log;

use Gems\Model\LogModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:17:48
 */
class LogTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gla_created' => SORT_DESC);

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if (! $this->columns) {
            $br   = \MUtil_Html::create('br');

            $this->columns[10] = array('gla_created', $br, 'gls_name');
            $this->columns[20] = array('gla_message');
            $this->columns[30] = array('staff_name', $br, 'gla_role');
            $this->columns[40] = array('respondent_name', $br, 'gla_organization');
        }

        parent::addBrowseTableColumns($bridge, $model);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof LogModel) {
            $this->model = $this->loader->getModels()->createLogModel();
            $this->model->applyBrowseSettings();
        }

        return $this->model;
    }
}
