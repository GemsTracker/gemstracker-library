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
use Gems\MenuNew\MenuSnippetHelper;
use Gems\MenuNew\RouteHelper;
use Gems\Model\ConditionModel;
use MUtil\Model;
use MUtil\Model\ModelAbstract;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
    protected $bridgeMode = BridgeAbstract::MODE_ROWS;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'condition';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuSnippetHelper,
        Translator $translate,
        protected ConditionLoader $conditionLoader
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuSnippetHelper, $translate);
        $this->caption = $translate->_('Conditions with this condition');
        $this->onEmpty = $translate->_('No conditions using this condition found');
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
     * @param MetaModelInterface $model
     */
    public function getFilter(MetaModelInterface $model): array
    {
        $filters = parent::getFilter($model);
        $attributes = $this->requestInfo->getRequestMatchedParams();

        if (isset($attributes[Model::REQUEST_ID])) {
            $filters[] = sprintf('gcon_condition_text1 = %1$s OR gcon_condition_text2 = %1$s OR gcon_condition_text3 = %1$s OR gcon_condition_text4 = %1$s', $attributes[Model::REQUEST_ID]);
            $filters[] = "gcon_class LIKE '%AndCondition' OR gcon_class LIKE '%OrCondition'";
        }

        return $filters;
    }
}
