<?php

namespace Gems\Controller;

use ControllerTestAbstract;

class IndexControllerTest extends ControllerTestAbstract
{
    /**
     * @var int
     */
    public $organizationIdNr = 0;
    
    // We check that the program runs without an initialised database
    public $useDatabase = false;

    public function setUp()
    {
        $this->setPath(GEMS_TEST_DIR . '/data/controller');
        parent::setUp();
    }

    /**
     */
    public function testSaltRequired()
    {
        if (interface_exists('Throwable')) {
            $this->dispatch('/');
            $response = $this->getFrontController()->getResponse();
            $exception = $response->getExceptionByMessage("Missing required project setting: 'salt'.");
            $this->assertTrue(count($exception) == 1);
        } else {
            $this->markTestSkipped("Test cannot be run in PHP versions < 7.0.");
        }
    }

    /**
     *
     */
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
        // echo __FUNCTION__ . "\n" . $this->getResponse()->getBody();
        $this->assertController('index');
        $this->assertAction('login');
    }

    /**
     * 
     * @throws \Zend_Cache_Exception
     * @throws \Zend_Controller_Exception
     */
    public function testValidProjectLogin()
    {
        $this->_fixSetup();
        
        $postVars = array(
            'organization' => \Gems_User_UserLoader::SYSTEM_NO_ORG,
            'userlogin'    => 'superadmin',  // Valid login, this comes from project.ini in new-project
            'password'     => 'superadmin',
            'button'       => 'Login'           // Submit button / label come from Gems_User_Form_LoginForm
            );
        
        $this->getRequest()->setMethod('POST')->setPost($postVars);
        $this->dispatch('/index/login');

        // echo __FUNCTION__ . "\n" . $this->getResponse()->getBody();
        
        $this->getResponse();
        $this->assertRedirect('Valid project login not accepted');
    }

    /**
     * @throws \Zend_Controller_Exception
     */
    public function testInvalidProjectLogin()
    {
        $this->_fixSetup();
        $postVars = array(
            'organization' => \Gems_User_UserLoader::SYSTEM_NO_ORG,
            'userlogin'    => 'superadmin',
            'password'     => 'superpassword', // This is wrong
            'submit'       => 'Login'
            );
        $this->getRequest()->setMethod('POST')->setPost($postVars);

        $this->dispatch('/index/login');
        // echo __FUNCTION__ . "\n" . $this->getResponse()->getBody();
        
        $this->getResponse();
        $this->assertNotRedirect('Invalid project login accepted');
    }
}
