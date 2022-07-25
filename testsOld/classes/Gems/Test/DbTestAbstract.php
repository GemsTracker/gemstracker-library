<?php

/**
 *
 * Base test class for \Gems object test cases
 *
 * @package    Gems
 * @subpackage Test
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Test;

/**
 * Base test class for \Gems object test cases that involve a database test
 *
 * @package    Gems
 * @subpackage Test
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */
abstract class DbTestAbstract extends \Zend_Test_PHPUnit_DatabaseTestCase
{
    /**
     *
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected $_connectionMock;

    /**
     * @var \Zend_Application
     */
    protected $bootstrap;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db = null;

    /**
     * @var \Gems\Loader
     */
    protected $loader = null;

    /**
     * Returns the test database connection.
     *
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        if($this->_connectionMock == null) {
            $connection = \Zend_Db::factory('Pdo_Sqlite', array('dbname' => ':memory:', 'username' => 'test'));

            if ($sqlFiles = $this->getInitSql()) {
                foreach ($sqlFiles as $file) {
                    $sql  = file_get_contents($file);
                    $statements = explode(';', $sql);
                    foreach($statements as $sql) {
                        if (!strpos(strtoupper($sql), 'INSERT INTO') && !strpos(strtoupper($sql), 'INSERT IGNORE')
                            && !strpos(strtoupper($sql), 'UPDATE ')) {
                            $stmt = $connection->query($sql);
                        }
                    }
                }
            }
            $this->_connectionMock = $this->createZendDbConnection(
                $connection, 'zfunittests'
            );
            \Zend_Db_Table_Abstract::setDefaultAdapter($connection);
        }

        return $this->_connectionMock;
    }

    protected function getInitSql()
    {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        return array($path . 'sqllite/create-lite.sql');
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setUpApplication();

        parent::setUp();

        $this->db   = $this->getConnection()->getConnection();
        $connection = $this->db->getConnection();

        // Now add some utility functions that sqlite does not have. Copied from
        // Drupal: https://github.com/drupal/drupal/blob/8.4.x/core/lib/Drupal/Core/Database/Driver/sqlite/Connection.php
        /* @var $connection \PDO */
        $connection->sqliteCreateFunction('CHAR_LENGTH', 'strlen');
        $connection->sqliteCreateFunction('concat', array(__CLASS__, 'sqlFunctionConcat'));
        $connection->sqliteCreateFunction('concat_ws', array(__CLASS__, 'sqlFunctionConcatWs'));

        $container = $this->bootstrap->getBootstrap()->getContainer();

        // Pre init db, then set this connection to SQLite db, as well as the registry
        $this->bootstrap->bootstrap('db');
        $container->db = $this->db;
        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        // Run bootstrap
        $this->bootstrap->bootstrap();

        // Removing caching as this screws up tests
        $this->bootstrap->bootstrap('cache');
        $this->bootstrap->getBootstrap()->getContainer()->cache = \Zend_Cache::factory(
            'Core',
            'Static',
            ['caching' => false],
            ['disable_caching' => true]
        );

        $this->loader = $container->loader;

        // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $datetimeFormOptions['dateFormat']   = 'dd-MM-yyyy HH:mm';
        $timeFormOptions['dateFormat']   = 'HH:mm';

        \MUtil\Model\Bridge\FormBridge::setFixedOptions(array(
                                                            'date'     => $dateFormOptions,
                                                            'datetime' => $datetimeFormOptions,
                                                            'time'     => $timeFormOptions,
                                                        ));
    }

    protected function setUpApplication()
    {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        $iniFile = APPLICATION_PATH . '/configs/application.example.ini';

        if (!file_exists($iniFile)) {
            $iniFile = APPLICATION_PATH . '/configs/application.ini';
        }

        // Use a database, can be empty but this speeds up testing a lot
        $config = new \Zend_Config_Ini($iniFile, 'testing', true);
        $config->merge(new \Zend_Config([
                                            'resources' => [
                                                'db' => [
                                                    'adapter' => 'Pdo_Sqlite',
                                                    'params'  => [
                                                        'dbname'   => ':memory:',
                                                        'username' => 'test'
                                                    ]
                                                ]
                                            ]
                                        ]));

        // Add our test loader dirs
        $dirs               = $config->loaderDirs->toArray();
        $config->loaderDirs = [GEMS_PROJECT_NAME_UC => GEMS_TEST_DIR . "/classes/" . GEMS_PROJECT_NAME_UC] +
            $dirs;

        // Create application, bootstrap, and run
        $this->bootstrap  = new \Zend_Application(APPLICATION_ENV, $config);
    }

    /**
     * SQLite compatibility implementation for the CONCAT() SQL function.
     */
    public static function sqlFunctionConcat() {
        $args = func_get_args();
        return implode('', $args);
    }

    /**
     * SQLite compatibility implementation for the CONCAT_WS() SQL function.
     *
     * @see http://dev.mysql.com/doc/refman/5.6/en/string-functions.html#function_concat-ws
     */
    public static function sqlFunctionConcatWs() {
        $args = func_get_args();
        $separator = array_shift($args);
        // If the separator is NULL, the result is NULL.
        if ($separator === FALSE || is_null($separator)) {
            return NULL;
        }
        // Skip any NULL values after the separator argument.
        $args = array_filter($args, function ($value) {
            return !is_null($value);
        });
        return implode($separator, $args);
    }
}