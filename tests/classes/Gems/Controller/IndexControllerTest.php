<?php

namespace Gems\Controller;

use ControllerTestAbstract;

class IndexControllerTest extends \ControllerTestAbstract
{
    // We check that the program runs without an initialised database
    public $useDatabase = false;

    public function setUp()
    {
        $this->setPath(GEMS_TEST_DIR . '/data/controller');
        parent::setUp();
    }

    public function testSaltRequired()
    {
        $this->dispatch('/');
        $reponse   = $this->getFrontController()->getResponse();
        $exception = $reponse->getExceptionByMessage("Missing required project setting: 'salt'.");
        $this->assertTrue(count($exception) == 1);
    }

    public function testHomeRedirectsToLogin()
    {
        $this->_fixSetup();
        $this->dispatch('/');
        $this->assertRedirectTo('/index/login');
    }

    public function testLoginPage()
    {
        $this->_fixSetup();
        $this->dispatch('/index/login');
        $this->assertController('index');
        $this->assertAction('login');
    }

    public function testValidProjectLogin()
    {
        $this->_fixSetup();
        $postVars = array(
            'organization' => '10',
            'userlogin'    => 'superadmin',  // Valid login, this comes from project.ini in newproject
            'password'     => 'superadmin',
            'button'       => 'Login'           // Submit button / label come from Gems_User_Form_LoginForm
            );
        $this->getRequest()->setMethod('POST')->setPost($postVars);
        $this->dispatch('/index/login');
        
        // echo $this->getResponse()->getBody();
        
        $response = $this->getResponse();
        $this->assertRedirect('Valid project login not accepted');
    }

    public function testInvalidProjectLogin()
    {
        $this->_fixSetup();
        $postVars = array(
            'organization'=>'10',
            'userlogin'=>'superadmin',
            'password'=>'superpassword', //This is wrong
            'submit'=>'Login'
            );
        $this->getRequest()->setMethod('POST')->setPost($postVars);

        $this->dispatch('/index/login');
        $response = $this->getResponse();
        $this->assertNotRedirect('Invalid project login accepted');
    }
}
