<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CommtemplateModelTest
 *
 * @author 175780
 */
class CommtemplateModelTest extends MUtil\Model_AbstractModelTest
{

    protected $allLanguages = ['en', 'nl'];

    /**
     *
     * @var \Gems\Model\CommtemplateModel
     */
    protected $model;

    public function setUp()
    {
        parent::setUp();

        $this->setUpApplication();

        $this->fixUser();

        $this->model = new \Gems\Model\CommtemplateModel();
    }

    protected function fixUser()
    {
        // Fix user
        $escort              = \Gems\Escort::getInstance();
        $escort->currentUser = 1;
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
        $application = new \Zend_Application(APPLICATION_ENV, $config);
    }

    /**
     * The template file name to create the sql create and xml load names from.
     *
     * Just reutrn __FILE__
     *
     * @return string
     */
    protected function getTemplateFileName()
    {
        return __FILE__;
    }

    protected function addLoadTransformer()
    {
        $translationModel = new \MUtil\Model\TableModel('gems__comm_template_translations', 'gctt');

        $transformer  = new \MUtil\Model\Transform\RequiredRowsTransformer();
        $requiredRows = array();
        foreach ($this->allLanguages as $code) {
            $requiredRows[]['gctt_lang'] = $code;
        }

        $transformer->setRequiredRows($requiredRows);
        $translationModel->addTransformer($transformer);


        $this->model->addModel($translationModel, array('gct_id_template' => 'gctt_id_template'), 'gctt');
    }

    public function testLoad()
    {
        $record = $this->model->loadFirst();

        $this->assertEquals([
            'gct_id_template' => '1',
            'gct_name'        => 'abc',
            'gct_target'      => 'Staff',
            'gct_code'        => 'code',
            'gct_changed'     => '2000-01-15',
            'gct_changed_by'  => '1',
            'gct_created'     => '2000-01-15',
            'gct_created_by'  => '1'
                ], $record);
    }

    public function testLoadWithTransformer()
    {
        $this->addLoadTransformer();

        $record = $this->model->loadFirst();

        $this->assertEquals([
            'gct_id_template' => '1',
            'gct_name'        => 'abc',
            'gct_target'      => 'Staff',
            'gct_code'        => 'code',
            'gct_changed'     => '2000-01-15',
            'gct_changed_by'  => '1',
            'gct_created'     => '2000-01-15',
            'gct_created_by'  => '1',
            'gctt'            => [
                [
                    'gctt_id_template' => '1',
                    'gctt_lang'        => 'en',
                    'gctt_subject'     => 'subject_en',
                    'gctt_body'        => 'body_en'
                ], [
                    'gctt_id_template' => '1',
                    'gctt_lang'        => 'nl',
                    'gctt_subject'     => 'subject_nl',
                    'gctt_body'        => 'body_nl'
                ]
            ]
                ], $record);
    }

    public function testDelete()
    {
        $filter  = ['gct_id_template' => 1];
        $before  = $this->model->load($filter);
        $deleted = $this->model->delete($filter);
        $after   = $this->model->load($filter);
        $this->assertEquals([], $after);
    }

    public function testDeleteWithTransformer()
    {
        $this->addLoadTransformer();
        $filter    = ['gct_id_template' => 1];
        $subFilter = ['gctt_id_template' => 1];

        $deleted = $this->model->delete($filter);
        $after   = $this->model->load($filter);
        $this->assertEquals([], $after);

        $field       = $this->model->get('gctt');
        $nestedModel = $field['model'];
        $rows        = $nestedModel->load($subFilter);
        $this->assertEquals([
            [
                'gctt_id_template' => null,
                'gctt_lang'        => 'en',
                'gctt_subject'     => null,
                'gctt_body'        => null
            ], [
                'gctt_id_template' => null,
                'gctt_lang'        => 'nl',
                'gctt_subject'     => null,
                'gctt_body'        => null
            ]
                ], $rows);
    }

}
