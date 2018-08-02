<?php

class ControllerTestAbstract extends \Zend_Test_PHPUnit_ControllerTestCase
{
    /**
     *
     * @var Set to no to work without an initilised database
     */
    public $useDatabase = true;

    /**
     *
     * @var int
     */
    public $userIdNr = 70;

    protected function _fixUser()
    {
        $gems       = \GemsEscort::getInstance();
        $loader     = $gems->getLoader();
        $userLoader = $loader->getUserLoader();
        $defName    = \Gems_User_UserLoader::USER_CONSOLE;
        $definition = $userLoader->getUserDefinition($defName);

        $values = $definition->getUserData('unittest', $this->userIdNr);
        $values['user_locale'] = 'en';

        $values = $userLoader->ensureDefaultUserValues($values, $definition, $defName);
        $user   = new \Gems_User_User($values, $definition);
        $user->answerRegistryRequest('basepath', $gems->basepath);
        $user->answerRegistryRequest('db', $gems->db);
        $user->answerRegistryRequest('loader', $loader);
        $user->answerRegistryRequest('locale', $gems->locale);
        $user->answerRegistryRequest('session', $gems->session);
        $user->answerRegistryRequest('translateAdapter', $gems->translateAdapter);
        $user->answerRegistryRequest('userLoader', $userLoader);
        $user->answerRegistryRequest('util', $loader->getUtil());

        $userLoader->setCurrentUser($user);
        // Do deep injection in all relevant parts
        $gems->currentUser                 = $user;                    // Copied to controller
        $gems->getContainer()->currentUser = $user;
        $gems->getContainer()->currentuser = $user;
    }

    /**
     * Setup test database and load fixture
     */
    protected function _setupDatabase()
    {
        $db = $this->bootstrap->getBootstrap()->getPluginResource('db')->getDbAdapter();

        \Zend_Db_Table_Abstract::setDefaultAdapter($db);
        $connection = $db->getConnection();

        // Now add some utility functions that sqlite does not have. Copied from
        // Drupal: https://github.com/drupal/drupal/blob/8.4.x/core/lib/Drupal/Core/Database/Driver/sqlite/Connection.php
        /* @var $connection \PDO */
        $connection->sqliteCreateFunction('adddate', array(__CLASS__, 'sqlFunctionAddDate'));
        $connection->sqliteCreateFunction('char_length', array(__CLASS__, 'sqlFunctionCharLength'));
        $connection->sqliteCreateFunction('concat', array(__CLASS__, 'sqlFunctionConcat'));
        $connection->sqliteCreateFunction('concat_ws', array(__CLASS__, 'sqlFunctionConcatWs'));

        if ($sqlFiles = $this->getInitSql()) {
            foreach ($sqlFiles as $file) {
                $sql = file_get_contents($file);
                $statements = explode(';', $sql);
                foreach ($statements as $sql) {
                    if (!strpos(strtoupper($sql), 'INSERT INTO') && !strpos(strtoupper($sql), 'INSERT IGNORE') && !strpos(strtoupper($sql), 'UPDATE ')) {
                        if (!empty($sql)) {
                            $stmt = $db->exec($sql);
                        }
                    }
                }
            }
        }

        $connection = new \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($db->getConnection());

        $dataSet = $this->getDataSet();

        if ($dataSet instanceof \PHPUnit_Extensions_Database_DataSet_IDataSet) {
            $setupOperation = \PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
            $setupOperation->execute($connection, $dataSet);
        }
    }

    /**
     * Here we fix the intentional errors that are in de default setup
     *
     * At the moment we only set a salt in the project resource
     */
    protected function _fixSetup()
    {
        $project = $this->bootstrap->getBootstrap()->getResource('project');
        $project->salt = 'TESTCASE';
    }

    /**
     * Used to setup database for an indivudual testcase
     *
     * Will use <classname>_<testname>.xml or <classname>.xml
     *
     * @return \PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet | null
     */
    protected function getDataSet()
    {
        $path      = $this->getPath();
        $testcase  = $this->getName(false);
        // Just basename fails on linux systems
        $classParts = explode('\\', get_class($this));
        $className = end($classParts);
        $classFile = $path . DIRECTORY_SEPARATOR . $className . '.xml';
        $testFile  = $path . DIRECTORY_SEPARATOR . $className . '_' . $testcase . '.xml';
        if (file_exists($testFile)) {
            return new \PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($testFile);
        }
        if (file_exists($classFile)) {
            return new \PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($classFile);
        }

        return null;
    }

    /**
     * Return the files needed to setup the database
     *
     * @return array
     */
    protected function getInitSql()
    {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        return array($path . 'sqllite/create-lite.sql');
    }

    /**
     * Get the path to use for database files
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Helper function to create xml files to seed the database.
     *
     * @param array $tables    Array of tablenames to save
     * @param string $filename Filename to use, without .xml
     */
    protected function saveTables($tables, $filename)
    {
        $db = \Zend_Db_Table_Abstract::getDefaultAdapter();
        foreach ($tables as $table) {
            $results = $db->query(sprintf('select * from %s;', $table))->fetchAll();
            if ($results) {
                $data[$table] = $results;
            }
        }
        if ($data) {
            $path      = $this->getPath();
            $dataset = new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet($data);
            \PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet::write($dataset, $path . DIRECTORY_SEPARATOR . $filename . '.xml');
        }
    }

    /**
     * Get the path to use for database files
     *
     * @return string
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    public function setUp()
    {
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
        $dirs = $config->loaderDirs->toArray();
        $config->loaderDirs =
                [GEMS_PROJECT_NAME_UC => GEMS_TEST_DIR . "/classes/" . GEMS_PROJECT_NAME_UC] +
                $dirs;

        // Create application, bootstrap, and run
        $this->bootstrap = new \Zend_Application(APPLICATION_ENV, $config);

        if ($this->useDatabase){
            $this->_setupDatabase();
        }

        // Run the bootstrap
        parent::setUp();
    }

    /**
     * SQLite compatibility implementation for the MySQL ADDATE() SQL function, when not using INTERVAL syntax.
     */
    public static function sqlFunctionAddDate($dateTime, $days)
    {
        $date = new \DateTime($dateTime);

        $date->add(new \DateInterval('P' . $days . 'D'));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * SQLite compatibility implementation for the CHAR_LENGTH() SQL function.
     */
    public static function sqlFunctionCharLength($subject) {
        return mb_strlen($subject);
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