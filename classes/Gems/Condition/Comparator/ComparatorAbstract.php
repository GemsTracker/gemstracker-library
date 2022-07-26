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

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
abstract class ComparatorAbstract extends \MUtil\Translate\TranslateableAbstract implements ComparatorInterface {
    
    public function __construct($options = array()) {
        $this->_options = $options;
    }
    
    /**
     * Get the descriptions for the parameters
     * 
     * @return []
     */
    public function getParamDescriptions() {
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
    public function getParamLabels() {
        return [
            $this->_('First parameter'),
            $this->_('Second parameter')
        ];
    }

}