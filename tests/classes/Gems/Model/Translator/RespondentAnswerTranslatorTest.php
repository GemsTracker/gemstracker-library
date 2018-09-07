<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Model\Translator;

use ControllerTestAbstract;

/**
 * Description of AppointmentTranslatorTest
 *
 * @author 175780
 */
class RespondentAnswerTranslatorTest extends ControllerTestAbstract {

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Gems_Model_Translator_AppointmentTranslator
     */
    protected $object;
    
    public $userIdNr = 1;

    public function setUp() {
        $this->setPath(GEMS_TEST_DIR . '/data/model');
        parent::setUp();
        $this->_fixUser();

        $this->loader = $this->bootstrap->getBootstrap()->loader;

        \Zend_Registry::set('loader', $this->loader);

        $importLoader = $this->loader->getImportLoader();
        $translator   = 'resp';
        $translators  = $importLoader->getTranslators('answers');
        $object       = $translators[$translator];
        $object->answerRegistryRequest('loader', $this->loader);
        $object->afterRegistry();
        
        $this->object = $object;
    }

    /**
     * @return \MUtil_Model_ModelAbstract
     */
    protected function getAnswerModel() {
        $sourceSurveyId = 1;
        $language       = 'en';
        $lsDb           = \Zend_Db_Table_Abstract::getDefaultAdapter();
        $translate      = $this->getTranslate();
        $tablePrefix    = '';
        $cache          = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));

        $fieldmap = new \Gems_Tracker_Source_LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix, $cache);


        // Create a simple array model to apply to fieldmap to
        $array = array('test' => 123);
        $model = new \Gems_Model_PlaceholderModel('test', $array);
        $fieldmap->applyToModel($model);

        return $model;
    }

    protected function getInitSql() {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        $files   = parent::getInitSql();
        $files[] = $path . 'sqllite/create-lite-ls.sql';

        return $files;
    }

    /**
     * 
     * @return \Zend_Translate
     */
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

    public function testFilter() {
        $targetModel = $this->getAnswerModel();
        $this->object->setTargetModel($targetModel);

        $data = [
            [
                'patient_id'      => 'o1p1',
                'organization_id' => 1,
                'completion_date' => '2000-11-22T00:00:00',
                'num'             => 1
            ],
            [
                'patient_id'      => 'o1p1',
                'organization_id' => 1,
                'completion_date' => '2000-11-22 00:00:00',
                'num'             => 1
            ],
            [
                'patient_id'      => 'o1p1',
                'organization_id' => 1,
                'completion_date' => '2000-11-22',
                'num'             => 1
            ],
        ];

        $actual = $this->object->translateImport($data);

        foreach ($actual as &$row) {
            if ($row['completion_date'] instanceof \MUtil_Date) {
                $row['completion_date'] = $row['completion_date']->toString('yyyy-MM-dd HH:mm:ss');
            }
        }

        $expected = [
            [
                'patient_id'      => 'o1p1',
                'organization_id' => 1,
                'completion_date' => '2000-11-22 00:00:00',
                'num'             => 1,
                'track_id'        => null,
                'survey_id'       => null,
                'token'           => ''
            ],
            [
                'patient_id'      => 'o1p1',
                'organization_id' => 1,
                'completion_date' => '2000-11-22 00:00:00',
                'num'             => 1,
                'track_id'        => null,
                'survey_id'       => null,
                'token'           => ''
            ],
            [
                'patient_id'      => 'o1p1',
                'organization_id' => 1,
                'completion_date' => '2000-11-22 00:00:00',
                'num'             => 1,
                'track_id'        => null,
                'survey_id'       => null,
                'token'           => ''
            ],
        ];

        //$this->saveTables(['gems__agenda_staff'], 'AppointmentTranslatorTest');
        $this->assertEquals($expected, $actual);
    }
    
    public function testBatch()
    {
        $importLoader = $this->loader->getImportLoader();
        $importLoader->answerRegistryRequest('_orgCode', 'code');
        $importer     = $importLoader->getImporter('answers');
        $importer->setSourceFile(__DIR__ . DIRECTORY_SEPARATOR . 'answerimport.csv');
        $importer->setTargetModel($this->getAnswerModel());
        $importer->setImportTranslator($this->object);
        $this->object->setSurveyId(1);
        //$batch = $importer->getCheckWithImportBatches();
        $batch = $importer->getCheckAndImportBatch();
        $batch->runAll();
        /*
        if ($exceptions = $batch->getExceptions()) {
            var_dump($exceptions);
        }
         */
        $messages = $batch->getMessages();
        $this->assertEquals($messages['addedAnswers'], '4 tokens were imported as a new extra token.');
    }

}
