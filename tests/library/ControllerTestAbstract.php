<?php
class ControllerTestAbstract extends \Zend_Test_PHPUnit_ControllerTestCase
{
    public function setUp()
    {
        $iniFile = APPLICATION_PATH . '/configs/application.example.ini';

        if (!file_exists($iniFile)) {
            $iniFile = APPLICATION_PATH . '/configs/application.ini';
        }

        // Create application, bootstrap, and run
        $application = new \Zend_Application(APPLICATION_ENV, $iniFile);

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
