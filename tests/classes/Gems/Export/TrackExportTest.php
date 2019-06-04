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
    
    /**
     * For now mostly copied from ImportTrackSnippetAbstract
     * @param type $filename
     * @return type
     */
    protected function loadImportData($filename)
    {
        $sections = array(
            'version'       => false,
            'track'         => false,
            'organizations' => false,
            'fields'        => true,
            'surveys'       => false,
            'conditions'    => false,
            'rounds'        => false,
            );
        $content  = file_get_contents($filename);

        $fieldsCount = 0;
        $fieldsNames = false;
        $fieldsReset = false;
        $key         = false;
        $lineNr      = 0;

        foreach (explode("\r\n", $content) as $line) {
            $lineNr++;
            if ($line) {
                if (strpos($line, "\t") === false) {
                    $key         = strtolower(trim($line));
                    $fieldsNames = false;
                    $fieldsReset = false;

                    if (isset($sections[$key])) {
                        $fieldsReset = $sections[$key];
                    } else {
                        $this->_session->importData['errors'][] = sprintf(
                                $this->_('Unknown data type identifier "%s" found at line %s.'),
                                trim($line),
                                $lineNr
                                );
                        $key = false;
                    }

                } else {
                    $raw = explode("\t", $line);

                    if ($fieldsNames) {
                        if (count($raw) === $fieldsCount) {
                            $data = array_combine($fieldsNames, $raw);
                            $this->_session->importData[$key][$lineNr] = $data;
                        } else {
                            $this->_session->importData['errors'][] = sprintf(
                                    $this->_('Incorrect number of fields at line %d. Found %d while %d expected.'),
                                    $lineNr,
                                    count($raw),
                                    $fieldsCount
                                    );
                        }
                        if ($fieldsReset) {
                            $fieldsNames = false;
                        }
                    } else {
                        $fieldsNames = $raw;
                        $fieldsCount = count($fieldsNames);
                    }
                }
            }
        }
        return $this->_session->importData;
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
        
        \Zend_Controller_Front::getInstance()->setRequest(new \Zend_Controller_Request_HttpTestCase());
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
    
    /**
     * Import the track we exported in the previous test
     */
    public function testImport()
    {
        $trackId  = 1;
        $batch    = $this->loader->getTaskRunnerBatch('track_import_create_' . $trackId);
        $filename = GEMS_TEST_DIR . '/data/export/TrackExportTest.txt';
        $import   = $this->loadImportData($filename);
        $formData = [
            'gtr_track_name' => 'Copy',
            'gtr_organizations' => [1]
            ];

        $batch->setSessionVariable('import', $import);
        
        // FIRST Check tasks        
        $batch->addTask(
                'Tracker\\Import\\CheckTrackImportTask',
                $import['track']
                );

        foreach ($import['organizations'] as $lineNr => $organizationData) {
            $batch->addTask(
                    'Tracker\\Import\\CheckTrackOrganizationImportTask',
                    $lineNr,
                    $organizationData
                    );
        }

        foreach ($import['fields'] as $lineNr => $fieldData) {
            $batch->addTask(
                    'Tracker\\Import\\CheckTrackFieldImportTask',
                    $lineNr,
                    $fieldData
                    );
        }

        foreach ($import['surveys'] as $lineNr => $surveyData) {
            $batch->addTask(
                    'Tracker\\Import\\CheckTrackSurveyImportTask',
                    $lineNr,
                    $surveyData
                    );
        }

        foreach ($import['conditions'] as $lineNr => $conditionData) {
            $batch->addTask(
                    'Tracker\\Import\\CheckTrackRoundConditionImportTask',
                    $lineNr,
                    $conditionData
                    );
        }

        foreach ($import['rounds'] as $lineNr => $roundData) {
            $batch->addTask(
                    'Tracker\\Import\\CheckTrackRoundImportTask',
                    $lineNr,
                    $roundData
                    );
        }
            
        // THEN create Tasks
        $batch->addTask(
                'Tracker\\Import\\CreateTrackImportTask',
                $formData
                );

        foreach ($import['fields'] as $lineNr => $fieldData) {
            $batch->addTask(
                    'Tracker\\Import\\CreateTrackFieldImportTask',
                    $lineNr,
                    $fieldData
                    );
        }

        foreach ($import['conditions'] as $lineNr => $conditionData) {
            $batch->addTask(
                    'Tracker\\Import\\CreateTrackRoundConditionImportTask',
                    $lineNr,
                    $conditionData
                    );
        }

        foreach ($import['rounds'] as $lineNr => $roundData) {
            $batch->addTask(
                    'Tracker\\Import\\CreateTrackRoundImportTask',
                    $lineNr,
                    $roundData
                    );
        }
                
        $batch->runAll();
        
        // To see what was created, no actual test yet
        //$this->saveTables(['gems__tracks', 'gems__track_fields', 'gems__conditions', 'gems__rounds'], 'import');
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
            $path      = GEMS_TEST_DIR . '/data';
            $dataset = new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet($data);
            \PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet::write($dataset, $path . DIRECTORY_SEPARATOR . $filename . '.xml');
        }
    }
}
