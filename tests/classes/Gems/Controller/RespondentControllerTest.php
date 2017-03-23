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

    protected function _fixUser()
    {
        $loader = \GemsEscort::getInstance()->getLoader();
        $userLoader = $loader->getUserLoader();
        $defName = \Gems_User_UserLoader::USER_CONSOLE;
        $definition = $userLoader->getUserDefinition($defName);

        $values = $definition->getUserData('unittest', 70);

        $values = $userLoader->ensureDefaultUserValues($values, $definition, $defName);
        $user = new \Gems_User_User($values, $definition);
        $user->answerRegistryRequest('userLoader', $userLoader);
        $user->answerRegistryRequest('session', \GemsEscort::getInstance()->session);
        $user->answerRegistryRequest('loader', $loader);
        $user->answerRegistryRequest('util', $loader->getUtil());
        $user->answerRegistryRequest('db', \Zend_Db_Table_Abstract::getDefaultAdapter());

        $userLoader->setCurrentUser($user);
        // Do deep injection in all relevant parts
        \GemsEscort::getInstance()->currentUser = $user;                    // Copied to controller
        \GemsEscort::getInstance()->getContainer()->currentUser = $user;
        \GemsEscort::getInstance()->getContainer()->currentuser = $user;
    }
    
    

    /**
     * Test inseeting a new patient
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
            'grs_email'            => 'email@email.com',
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
            'grs_email'            => 'email@email.com',
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
            'grs_email'            => 'email@email.com',
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

}
