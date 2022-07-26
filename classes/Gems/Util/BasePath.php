<?php

/**
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

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
class BasePath
{
    /**
     *
     * @var String
     */
    protected $basePath = null;

    /**
     *
     * @return string
     */
    public function  __toString()
    {
        return $this->getBasePath();
    }

    /**
     *
     * @return string
     */
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

    /**
     *
     * @return string The base path if it is more then '/' , null otherwise
     */
    public function getBasePathIfExists()
    {
        $basepath = $this->getBasePath();

        if ('/' == $basepath) {
            return null;
        }

        return $basepath;
    }

    /**
     *
     * @param string $path
     * @return $this
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;

        return $this;
    }
}
