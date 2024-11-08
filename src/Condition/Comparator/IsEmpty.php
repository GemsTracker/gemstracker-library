<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Condition\Comparator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Condition\Comparator;

use Gems\Condition\Comparator\ComparatorAbstract;

/**
 * @package    Gems
 * @subpackage Condition\Comparator
 * @since      Class available since version 1.0
 */
class IsEmpty extends ComparatorAbstract
{

    /**
     * @inheritDoc
     */
    public function getDescription(string $subject): string
    {
        return sprintf(
            $this->_("%s is null or %s = ''"),
            $subject,
            $subject,
        );
    }

    /**
     * @inheritDoc
     */
    public function getNumParams(): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function isValid(mixed $value): bool
    {
        return strlen($value) === 0;
    }
}