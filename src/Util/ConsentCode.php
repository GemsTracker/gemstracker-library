<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use Gems\Repository\ConsentRepository;

/**
 * Utility function for the user of consents.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class ConsentCode
{
    public function __construct(
        protected readonly string $description,
        protected readonly string $code,
        protected readonly int|null $order = null,
        protected readonly string $consentRejectedCode = 'do not use',
    )
    {
    }
    
    /**
     * Compatibility mode, for use with logical operators returns this->getCode()
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getCode();
    }

    /**
     * Can you use the data with this code
     *
     * @return boolean
     */
    public function canBeUsed(): bool
    {
        return $this->getCode() !== $this->consentRejectedCode;
    }

    /**
     * Returns the complete record.
     *
     * @return array
     */
    public function getAllData(): array
    {
        return [
            'gco_code' => $this->code,
            'gco_description' => $this->description,
            'gco_order' => $this->order,
        ];
    }

    /**
     * The reception code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     *
     * @return boolean
     */
    public function getDescription(): bool
    {
        return $this->description;
    }

    /**
     *
     * @return boolean
     */
    public function hasDescription(): bool
    {
        return !empty($this->description);
    }

    /**
     * @return int|null
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }
}
