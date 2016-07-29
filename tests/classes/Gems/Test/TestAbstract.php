<?php

/**
 *
 * Base test class for Gems object test cases
 *
 * @package    Gems
 * @subpackage Test
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Base test class for Gems object test cases
 *
 * @package    Gems
 * @subpackage Test
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
abstract class Gems_Test_TestAbstract extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Gems_Loader
     */
    protected $loader = null;

    /**
     * @var \Zend_Db
     */
    protected $db = null;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->db = \Zend_Db::factory('pdo_sqlite', array('dbname'=>':memory:'));

        \Zend_Registry::set('db', $this->db);

        $settings = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/application.example.ini', APPLICATION_ENV);
        $sa = $settings->toArray();
        $this->loader  = new \Gems_Loader(\Zend_Registry::getInstance(), $sa['loaderDirs']);

        \Zend_Registry::set('loader', $this->loader);

        $this->tracker = $this->loader->getTracker();

        \Zend_Registry::set('tracker', $this->tracker);
    }
}
