<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.9.2
 */
trait TopicCallableTrait
{
    /**
     * When set getTopic uses this function instead of parent class.
     *
     * @var callable
     */
    protected $topicCallable;

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        if (is_callable($this->topicCallable)) {
            return call_user_func($this->topicCallable, $count);
        } elseif (property_exists($this, 'subjects')) {
            return $this->plural($this->subjects[0], $this->subjects[1], $count);
        } else {
            return $this->plural('item', 'items', $count);
        }
    }


}