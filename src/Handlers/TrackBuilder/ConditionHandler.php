<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Condition\ConditionInterface;
use Gems\Condition\ConditionLoader;
use Gems\Event\Application\ModelCreateEvent;
use Gems\Exception\Coding;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model\ConditionUsageCounter;
use Gems\Model\ConditionModel;
use Gems\Model\Dependency\UsageDependency;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelConfirmDeleteSnippet;
use Gems\Snippets\Usage\UsageSnippet;
use Gems\SnippetsLoader\GemsSnippetResponder;
use MUtil\Model;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gcon_name' => SORT_ASC],
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['conditions'];

    /**
     * @var ConditionInterface|null
     */
    protected ?ConditionInterface $condition = null;

    protected array $deleteSnippets = [
        ModelConfirmDeleteSnippet::class,
        UsageSnippet::class
    ];

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $deleteParameters = [
        'conditionId' => '_getIdParam',
        'usageCounter' => '_getUsageCounter',
    ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        'Generic\\ContentTitleSnippet',
        'Condition\\ConditionSearchFormSnippet'
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showParameters = [
        'condition' => 'getCondition',
        'usageCounter' => '_getUsageCounter'
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelDetailTableSnippet',
        CurrentButtonRowSnippet::class,
        'Tracker\\Rounds\\ConditionRoundsTableSnippet',
        'Condition\\ConditionAndOrTableSnippet',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected ConditionLoader $conditionLoader,
        protected EventDispatcherInterface $event,
        protected readonly ConditionUsageCounter $conditionUsageCounter,

    ) {
        parent::__construct($responder, $translate, $cache);
    }

    protected function _getUsageCounter(): ConditionUsageCounter
    {
        return $this->conditionUsageCounter;
    }

    protected function createModel(bool $detailed, string $action): ConditionModel
    {
        $model = $this->conditionLoader->getConditionModel();

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings(('create' === $action));
            } else {
                $model->applyDetailSettings();
            }
            if (! $model->getMetaModel()->hasDependency('gcon_active')) {
                if ($this->responder instanceof GemsSnippetResponder) {
                    $menuHelper = $this->responder->getMenuSnippetHelper();
                } else {
                    $menuHelper = null;
                }
                $metaModel = $model->getMetaModel();
                $metaModel->addDependency(new UsageDependency(
                    $this->translate,
                    $metaModel,
                    $this->conditionUsageCounter,
                    $menuHelper,
                ));
            }
        } else {
            $model->applyBrowseSettings();
        }

        $event = new ModelCreateEvent($model, $action, $detailed);
        $this->event->dispatch($event, $event->name);

        return $model;
    }

    /**
     * @return ConditionInterface
     * @throws Coding
     */
    public function getCondition(): ConditionInterface
    {
        $id = $this->request->getAttribute(Model::REQUEST_ID);

        if ($id && (!$this->condition)) {
            $this->condition = $this->conditionLoader->loadCondition($id);
        }

        return $this->condition;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Conditions');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('condition', 'conditions', $count);
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction(): void
    {
        $model = $this->getModel();
        if ($model->getMetaModel()->hasMeta('ConditionShowSnippets')) {
            $this->showSnippets = array_merge(
                $this->showSnippets,
                (array) $model->getMetaModel()->getMeta('ConditionShowSnippets')
            );
        }

        parent::showAction();
    }
}