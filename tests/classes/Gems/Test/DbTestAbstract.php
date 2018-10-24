<?php

/**
 *
 * Base test class for Gems object test cases
 *
 * @package    Gems
 * @subpackage Test
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TestAbstract.php 925 2012-09-05 09:59:13Z mennodekker $
 */

/**
 * Base test class for Gems object test cases that involve a database test
 *
 * @package    Gems
 * @subpackage Test
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TestAbstract.php 925 2012-09-05 09:59:13Z mennodekker $
 */
abstract class Gems_Test_DbTestAbstract extends \Zend_Test_PHPUnit_DatabaseTestCase
{
    /**
     * @var \Gems_Loader
     */
    protected $loader = null;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db = null;

    /**
     *
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected $_connectionMock;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->db = $this->getConnection()->getConnection();
        $connection = $this->getConnection()->getConnection()->getConnection();

        // Now add some utility functions that sqlite does not have. Copied from
        // Drupal: https://github.com/drupal/drupal/blob/8.4.x/core/lib/Drupal/Core/Database/Driver/sqlite/Connection.php
        /* @var $connection \PDO */
        $connection->sqliteCreateFunction('concat', array(__CLASS__, 'sqlFunctionConcat'));
        $connection->sqliteCreateFunction('concat_ws', array(__CLASS__, 'sqlFunctionConcatWs'));

        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $settings = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/application.example.ini', APPLICATION_ENV);
        $sa = $settings->toArray();
        $this->loader  = new \Gems_Loader(\Zend_Registry::getInstance(), $sa['loaderDirs']);

        \Zend_Registry::set('loader', $this->loader);
    }

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