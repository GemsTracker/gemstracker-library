<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
abstract class RoundConditionAbstract extends \MUtil_Translate_TranslateableAbstract implements RoundConditionInterface
{
    protected $_data;
    
    public function exchangeArray(array $data)
    {
        $this->_data = $data;
    }

}
