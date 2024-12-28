<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config;

/**
 * @package    Gems
 * @subpackage Config
 * @since      Class available since version 1.0
 */
class ConfigAccessor
{
    public function __construct(
        protected readonly array $config,
    )
    { }

    public function isAutosearch(): bool
    {
        return $this->config['interface']['autosearch'] ?? false;
    }
}