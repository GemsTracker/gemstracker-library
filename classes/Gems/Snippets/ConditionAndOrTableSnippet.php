<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Tracker\Model\RoundModel;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2019, Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class ConditionAndOrTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = [
        'gcon_type'   => SORT_ASC,
        'gcon_name' => SORT_ASC,        
        ];

    /**
     *
     * @var \Gems_Model_ConditionModel
     */
    protected $_model;

    /**
     * One of the \MUtil_Model_Bridge_BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_ROWS;

    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'condition';

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->browse  = true;
        $this->caption = $this->_('Conditions with this condition');
        $this->onEmpty = $this->_('No conditions using this condition found');
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->_model instanceof ConditionModel) {
            $this->_model = $this->loader->getModels()->getConditionModel();
            $this->_model->applyBrowseSettings(true);
        }

        return $this->_model;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        $conditionId = $this->request->getParam(\MUtil_Model::REQUEST_ID);

        if ($conditionId) {
            $model->addFilter([sprintf('gcon_condition_text1 = %1$s OR gcon_condition_text2 = %1$s OR gcon_condition_text3 = %1$s OR gcon_condition_text4 = %1$s', $conditionId)]);
        }

        $this->processSortOnly($model);
    }
}
