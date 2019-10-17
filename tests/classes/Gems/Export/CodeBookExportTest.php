<?php

namespace Gems\Export;

class CodeBookExportTest extends \Gems_Test_DbTestAbstract
{
    /**
     *
     * @var \Gems_Tracker_Source_LimeSurvey1m9FieldMap
     */
    private $fieldmap;
    
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

        $this->fieldmap = new \Gems_Tracker_Source_LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix, $cache, 1);
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
    
    protected function getModel($questionInformation, $locale = 'en')
    {
        $survey = $this->getMockBuilder('Gems_Tracker_Survey')
                ->disableOriginalConstructor()
                ->getMock();
        
        $survey->expects($this->any())
                ->method('getQuestionInformation')
                ->will($this->returnValue($questionInformation));
        
        $tracker = $this->getMockBuilder('Gems_Tracker')
                ->disableOriginalConstructor()
                ->getMock();
        
        $tracker->expects($this->any())
                ->method('getSurvey')
                ->will($this->returnValue($survey));
        
        $currentUser = $this->getMockBuilder('Gems_User_User')
                ->disableOriginalConstructor()
                ->getMock();
        
        $tracker->expects($this->any())
                ->method('getLocale')
                ->will($this->returnValue($locale));
               
        $model = new \Gems\Model\SurveyCodeBookModel(1);
        $model->answerRegistryRequest('tracker', $tracker);
        $model->answerRegistryRequest('currentUser', $currentUser);
        $model->afterRegistry();
        
        return $model;
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
        $questionInformation = $this->fieldmap->getQuestionInformation();
        $model = $this->getModel($questionInformation, 'en');
        
        $export = $this->loader->getExport()->getExport('StreamingExcelExport');
        $options = [ 'type' => 'StreamingExcelExport' ];
        $export->setModel($model);
        $export->addExport($options);
        $file = $export->finalizeFiles();
        
        // Extract
        $actual = file_get_contents('zip://'. $file['file'] . '#xl/worksheets/sheet1.xml');
        //file_put_contents(GEMS_TEST_DIR . '/data/export/codebook.xml', $actual);
        
        // Cleanup in case tests fail
        unlink($file['file']);
        // Dirty fix to clean the session for the next test
        $session = new \Zend_Session_Namespace('Gems\Export\ExportAbstract');
        $session->unsetAll();
                
        // Check
        $expectedData = file_get_contents(GEMS_TEST_DIR . '/data/export/codebook.xml');
        $expectedData = preg_replace('~\r\n?~', "\n", $expectedData);
        
        $this->assertEquals($actual, $expectedData);
    }
    
    public function testExportNoSurvey()
    {
        $questionInformation = [];
        $model = $this->getModel($questionInformation, 'en');
        
        $export = $this->loader->getExport()->getExport('StreamingExcelExport');
        $options = [ 'type' => 'StreamingExcelExport' ];
        $export->setModel($model);
        $export->addExport($options);
        $file = $export->finalizeFiles();
        
        // Extract
        $actual = file_get_contents('zip://'. $file['file'] . '#xl/worksheets/sheet1.xml');
        //file_put_contents(GEMS_TEST_DIR . '/data/export/codebook-empty.xml', $actual);
        
        // Cleanup in case tests fail
        unlink($file['file']);
        
        // Check
        $expectedData = file_get_contents(GEMS_TEST_DIR . '/data/export/codebook-empty.xml');
        $expectedData = preg_replace('~\r\n?~', "\n", $expectedData);
        
        $this->assertEquals($actual, $expectedData);
    }
}
