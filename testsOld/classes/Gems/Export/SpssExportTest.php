<?php

namespace Gems\Export;

class SpssExportTest extends \Gems_Test_DbTestAbstract
{

    protected function setUp()
    {
        parent::setUp();

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
        $classFile = str_replace('.php', '.yml', __FILE__);
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
        
        $export = $this->loader->getExport()->getExport('SpssExport');
        $options = $export->getDefaultFormValues();
        $export->setModel($model);
        $export->addExport($options);
        $file = $export->finalizeFiles();
        
        // Extract
        $sps = file_get_contents('zip://'. $file['file'] . '#test.sps');
        $dat = file_get_contents('zip://'. $file['file'] . '#test.dat');
        
        // Cleanup in case tests fail
        unlink($file['file']);
        
        // Check
        $expectedDat = file_get_contents(GEMS_TEST_DIR . '/data/export/test.dat');
        $expectedDat = preg_replace('~\r\n?~', "\n", $expectedDat);
        
        $this->assertEquals($dat, $expectedDat);
        
        $expectedSps = file_get_contents(GEMS_TEST_DIR . '/data/export/test.sps');
        $expectedSps = preg_replace('~\r\n?~', "\n", $expectedSps);
        $this->assertEquals($sps, $expectedSps);        
    }
}
