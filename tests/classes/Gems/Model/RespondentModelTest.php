<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Model;

use Gems_Model_RespondentModel as RespondentModel;
use GemsEscort;
use MUtil_Model_AbstractModelTest as AbstractModelTest;
use Zend_Application;
use Zend_Config;
use Zend_Config_Ini;

/**
 * Description of RespondentModelTest
 *
 * @author 175780
 */
class RespondentModelTest extends AbstractModelTest {

    protected $allLanguages = ['en', 'nl'];

    /**
     *
     * @var RespondentModel
     */
    protected $model;
    protected $baseRecord = [
        'grs_id_user'           => '123',
        'grs_ssn'               => null,
        'grs_iso_lang'          => 'nl',
        'grs_first_name'        => null,
        'grs_last_name'         => null,
        'grs_gender'            => 'U',
        'grs_birthday'          => null,
        'grs_address_1'         => null,
        'grs_address_2'         => null,
        'grs_zipcode'           => null,
        'grs_city'              => null,
        'grs_iso_country'       => 'NL',
        'grs_phone_1'           => null,
        'grs_phone_2'           => null,
        'grs_changed'           => '2000-01-15',
        'grs_changed_by'        => '1',
        'grs_created'           => '2000-01-15',
        'grs_created_by'        => '1',
        'gr2o_patient_nr'       => 'o1p1',
        'gr2o_id_organization'  => '1',
        'gr2o_id_user'          => '123',
        'gr2o_email'             => null,
        'gr2o_mailable'         => '1',
        'gr2o_comments'         => null,
        'gr2o_consent'          => 'Unknown',
        'gr2o_reception_code'   => 'OK',
        'gr2o_opened'           => '2000-01-15',
        'gr2o_opened_by'        => '1',
        'gr2o_changed'          => '2000-01-15',
        'gr2o_changed_by'       => '1',
        'gr2o_created'          => '2000-01-15',
        'gr2o_created_by'       => '1',
        'grc_id_reception_code' => 'OK',
        'grc_description'       => '',
        'grc_success'           => '1',
        'grc_for_surveys'       => '1',
        'grc_redo_survey'       => '0',
        'grc_for_tracks'        => '1',
        'grc_for_respondents'   => '1',
        'grc_overwrite_answers' => '0',
        'grc_active'            => '1',
        'grc_changed'           => '2017-08-30 12:00:00',
        'grc_changed_by'        => '1',
        'grc_created'           => '2017-08-30 12:00:00',
        'grc_created_by'        => '1',
        'row_class'             => '',
        'resp_deleted'          => '0'
    ];

    public function setUp()
        {
        parent::setUp();

        $this->setUpApplication();

        $this->fixUser();

        $this->model = new RespondentModel();

        // So loader knows our db too
        GemsEscort::getInstance()->getContainer()->db = $this->getConnection()->getConnection();

        $this->model->answerRegistryRequest('currentUser', $this->currentUser);
        $this->model->answerRegistryRequest('loader', GemsEscort::getInstance()->loader);
        $this->model->afterRegistry();
    }

    protected function fixUser()
    {
        // Fix user
        $currentUser = $this->getMockBuilder('Gems_User_User')
                ->disableOriginalConstructor()
                ->getMock();
        $currentUser->expects($this->any())
                ->method('getGroup')
                ->will($this->returnValue(1));
        $currentUser->expects($this->any())
                ->method('getUserId')
                ->will($this->returnValue(1));

        GemsEscort::getInstance()->currentUser = $currentUser;
        $this->currentUser                     = $currentUser;
    }

    protected function setUpApplication() {
        $iniFile = APPLICATION_PATH . '/configs/application.example.ini';

        if (!file_exists($iniFile)) {
            $iniFile = APPLICATION_PATH . '/configs/application.ini';
        }

        // Use a database, can be empty but this speeds up testing a lot
        $config = new Zend_Config_Ini($iniFile, 'testing', true);
        $config->merge(new Zend_Config([
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
        $application = new Zend_Application(APPLICATION_ENV, $config);
        $application->bootstrap(['project', 'translate', 'util', 'loader']);
    }

    /**
     * The template file name to create the sql create and xml load names from.
     *
     * Just reutrn __FILE__
     *
     * @return string
     */
    protected function getTemplateFileName() {
        return __FILE__;
    }

    public function testLoad() {
        $record = $this->model->loadFirst(['gr2o_id_organization' => 1]);

        $this->assertEquals($this->baseRecord, $record);
    }

    public function testCopy2Org() {
        $fromOrg = 1;
        $fromPid = 'o1p1';
        $toOrg   = 2;
        $toPid   = 'o2p1';
        $this->model->copyToOrg($fromOrg, $fromPid, $toOrg, $toPid, true);
        $record  = $this->model->loadFirst(['gr2o_id_organization' => 2]);

        $baseRecord   = $this->baseRecord;
        $changeFields = [
            'gr2o_opened'  => '',
            'gr2o_changed' => '',
            'gr2o_created' => '',
        ];

        // First check if change fields are updated
        $expected = array_intersect_key($baseRecord, $changeFields);
        $actual   = array_intersect_key($record, $changeFields);
        $this->assertNotEquals($expected, $actual); // Make sure the fields are updated!

        // Now check if remaining fields match expectations
        $expected                         = array_diff_key($baseRecord, $changeFields);
        $expected['gr2o_id_organization'] = $toOrg;
        $expected['gr2o_patient_nr']      = $toPid;
        $actual                           = array_diff_key($record, $changeFields);
        $this->assertEquals($expected, $actual);
    }

    public function testCopy2OrgRespondentLogin()
    {
        $this->model->addLoginCheck();

        $fromOrg = 1;
        $fromPid = 'o1p2';
        $toOrg   = 2;
        $toPid   = 'o2p2';
        $oldRecord = $this->model->loadFirst(['gr2o_patient_nr' => $fromPid, 'gr2o_id_organization' => $fromOrg]);

        $this->model->copyToOrg($fromOrg, $fromPid, $toOrg, $toPid, true);
        $newRecord = $this->model->loadFirst(['gr2o_patient_nr' => $fromPid, 'gr2o_id_organization' => $fromOrg]);

        $this->assertEquals($oldRecord, $newRecord, 'Old record should be the same after copy');
    }

}
