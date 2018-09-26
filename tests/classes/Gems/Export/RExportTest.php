<?php

namespace Gems\Export;

class RExportTest extends \Gems_Test_DbTestAbstract
{

    protected function setUp()
    {
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

        $this->fieldmap = new \Gems_Tracker_Source_LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix, $cache);
    }

    /**
     * Returns the test dataset xml of the same name as the test
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $classFile = __DIR__ . '/SpssExportTest.yml';
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                $classFile
        );
    }

    protected function getInitSql()
    {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        return array($path . 'sqllite/create-lite-ls.sql');
    }

    protected function getTranslate()
    {
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
    
    public function testExport()
    {
        // Create a simple array model to apply to fieldmap to
        $array = array('test' => 123);
        $data  = [
            [
                'startdate' => '2018-08-10',
                'submitdate' => '2018-08-10',
                'datestamp' => '2018-08-10',
                'text' => 'some text',
                'list' => 1,
                'list2' => 'a'
            ]
        ];
        $model = new \Gems_Model_PlaceholderModel('test', $array, $data); 
        $this->fieldmap->applyToModel($model);
        
        $export = $this->loader->getExport()->getExport('RExport');
        $options = $export->getDefaultFormValues();
        $export->setModel($model);
        $export->addExport($options);
        $file = $export->finalizeFiles();
        
        // Extract
        $syntax = file_get_contents('zip://'. $file['file'] . '#test.R');
        $data = file_get_contents('zip://'. $file['file'] . '#test.csv');
        //file_put_contents(GEMS_TEST_DIR . '/data/export/test.R', $syntax);
        //file_put_contents(GEMS_TEST_DIR . '/data/export/test.csv', $data);
        
        // Cleanup in case tests fail
        unlink($file['file']);
        
        // Check
        $expectedData = file_get_contents(GEMS_TEST_DIR . '/data/export/test.csv');
        $expectedData = preg_replace('~\r\n?~', "\n", $expectedData);
        
        $this->assertEquals($data, $expectedData);
        
        $expectedSyntax = file_get_contents(GEMS_TEST_DIR . '/data/export/test.R');
        $expectedSyntax = preg_replace('~\r\n?~', "\n", $expectedSyntax);
        $this->assertEquals($syntax, $expectedSyntax);        
    }
}
