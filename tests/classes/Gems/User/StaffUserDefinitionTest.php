<?php

class StaffUserDefinitionTest extends \Gems_Test_Db2TestAbstract
{
    /**
     * @var string Password to test with
     */
    protected $testPassword = 'correcthorsebatterystaple';

    protected $testUser = 'testuser';

    protected $testOrganization = 70;

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
        $this->userDefinition->setDb2($this->db);
    }

    /**
     * Returns the test dataset xml of the same name as the test
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return new PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            $classFile
        );
    }

    public function testValidAuthentication()
    {
        $user = $this->getMockBuilder('\Gems_User_User')
            ->setConstructorArgs([[], $this->userDefinition])
            ->getMock();

        $user->expects($this->once())
            ->method('getLoginName')
            ->will($this->returnValue($this->testUser));

        $user->expects($this->once())
            ->method('getBaseOrganizationId')
            ->will($this->returnValue($this->testOrganization));


        $authAdapter = $this->userDefinition->getAuthAdapter($user, $this->testPassword);

        // Test if auth adapter gets returned
        $this->assertInstanceOf(Zend\Authentication\Adapter\AbstractAdapter::class, $authAdapter);

        // Add hashed password with current settings to the database
        $hashedPassword = $this->userDefinition->hashPassword($this->testPassword);
        $db = $this->db;
        $this->db->query(
            "UPDATE gems__user_passwords SET gup_password = '$hashedPassword'",
            Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
        
        $result = $authAdapter->authenticate();

        // Test if Result object is returned
        $this->assertInstanceOf(Zend\Authentication\Result::class, $result);
    
        // Test if authentication is succesfull
        $this->assertTrue($result->isValid());
    }

    public function testWrongAuthentication()
    {
        $user = $this->getMockBuilder('\Gems_User_User')
            ->setConstructorArgs([[], $this->userDefinition])
            ->getMock();

        $user->expects($this->once())
            ->method('getLoginName')
            ->will($this->returnValue($this->testUser));

        $user->expects($this->once())
            ->method('getBaseOrganizationId')
            ->will($this->returnValue($this->testOrganization));


        $authAdapter = $this->userDefinition->getAuthAdapter($user, 'wrongPassword');

        // Test if auth adapter gets returned
        $this->assertInstanceOf(Zend\Authentication\Adapter\AbstractAdapter::class, $authAdapter);

        // Add hashed password with current settings to the database
        $hashedPassword = $this->userDefinition->hashPassword($this->testPassword);
        $this->db->query(
            "UPDATE gems__user_passwords SET gup_password = '$hashedPassword'",
            Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
        
        $result = $authAdapter->authenticate();

        // Test if Result object is returned
        $this->assertInstanceOf(Zend\Authentication\Result::class, $result);
    
        // Test if authentication is false
        $this->assertFalse($result->isValid());
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