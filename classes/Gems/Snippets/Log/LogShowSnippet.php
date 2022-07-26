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

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 23-apr-2015 11:10:02
 */
class LogShowSnippet extends \Gems\Snippets\ModelItemTableSnippetAbstract
{
    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_SINGLE_ROW;

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
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof LogModel) {
            $this->model = $this->loader->getModels()->createLogModel();
            $this->model->applyDetailSettings();
        }
        return $this->model;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        if ($this->request->getParam('log')) {
            $model->setFilter(array('gla_id' => $this->request->getParam('log')));
            parent::processSortOnly($model);
        } else {
            parent::processFilterAndSort($model);
        }
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $row = $bridge->getRow();

        parent::setShowTableFooter($bridge, $model);

        if (isset($row['gla_respondent_id'], $row['gla_organization']) &&
                ($this->menuList instanceof \Gems\Menu\MenuList)) {

            try {
                $patientNr = $this->util->getDbLookup()->getPatientNr($row['gla_respondent_id'], $row['gla_organization']);

                $this->menuList->addParameterSources(array(
                    'gr2o_patient_nr'      => $patientNr,
                    'gr2o_id_organization' => $row['gla_organization'],
                    ));

                $this->menuList->addByController('respondent', 'show', $this->_('Show respondent'));
            } catch (Exception $exc) {
                // Retrieving the patient van fail when an incorrect organization was logged. 
                // We silently fail and do not add thwe 'Show respondent' button
            }

            
        }
    }
}
