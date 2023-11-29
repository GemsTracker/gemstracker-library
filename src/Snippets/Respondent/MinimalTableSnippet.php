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
use Gems\Model\Respondent\RespondentModel;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

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
     * @var bool When true and the columns are specified, use those
     */
    protected bool $useColumns = false;

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseColumn1(TableBridge $bridge, DataReaderInterface $dataModel)
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
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseColumn2(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();
        $metaModel->setIfExists('gr2o_opened', ['tableDisplay' => 'small',]);
        if ($metaModel->has('gr2o_opened')) {
            $bridge->addSortable('gr2o_opened', $this->_('Opened on'));
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
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseColumn3(TableBridge $bridge, DataReaderInterface $dataModel)
    {

        if (isset($this->searchFilter['grc_success']) && (! $this->searchFilter['grc_success'])) {
            $dataModel->getMetaModel()->set('grc_description', 'label', $this->_('Rejection code'));
            $bridge->addSortable('grc_description');

        } elseif (! isset($this->searchFilter[MetaModelInterface::REQUEST_ID2])) {
            $bridge->addSortable('gor_name');
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
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseColumn4(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();
        /**
         * @var RespondentModel $dataModel
         */
        if ($dataModel->getJoinStore()->hasTable('gems__respondent2track')) {
            $br = Html::create('br');

            $metaModel->set('gtr_track_name',  'label', $this->_('Track'));
            $metaModel->set('gr2t_track_info', 'label', $this->_('Track description'));

            $bridge->addMultiSort('gtr_track_name', $br, 'gr2t_track_info');
        }
    }
}
