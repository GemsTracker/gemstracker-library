<?php

/**
 *
 * \Gems Base \Exception class
 *
 * @package    Gems
 * @subpackage Exception
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 * \Gems Base \Exception class
 *
 * @package    Gems
 * @subpackage Exception
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Exception extends \Zend_Exception
{
    /**
     * Optional extra information on the exception
     *
     * @var string
     */
    private $info;

    /**
     *
     * @param String $msg The message
     * @param int $code the HttpResponseCode for this exception
     * @param \Exception $previous
     * @param string $info Optional extra information on the exception
     */
    public function __construct($msg = '', $code = 200, \Exception $previous = null, $info = null)
    {
        parent::__construct($msg, $code, $previous);

        if ($info) {
            $this->setInfo($info);
        }
    }

    /**
     * Returns optional extra information in the exception
     *
     * @return String
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Optional extra information on the exception
     *
     * @param string $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }
}