<?php

/**
 *
 *
 * @package    Gems
 * @subpackage Condition
 * @author     mjong
 * @license    Not licensed, do not copy
 */

namespace Gems\Condition;

use Gems\Condition\Comparator\ComparatorInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @since      Class available since version 1.8.8
 */
abstract class ConditionAbstract implements ConditionInterface
{
    use TranslateableTrait;

    protected $_data;

    public function __construct(protected ConditionLoader $conditions, TranslatorInterface $translator)
    {
        $this->translate = $translator;
    }

    /**
     * @param array $data
     */
    public function exchangeArray(array $data): void
    {
        $this->_data = $data;
    }

    /**
     * Get the condition id for this condition
     *
     * @return int
     */
    public function getConditionId(): int
    {
        return $this->_data['gcon_id'];
    }

    /**
     * Return a comparator
     *
     * @return ComparatorInterface
     */
    public function getComparator(string $name, $options = array()): ComparatorInterface
    {
        return $this->conditions->loadComparator($name, $options);
    }

}