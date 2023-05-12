<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition\Comparator;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
interface ComparatorInterface
{
    /**
     * @param array $options
     */
    public function __construct(TranslatorInterface $translator, array $options = []);

    /**
     * Return a readable description, using the given subject and configured options
     *
     * @param string $subject
     * @return string
     */
    public function getDescription(string $subject): string;

    /**
     * The number of parameters this comparator expects
     *
     * @return int Less than 5
     */
    public function getNumParams(): int;
    
    /**
     * Get the descriptions for the parameters
     * 
     * @return []
     */
    public function getParamDescriptions(): array;
    
    /**
     * Get the labels for the parameters
     * 
     * @return []
     */
    public function getParamLabels(): array;

    /**
     * Is the comparison valid?
     *
     * Settings should already be in place by the constructor.
     *
     * @param mixed $value The id of the condition
     * @return bool
     */
    public function isValid(mixed $value): bool;
}