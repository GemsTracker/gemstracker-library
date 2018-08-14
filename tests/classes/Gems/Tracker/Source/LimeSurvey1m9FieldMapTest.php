<?php

class Gems_Tracker_Source_LimeSurvey1m9FieldMapTest extends \Gems_Test_DbTestAbstract {

    protected function setUp() {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        // Create application, bootstrap, and run
        $application = new \Zend_Application(
                APPLICATION_ENV, GEMS_ROOT_DIR . '/configs/application.example.ini'
        );

        $this->bootstrap = $application;

        parent::setUp();

        $this->bootstrap->bootstrap('db');
        $this->bootstrap->getBootstrap()->getContainer()->db = $this->db;

        $this->bootstrap->bootstrap();

        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $sourceSurveyId = 1;
        $language       = 'en';
        $lsDb           = $this->db;
        $translate      = $this->getTranslate();
        $tablePrefix    = '';
        $cache          = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        $this->cache    = $cache;

        $this->fieldmap = new \Gems_Tracker_Source_LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix, $cache);
        
    }

    /**
     * Returns the test dataset xml of the same name as the test
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet() {
        $classFile = str_replace('.php', '.yml', __FILE__);
        return new PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                $classFile
        );
    }

    protected function getInitSql() {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        return array($path . 'sqllite/create-lite-ls.sql');
    }

    protected function getTranslate() {
        $english = array(
            'test' => 'test',
        );

        $translate = new \Zend_Translate(
                array(
            'adapter' => 'array',
            'content' => $english,
            'locale'  => 'en'
                )
        );

        return $translate;
    }

    public function providerTestSurveyModelDateStorageFormat() {
        return array(
            array('date01', 'yyyy-MM-dd HH:mm:ss'), // Limesurvey 2.0 default date
            array('date02', 'yyyy-MM-dd'), // Limesurvey legacy date
            array('date03', 'yyyy-MM-dd HH:mm:ss'), // Limesurvey 2.0 date with no time in Date Format
            array('date04', 'yyyy-MM-dd HH:mm:ss'), // Limesurvey 2.0 date with only time in Date Format
        );
    }

    /**
     * @param string $fieldName the name of the field in the model
     * @param string $expectedStorageFormat the Expected storageFormat value
     *
     * @dataProvider providerTestSurveyModelDateStorageFormat
     */
    public function testSurveyModelDateStorageFormat($fieldName, $expectedStorageFormat) {
        // Create a simple array model to apply to fieldmap to
        $array = array('test' => 123);
        $model = new \Gems_Model_PlaceholderModel('test', $array);
        $this->fieldmap->applyToModel($model);

        $this->assertEquals($expectedStorageFormat, $model->get($fieldName, 'storageFormat'));
    }

    /**
     * Test is list with only numeric options are presented as numeric
     * 
     * The database format is string, but limesurvey exports pure numeric list as numeric to spss
     * to mimic this we change the type if we can
     */
    public function testNumericOptions() {
        // Create a simple array model to apply to fieldmap to
        $array = array('test' => 123);
        $model = new \Gems_Model_PlaceholderModel('test', $array);
        $this->fieldmap->applyToModel($model);
        
        $this->assertEquals(\MUtil_Model::TYPE_NUMERIC, $model->get('list', 'type'));
        $this->assertEquals(\MUtil_Model::TYPE_STRING, $model->get('list2', 'type'));
    }
    
    /**
     * Test is list with only numeric options are presented as numeric
     * 
     * The database format is string, but limesurvey exports pure numeric list as numeric to spss
     * to mimic this we change the type if we can
     */
    public function testExpressionHelpIsQuestion() {
        // Create a simple array model to apply to fieldmap to
        $array = array('test' => 123);
        $model = new \Gems_Model_PlaceholderModel('test', $array);
        $this->fieldmap->applyToModel($model);
        
        $this->assertEquals('Expression question', $model->get('expression', 'label'));
    }

}
