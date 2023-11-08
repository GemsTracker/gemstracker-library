<?php

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
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use MUtil\Model;
use MUtil\Model\ModelAbstract;
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
        'conditionId' => '_getIdParam'
    ];
    
    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = ['Condition\\ConditionDeleteSnippet'];

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

    ) {
        parent::__construct($responder, $translate, $cache);
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return ModelAbstract
     */
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        $model = $this->conditionLoader->getConditionModel();

        if ($detailed) {
            if (('edit' == $action) || ('create' == $action)) {
                $model->applyEditSettings(('create' == $action));
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
        }

        $event = new ModelCreateEvent($model, $detailed, $action);
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
        if ($model->hasMeta('ConditionShowSnippets')) {
            $this->showSnippets = array_merge($this->showSnippets, (array) $model->getMeta('ConditionShowSnippets'));
        }

        parent::showAction();
    }
}