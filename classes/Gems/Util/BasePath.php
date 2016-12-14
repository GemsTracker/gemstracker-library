<?php

/**
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This is a simple reference replacement class.
 *
 * As the basePath cannot be set during initialization, setting it to this class make sure it is
 * set in all copied replacement later.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Util_BasePath
{
    /**
     *
     * @var String
     */
    protected $basePath = null;

    public function  __toString()
    {
        return $this->getBasePath();
    }

    public function getBasePath()
    {
        if (null === $this->basePath) {
            if (defined('BASE_URL')) {
                $this->setBasePath(BASE_URL);
            } else {
                $front   = \Zend_Controller_Front::getInstance();
                $request = $front->getRequest();

                if ($request) {
                    $this->setBasePath($request->getBasePath());
                }
            }
        }

        return $this->basePath;
    }

    public function setBasePath($path)
    {
        $this->basePath = $path;

        return $this;
    }
}
