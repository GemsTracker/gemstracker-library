<?php

/**
 *
 * @package    Gems
 * @subpackage Conditions
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition;

use Gems\Cache\HelperAdapter;
use Gems\Condition\Comparator\Between;
use Gems\Condition\Comparator\ComparatorInterface;
use Gems\Condition\Comparator\Contains;
use Gems\Condition\Comparator\EqualLess;
use Gems\Condition\Comparator\EqualMore;
use Gems\Condition\Comparator\Equals;
use Gems\Condition\Comparator\In;
use Gems\Condition\Comparator\NotEquals;
use Gems\Condition\Round\AgeCondition;
use Gems\Condition\Round\AndCondition;
use Gems\Condition\Round\GenderCondition;
use Gems\Condition\Round\LastAnswerCondition;
use Gems\Condition\Round\OrCondition;
use Gems\Condition\Round\TrackFieldCondition;
use Gems\Condition\Track\LocationCondition;
use Gems\Condition\Track\OrganizationCondition;
use Gems\Exception\Coding;
use Gems\Model\ConditionModel;
use Gems\Util\Translated;
use MUtil\Translate\TranslateableTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\DependencyResolver\ConstructorDependencyResolver;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\ProjectOverloader;

/**
 * Per project overruleable condition processing engine
 *
 * @package    Gems
 * @subpackage Conditions
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionLoader
{
    use TranslateableTrait;

    const COMPARATOR           = 'Comparator';
    const COMPARATOR_BETWEEN   = 'Between';
    const COMPARATOR_CONTAINS  = 'Contains';
    const COMPARATOR_EQUALS    = 'Equals';
    const COMPARATOR_EQUALLESS = 'EqualLess';
    const COMPARATOR_EQUALMORE = 'EqualMore';
    const COMPARATOR_IN        = 'In';
    const COMPARATOR_NOT       = 'NotEquals';

    const ROUND_CONDITION  = 'Round';
    const TRACK_CONDITION  = 'Track';

    /**
     * Each condition type must implement a condition class or interface derived
     * from ConditionInterface specified in this array.
     *
     * @see ConditionInterface
     *
     * @var array containing eventType => eventClass for all condition classes
     */
    protected $_conditionClasses = [
        self::COMPARATOR           => ComparatorInterface::class,
        self::ROUND_CONDITION      => RoundConditionInterface::class,
        self::TRACK_CONDITION      => TrackConditionInterface::class,
    ];

    protected ProjectOverloader $conditionLoader;

    protected array $config = [];

    /**
     *
     * @var array containing conditionType => label for all condition classes
     */
    protected $_conditionTypes = [];

    public function __construct(
        protected ProjectOverloader $overloader,
        TranslatorInterface $translator,
        protected Translated $translatedUtil,
        protected HelperAdapter $cache,
        array $config,
    ) {
        $this->conditionLoader = clone $this->overloader;
        $this->conditionLoader->setDependencyResolver(new ConstructorDependencyResolver());
        $this->translate = $translator;
        if (isset($config['tracker'], $config['tracker']['conditions'])) {
            $this->config = $config['tracker']['conditions'];
        }
    }

    /**
     * Lookup condition class for an event type. This class or interface should at the very least
     * implement the ConditionInterface.
     *
     * @see ConditionInterface
     *
     * @param string $conditionType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function _getConditionClass(string $conditionType): string
    {
        if (isset($this->_conditionClasses[$conditionType])) {
            return $this->_conditionClasses[$conditionType];
        } else {
            throw new Coding("No condition class exists for condition type '$conditionType'.");
        }
    }

    /**
     * Returns a list of selectable conditions with an empty element as the first option.
     *
     * @param string $conditionType The type (i.e. lookup directory with an associated class) of the conditions to list
     * @return ConditionInterface[] or more specific a $conditionClass type object
     */
    protected function _listConditions(string $conditionType): array
    {
        $key = HelperAdapter::cleanupForCacheId(static::class . 'listConditions_' . $conditionType);
        if ($this->cache->hasItem($key)) {
            return $this->translatedUtil->getEmptyDropdownArray() + $this->cache->getCacheItem($key);
        }

        $conditions = $this->getConditionClasses($conditionType);

        $conditionList = [];
        if ($conditions) {
            foreach($conditions as $conditionClassName) {
                $condition = $this->getCondition($conditionClassName, $conditionType);
                $conditionList[$conditionClassName] = $condition->getName();
            }
        }

        $this->cache->setCacheItem($key, $conditionList);

        return $this->translatedUtil->getEmptyDropdownArray() + $conditionList;
    }

    public function getComparators(): ?array
    {
        if (isset($this->config['comparators'])) {
            return $this->config['comparators'];
        }
        return null;
    }

    public function getCondition(string $conditionClassName, string $conditionType): ConditionInterface
    {
        try {
            /**
             * @var $condition ConditionInterface
             */
            $condition = $this->conditionLoader->create($conditionClassName);
        } catch (LoadException) {
            throw new Coding("The condition '$conditionClassName' of type '$conditionType' can not be found");
        }

        $conditionClass = $this->_getConditionClass($conditionType);

        if (! $condition instanceof $conditionClass) {
            throw new ConditionLoadException("The condition '$conditionClassName' of type '$conditionType' is not an instance of '$conditionClass'.");
        }

        return $condition;
    }

    public function getConditionClasses(string $conditionType): ?array
    {
        if (isset($this->config[strtolower($conditionType)])) {
            return $this->config[strtolower($conditionType)];
        }
        return null;
    }

    public function getConditionModel(): ConditionModel
    {
        /**
         * @var $model ConditionModel
         */
        $model = $this->overloader->create('Model\\ConditionModel');
        return $model;
    }

    /**
     * @param string $conditionType A condition constant
     * @param bool $activeOnly
     * @return string[] condId => Name
     */
    public function getConditionsFor(string $conditionType, bool $activeOnly = true): array
    {
        $model = $this->getConditionModel();

        $filter['gcon_type'] = $conditionType;
        if ($activeOnly) {
            $filter['gcon_active'] = 1;
        }

        $model->trackUsage();
        $model->get('gcon_id');
        $model->get('gcon_name');
        $conditions = $model->load($filter, ['gcon_name']);

        $output = $this->translatedUtil->getEmptyDropdownArray();

        foreach($conditions as $condition) {
            $output[$condition['gcon_id']] = $condition['gcon_name'];
        }

        return $output;
    }

    /**
     * @return string[]
     */
    public function getConditionTypes(): array
    {
        if (! $this->_conditionTypes) {
            $this->_conditionTypes = [
                self::ROUND_CONDITION => $this->_('Round'),
                self::TRACK_CONDITION => $this->_('Track'),
            ];

            asort($this->_conditionTypes);
        }
        return $this->_conditionTypes;
    }

    /**
     *
     * @return string[] eventname => string
     */
    public function listConditionsForType(string $conditionType): array
    {
        return $this->_listConditions($conditionType);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listRoundConditions(): array
    {
        return $this->_listConditions(self::ROUND_CONDITION);
    }

    /**
     * Load a comparator
     *
     * @param string $name
     * @param array $options
     * @return ComparatorInterface|null
     */
    public function loadComparator(string $name, array $options = []): ?ComparatorInterface
    {
        $comparators = $this->getComparators();

        $comparatorClass = null;
        foreach($comparators as $comparator) {
            if (str_ends_with($comparator, "\\$name")) {
                $comparatorClass = $comparator;
            }
        }
        if ($comparatorClass !== null) {
            array_unshift($options, $this->translate);
            /**
             * @var ComparatorInterface
             */
            return $this->overloader->create($comparatorClass, ...$options);
        }
        return null;
    }

    /**
     *
     * @param string $conditionName
     * @return ConditionInterface|null
     */
    public function loadConditionForType(string $conditionType, string $conditionName): ?ConditionInterface
    {
        return $this->getCondition($conditionName, $conditionType);
    }

    /**
     *
     * @param string $conditionId
     * @return ConditionInterface
     */
    public function loadCondition(string $conditionId): ConditionInterface
    {
        $model = $this->getConditionModel();

        $conditionData = $model->loadFirst(['gcon_id' => $conditionId]);

        if ($conditionData) {
            $condition = $this->loadConditionForType($conditionData['gcon_type'], $conditionData['gcon_class']);
            $condition->exchangeArray($conditionData);

            return $condition;
        }

        throw new Coding('Unable to load requested condition');
    }

    /**
     *
     * @param string $conditionName
     * @return RoundConditionInterface
     */
    public function loadRoundCondition(string $conditionName): RoundConditionInterface
    {
        /**
         * @var RoundConditionInterface
         */
        return $this->getCondition($conditionName, self::ROUND_CONDITION);
    }

    /**
     *
     * @param string $conditionName
     * @return TrackConditionInterface
     */
    public function loadTrackCondition(string $conditionName): TrackConditionInterface
    {
        /**
         * @var TrackConditionInterface
         */
        return $this->getCondition($conditionName, self::TRACK_CONDITION);
    }
}
