<?php
/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

use Zalt\Model\MetaModelInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
interface UserDefinitionConfigurableInterface
{
    /**
     * Appends the needed fields for this config to the $bridge
     *
     * @param MetaModelInterface $orgModel
     */
    public function addConfigFields(MetaModelInterface $orgModel): void;

    /**
     * Should return the number of changed records for the save performed
     */
    public function getConfigChanged(): int;

    /**
     * Do we need to add custom config parameters to use this definition?
     *
     * @return boolean
     */
    public function hasConfig(): bool;

    /**
     * Handles loading the config for the given data
     *
     * @param array $data
     * @return array
     */
    public function loadConfig(array $data): array;

    /**
     * Handles saving the configvalues in $values using the $data
     *
     * @param array $data
     * @param array $values
     * @return array
     */
    public function saveConfig(array $data, array $values): array;
}