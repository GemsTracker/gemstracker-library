<?php

/*

/**
 * Description of EncryptedFieldTest
 *
 * @package    Gems
 * @subpackage Gems
 * @author     175780
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class EncryptedFieldTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gems_Model_Type_EncryptedField
     */
    protected $maskedType;

    /**
     * @var Gems_Model_Type_EncryptedField
     */
    protected $unmaskedType;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $model   = new MUtil_Model_NestedArrayModel('text', array());
        $settings = new Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $project = new Gems_Project_ProjectSettings($settings);

        $this->maskedType   = new Gems_Model_Type_EncryptedField($project, true);
        $this->unmaskedType = new Gems_Model_Type_EncryptedField($project, false);

        $this->maskedType->apply($model, 'f1', 'f2');
        $this->unmaskedType->apply($model, 'f1', 'f2');
    }

    /**
     * Make sure the returned value is not the input value (we test decryption possibility later)
     */
    public function testEncryption()
    {
        $unencrypted = 'myvisiblepassword';
        $this->assertNotEquals($unencrypted, $this->maskedType->saveValue($unencrypted));
    }

    /**
     * Actually two tests:
     * 1) Encrypt and see if decrypt gives the same result
     * 2) Use a previously encrypted value and see if we can decrypt it
     */
    public function testDecryption()
    {
        $unencrypted = 'myvisiblepassword';
        $encrypted   = $this->unmaskedType->saveValue($unencrypted);
        // echo "\n$encrypted\n";
        
        $this->assertEquals('********',   $this->maskedType->loadValue($encrypted, false, 'f1', array('f2' => 'default')));
        $this->assertEquals($unencrypted, $this->unmaskedType->loadValue($encrypted, false, 'f1', array('f2' => 'default')));
    }

    /**
     * When decrypting a field that can not match, we should get our unaltered input back
     */
    public function testDecryptionUnencrypted()
    {
        $unencrypted = 'myvisiblepassword';
        $this->assertEquals($unencrypted, $this->unmaskedType->loadValue($unencrypted, false, 'f1', array('f2' => null)));
    }

    /**
     * When decryption fails, we expect the unaltered input as a return value
     */
    public function testDecryptionFailure()
    {
        $unencrypted = 'myvisiblepassword';
        $this->assertNotEquals(
                $unencrypted,
                $this->unmaskedType->saveValue($unencrypted, false, 'f1', array('f2' => null))
                );
    }
}
