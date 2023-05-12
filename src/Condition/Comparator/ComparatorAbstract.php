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
use Zalt\Base\TranslateableTrait;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
abstract class ComparatorAbstract implements ComparatorInterface
{
    use TranslateableTrait;

    protected array $_options;

    public function __construct(TranslatorInterface $translator, array $options = []) {
        $this->_options = $options;
        $this->translate = $translator;

    }
    
    /**
     * Get the descriptions for the parameters
     * 
     * @return ?string[]
     */
    public function getParamDescriptions(): array {
        return [
            null,
            null
        ];
    }
    
    /**
     * Get the labels for the parameters
     * 
     * @return []
     */
    public function getParamLabels(): array {
        return [
            $this->_('First parameter'),
            $this->_('Second parameter')
        ];
    }

}