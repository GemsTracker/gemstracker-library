<?php

/**
 * Copyright (c) 2014, J-POP Foundation
 * All rights reserved.
 *
 * @package    Booth
 * @subpackage Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 J-POP Foundation
 * @license    no free license, do not use without permission
 * @version    $Id: Error.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    Booth
 * @subpackage Log
 * @copyright  Copyright (c) 2014 J-POP Foundation
 * @license    no free license, do not use without permission
 * @since      Class available since 2014 $(date} 21:13:21
 */
class MUtil_Log extends Zend_Log
{
    const ROTATE_PER_MONTH = 'M';

    /**
     * The current filename
     *
     * @var string
     */
    protected $_logFileName;

    /**
     * The root for the current filenamea
     *
     * @var string
     */
    protected $_logFileRoot;

    /**
     * The rotate function
     *
     * @var string
     */
    protected $_logRotate;

    /**
     * Construct a logger with filename depending on $logRotate
     *
     * @param mixed $filename Start of the filename minus .log extension
     * @param mixed $logRotate One of the cosntants for log rotate
     * @param int One of the Zend_Log constants
     */
    public function __construct($filename, $logRotate = null, $priority = null)
    {
        $this->_logFileRoot = $filename;
        $this->_logRotate   = $logRotate;

        switch ($logRotate) {
            case self::ROTATE_PER_MONTH:
                $now = new MUtil_Date();
                $filename .= '-' . $now->toString(Zend_Date::MONTH) . '.log';
                break;

            default:
                if (! MUtil_String::endsWith($filename, '.log')) {
                    $filename .= '.log';
                }
                break;
        }

        $this->_logFileName = $filename;

        try {
            $writer = new Zend_Log_Writer_Stream($filename);
        } catch (Exception $exc) {
            try {
                // Try to solve the problem, otherwise fail heroically
                MUtil_File::ensureDir(dirname($filename));
                $writer = new Zend_Log_Writer_Stream($filename);
            } catch (Exception $exc) {
                $this->bootstrap(array('locale', 'translate'));
                die(sprintf($this->translate->_('Path %s not writable'), dirname($filename)));
            }
        }

        parent::__construct($writer);

        if (null !== $priority) {
            $this->setLogPriority($priority);
        }
    }

    /**
     * Set the log level
     * @param int $priority One
     * @param int One of the Zend_Log constants
     */
    public function setLogPriority($priority)
    {
        $writer = reset($this->_writers);

        if ($writer instanceof Zend_Log_Writer_Abstract) {
            $filter = new Zend_Log_Filter_Priority($priority);
            $writer->addFilter($filter);
        }

        return $this;
    }
}
