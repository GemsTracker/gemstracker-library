<?php

namespace Gems\Export;

class RExportTest extends \Gems\Test\DbTestAbstract
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

        $this->fieldmap = new \Gems\Tracker\Source\LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix, $cache, 1);
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
            ],
            [
                'startdate' => '2018-08-10',
                'submitdate' => '2018-08-10',
                'datestamp' => '2018-08-10',
                'text' => 'some \'text about a patiënt',
                'list' => 1,
                'list2' => 'a'                
            ]
        ];
        $model = new \Gems\Model\PlaceholderModel('test', $array, $data); 
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
