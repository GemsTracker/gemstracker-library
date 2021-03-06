<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Controller;

use ControllerTestAbstract;

/**
 * Description of RespondentControllerTest
 *
 * @author mdekk
 */
class RespondentControllerTest extends ControllerTestAbstract {

    public function setUp() {
        $this->setPath(GEMS_TEST_DIR . '/data/controller');
        parent::setUp();

        $this->_fixSetup();
        $this->_fixUser();
    }

    /**
     * Test inserting a new patient
     */
    public function testSimplaApi1()
    {
        $params = [
            'gr2o_patient_nr'      => '1001303',
            'gr2o_id_organization' => 70,
            'gr2o_reception_code'  => 'OK',
            'grs_iso_lang'         => 'NL',
            'grs_first_name'       => 'Ria',
            'grs_surname_prefix'   => 'van',
            'grs_last_name'        => 'Veen-de Vries',
            'grs_gender'           => 'F',
            'grs_birthday'         => '1965-04-11',
            'grs_ssn'              => '',
            'gr2o_email'            => 'email@email.com',
            // Just for the unit test as we don't have a default value
            'gr2o_opened_by'       => '0',
        ];
        $req = $this->getRequest();
        $req->setParams($params);
        $this->dispatch('/respondent/simple-api');
        $body = $this->getResponse()->getBody();
        $this->assertEquals('Changes to patient 1001303 saved.', $body, 'simple-api: Inserting a new patient failed');
        //Save this for the next test
        //$this->saveTables(['gems__respondents', 'gems__respondent2org'], 'RespondentControllerTest_testSimpleApi2');
    }

    /**
     * Check that no changes will be saved when use is up to date
     */
    public function testSimpleApi2()
    {
        $params = [
            'gr2o_patient_nr'      => '1001303',
            'gr2o_id_organization' => 70,
            'gr2o_reception_code'  => 'OK',
            'grs_iso_lang'         => 'NL',
            'grs_first_name'       => 'Ria',
            'grs_surname_prefix'   => 'van',
            'grs_last_name'        => 'Veen-de Vries',
            'grs_gender'           => 'F',
            'grs_birthday'         => '1965-04-11',
            'grs_ssn'              => '',
            'gr2o_email'            => 'email@email.com',
            // Just for the unit test as we don't have a default value
            'gr2o_opened_by'       => '0',
        ];
        $req = $this->getRequest();
        $req->setParams($params);
        $this->dispatch('/respondent/simple-api');
        $body = $this->getResponse()->getBody();
        $this->assertEquals('No changes to patient 1001303.', $body, 'simple-api: unchanged patient failed');
        //$this->saveTables(['gems__respondents', 'gems__respondent2org'], 'RespondentControllerTest_testSimpleApi3');
    }

    /**
     * Check that changes will be saved when name hase changed
     */
    public function testSimpleApi3()
    {
        $params = [
            'gr2o_patient_nr'      => '1001303',
            'gr2o_id_organization' => 70,
            'gr2o_reception_code'  => 'OK',
            'grs_iso_lang'         => 'NL',
            'grs_first_name'       => 'Ria',
            'grs_surname_prefix'   => 'de',
            'grs_last_name'        => 'Vries',
            'grs_gender'           => 'F',
            'grs_birthday'         => '1965-04-11',
            'grs_ssn'              => '',
            'gr2o_email'            => 'email@email.com',
            // Just for the unit test as we don't have a default value
            'gr2o_opened_by'       => '0',
        ];
        $req = $this->getRequest();
        $req->setParams($params);
        $this->dispatch('/respondent/simple-api');
        $body = $this->getResponse()->getBody();
        $this->assertEquals('Changes to patient 1001303 saved.', $body, 'simple-api: Changed patient failed');
        //$this->saveTables(['gems__respondents', 'gems__respondent2org'], 'RespondentControllerTest_testSimpleApi3');
    }

    /**
     * Check the merge function of the simple api
     *
     * When only old or new number exists, just update the pid if needed
     * When both exist, make sure all related records are merged
     *
     * @dataProvider MergeProvider
     */
    public function testSimpleApiMerge($oldPid, $newPid, $result)
    {
        $params = [
            'gr2o_patient_nr'      => $newPid,
            'gr2o_id_organization' => 70,
            'gr2o_reception_code'  => 'OK',
            'grs_iso_lang'         => 'NL',
            'grs_first_name'       => 'Ria',
            'grs_surname_prefix'   => 'de',
            'grs_last_name'        => 'Vries',
            'grs_gender'           => 'F',
            'grs_birthday'         => '1965-04-11',
            'grs_ssn'              => '',
            'gr2o_email'            => 'email@email.com',
            // Just for the unit test as we don't have a default value
            'gr2o_opened_by'       => '0',
        ];

        $params['oldpid'] = $oldPid;
        $req = $this->getRequest();
        $req->setParams($params);
        $this->dispatch('/respondent/simple-api');
        $body = $this->getResponse()->getBody();
        $this->assertContains($result, $body, 'simple-api: Merge patient failed');
        //$this->saveTables(['gems__respondents', 'gems__respondent2org'], 'RespondentControllerTest_testSimpleApi3');
    }

    public function mergeProvider()
    {
        return [
            // oldpid ,  newPid,   expectedResult
            ['1001304', '1001303', '1001304 merged to 1001303'],  // both pids exist
            ['1001305', '1001303', '1001305 not found, nothing to merge'],  // non existing old pid
            ['1001303', '1001305', '1001303 renamed to 1001305'],  // non existing new pid
        ];
    }

}
