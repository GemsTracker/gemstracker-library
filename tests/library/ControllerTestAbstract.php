<?php
class ControllerTestAbstract extends \Zend_Test_PHPUnit_ControllerTestCase
{
    public function setUp()
    {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        // Create application, bootstrap, and run
        $application = new \Zend_Application(
            APPLICATION_ENV,
            GEMS_ROOT_DIR . '/configs/application.example.ini'
        );

        $this->bootstrap = $application;

        parent::setUp();
    }

    /**
     * Here we fix the intentional errors that are in de default setup
     *
     * At the moment we only set a salt in the project resource
     */
    protected function _fixSetup() {
        $project = $this->bootstrap->getBootstrap()->getResource('project');
        $project->salt = 'TESTCASE';
    }
}
