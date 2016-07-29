<?php


/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Utility functions for token string functions
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Token_TokenFilter implements \Zend_Filter_Interface
{
    /**
     *
     * @var \Gems_Tracker_Token_TokenLibrary
     */
    private $_library;

    /**
     *
     * @param \Gems_Tracker_Token_TokenLibrary $library
     */
    public function __construct(\Gems_Tracker_Token_TokenLibrary $library)
    {
        $this->_library = $library;
    }

    public function filter($value)
    {
        return $this->_library->filter($value);
    }
}