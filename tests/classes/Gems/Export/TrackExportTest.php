<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Export;

/**
 * Description of TrackExportTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class TrackExportTest extends \Gems_Test_DbTestAbstract {
    
    /**
     *
     * @var \Zend_Application
     */
    protected $application;
    
    protected function fixUser()
    {
        // Fix user
        $escort              = \GemsEscort::getInstance();
        $escort->currentUser = 1;
    }
    
    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        //Dataset TokenTest.xml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.xml', __FILE__);
        return $this->createFlatXMLDataSet($classFile);
    }
    
    public function setUp()
    {
        parent::setUp();

        $this->setUpApplication();        
        $this->fixUser();
        
        $util = $this->loader->getUtil();
        \Zend_Registry::getInstance()->set('util', $util);
        
        $settings = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $project = new \Gems_Project_ProjectSettings($settings);
        \Zend_Registry::getInstance()->set('project', $project);
        
        $translate = new \MUtil_Translate_Adapter_Potemkin();
        \Zend_Registry::getInstance()->set('translate', $translate);
        
        $cache      = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        \Zend_Registry::getInstance()->set('cache', $cache);
    }    

    protected function setUpApplication()
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
        $dirs               = $config->loaderDirs->toArray();
        $config->loaderDirs = [GEMS_PROJECT_NAME_UC => GEMS_TEST_DIR . "/classes/" . GEMS_PROJECT_NAME_UC] +
                $dirs;

        // Create application, bootstrap, and run
        $this->application = new \Zend_Application(APPLICATION_ENV, $config);
    }
    
    /**
     * @see \Gems\Tracker\Snippets\ExportTrackSnippetAbstract::getExportBatch()
     */
    public function testExport()
    {
        $trackId = 1;
        $batch = $this->loader->getTaskRunnerBatch('track_export_' . $trackId);
        $formData = [
            'orgs' => 1,
            'fields' => ['f__1', 'f__2', 'f__3', 'a__4'],
            'rounds' => [10, 20, 30, 40],
            'surveys' => [1,2]
        ];        
        $filename = \MUtil_File::createTemporaryIn(GEMS_ROOT_DIR . '/var/tmp/export/track');
        
        $batch->setSessionVariable('filename', $filename);

        // Do not include this, to leave out the version dependency
        //$batch->addTask('Tracker\\Export\\ProjectVersionExportTask');

        $batch->addTask(
                'Tracker\\Export\\MainTrackExportTask',
                $trackId,
                $formData['orgs']
                );

        
        foreach ($formData['fields'] as $fieldId) {
            $batch->addTask(
                    'Tracker\\Export\\TrackFieldExportTask',
                    $trackId,
                    $fieldId
                    );
        }
        

        foreach ($formData['surveys'] as $surveyId) {
            $batch->addTask(
                    'Tracker\\Export\\TrackSurveyExportTask',
                    $trackId,
                    $surveyId
                    );
        }
        
        $batch->addTask(
                'Tracker\\Export\\TrackRoundConditionExportTask',
                $trackId
                );

        foreach ($formData['rounds'] as $roundId) {
            $batch->addTask(
                    'Tracker\\Export\\TrackRoundExportTask',
                    $trackId,
                    $roundId
                    );
        }
    

        $batch->setVariable('file', fopen($filename, 'a'));
        $batch->runAll();
        
        // Verify and cleanup temp file
        $expected = GEMS_TEST_DIR . '/data/export/TrackExportTest.txt';
        fclose($batch->getVariable('file'));
        $this->assertFileEquals($expected, $filename);
        unlink($filename);        
    }
}
