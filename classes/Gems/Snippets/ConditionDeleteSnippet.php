<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class ConditionDeleteSnippet extends \Gems_Snippets_ModelItemYesNoDeleteSnippetAbstract {
    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @var \Gems\Model\ConditionModel
     */
    protected $model;

    /**
     *
     * @var int
     */
    protected $conditionId;

    /**
     * The number of times someone started answering a round in this track
     *
     * @var int
     */
    protected $useCount = 0;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof \Gems\Model\ConditionModel) {
            $this->model = $this->loader->getModels()->getConditionModel();
        }

        return $this->model;
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($model instanceof \Gems\Model\ConditionModel) {
            $this->useCount = $model->getUsedCount($this->conditionId);

            if ($this->useCount) {
                $this->addMessage(sprintf($this->plural(
                        'This condition has been used %s time.', 'This condition has been used %s times.',
                        $this->useCount
                        ), $this->useCount));
                $this->addMessage($this->_('This condition cannot be deleted, only deactivated.'));

                $this->deleteQuestion = $this->_('Do you want to deactivate this condition?');
                $this->displayTitle   = $this->_('Deactivate condition');
            }
        }

        parent::setShowTableFooter($bridge, $model);
    }
}