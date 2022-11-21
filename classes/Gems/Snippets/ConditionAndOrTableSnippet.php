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

use Gems\Condition\ConditionLoader;
use Gems\Model\ConditionModel;
use MUtil\Model;
use MUtil\Model\ModelAbstract;
use Zalt\Model\Data\DataReaderInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2019, Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class ConditionAndOrTableSnippet extends ModelTableSnippetAbstract
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
     * @var ConditionModel
     */
    protected $_model;

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_ROWS;

    /**
     * @var ConditionLoader
     */
    protected $conditionLoader;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'condition';

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
     * @return ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->_model instanceof ConditionModel) {
            $this->_model = $this->conditionLoader->getConditionModel();
            $this->_model->applyBrowseSettings(true);
        }

        return $this->_model;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param ModelAbstract $model
     */
    protected function processFilterAndSort(ModelAbstract $model)
    {
        $attributes = $this->requestInfo->getRequestMatchedParams();

        if (isset($attributes[Model::REQUEST_ID])) {
            $model->addFilter([sprintf('gcon_condition_text1 = %1$s OR gcon_condition_text2 = %1$s OR gcon_condition_text3 = %1$s OR gcon_condition_text4 = %1$s', $attributes[Model::REQUEST_ID]),
                "gcon_class LIKE '%AndCondition' OR gcon_class LIKE '%OrCondition'"]);
        }

        $this->processSortOnly($model);
    }
}
