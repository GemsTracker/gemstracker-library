<?php


/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Token;

/**
 * Utility functions for token string functions
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TokenFilter implements \Zend_Filter_Interface
{
    /**
     *
     * @var \Gems\Tracker\Token\TokenLibrary
     */
    private $_library;

    /**
     *
     * @param \Gems\Tracker\Token\TokenLibrary $library
     */
    public function __construct(\Gems\Tracker\Token\TokenLibrary $library)
    {
        $this->_library = $library;
    }

    public function filter($value)
    {
        return $this->_library->filter($value);
    }
}