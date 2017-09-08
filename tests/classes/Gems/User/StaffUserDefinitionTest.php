<?php

class StaffUserDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Password to test with
     */
    protected $testPassword = 'correcthorsebatterystaple';

    /**
     * @var \Gems_User_StaffUserDefinition
     */
    protected $userDefinition;

    public function setUp()
    {
        parent::setUp();

        $settings = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/application.example.ini', APPLICATION_ENV);
        $sa = $settings->toArray();
        $this->loader  = new \Gems_Loader(\Zend_Registry::getInstance(), $sa['loaderDirs']);
        $this->userDefinition = $this->loader->getUserLoader()->getUserDefinition('StaffUser');
    }

    public function testHashPassword()
    {
        $hash = $this->userDefinition->hashPassword($this->testPassword);

        $this->assertTrue(password_verify($this->testPassword, $hash));
    }

    public function testCredentialValidationCallback()
    {
        $hash = $this->userDefinition->hashPassword($this->testPassword);

        $this->userDefinition->checkOldHashes = false;
        $credentialValidationCallback = $this->userDefinition->getCredentialValidationCallback();

        $callbackResult = call_user_func($credentialValidationCallback, $hash, $this->testPassword);

        $this->assertTrue($callbackResult);
    }

    public function testCredentialValidationCallbackFalse()
    {
        $hash = $this->userDefinition->hashPassword('false');

        $this->userDefinition->checkOldHashes = false;
        $credentialValidationCallback = $this->userDefinition->getCredentialValidationCallback();

        $callbackResult = call_user_func($credentialValidationCallback, $hash, $this->testPassword);

        $this->assertFalse($callbackResult);
    }

    public function testCredentialValidationCallbackWithOldHashesCheck()
    {
        $hash = $this->userDefinition->hashPassword($this->testPassword);

        $this->userDefinition->checkOldHashes = true;
        $credentialValidationCallback = $this->userDefinition->getCredentialValidationCallback();

        $callbackResult = call_user_func($credentialValidationCallback, $hash, $this->testPassword);

        $this->assertTrue($callbackResult);
    }

    public function testCredentialValidationCallbackWithOldHashesCheckFalse()
    {
        $hash = $this->userDefinition->hashPassword('false');

        $this->userDefinition->checkOldHashes = true;
        $credentialValidationCallback = $this->userDefinition->getCredentialValidationCallback();

        $callbackResult = call_user_func($credentialValidationCallback, $hash, $this->testPassword);

        $this->assertFalse($callbackResult);
    }

    public function testCredentialValidationCallbackOldPasswords()
    {
        $hash = $this->userDefinition->hashOldPassword($this->testPassword);

        $this->userDefinition->checkOldHashes = true;
        $credentialValidationCallback = $this->userDefinition->getCredentialValidationCallback();

        $callbackResult = call_user_func($credentialValidationCallback, $hash, $this->testPassword);

        $this->assertTrue($callbackResult);
    }

    public function testCredentialValidationCallbackOldPasswordsFalse()
    {
        $hash = $this->userDefinition->hashOldPassword('false');

        $this->userDefinition->checkOldHashes = true;
        $credentialValidationCallback = $this->userDefinition->getCredentialValidationCallback();

        $callbackResult = call_user_func($credentialValidationCallback, $hash, $this->testPassword);

        $this->assertFalse($callbackResult);
    }
}