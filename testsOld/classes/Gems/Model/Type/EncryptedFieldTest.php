<?php

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
     *
     * @var \Gems_Project_ProjectSettings
     */
    private $project;

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
        $model         = new MUtil_Model_NestedArrayModel('text', array());
        $settings      = new Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $this->project = new Gems_Project_ProjectSettings($settings);

        $this->maskedType   = new Gems_Model_Type_EncryptedField($this->project, true);
        $this->unmaskedType = new Gems_Model_Type_EncryptedField($this->project, false);

        $this->maskedType->apply($model, 'f1');
        $this->unmaskedType->apply($model, 'f1');
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

        $this->assertEquals('********',   $this->maskedType->loadValue($encrypted, false, 'f1'));
        $this->assertEquals($unencrypted, $this->unmaskedType->loadValue($encrypted, false, 'f1'));
    }

    /**
     * When decrypting a field that can not match, we should get our unaltered input back
     */
    public function testDecryptionMCrypt()
    {
        if (version_compare(PHP_VERSION, '7.1.0') < 0) {
            // Test old code without using openssl library
            unset($this->project['security']['methods']);
        }

        $unencrypted = 'myvisiblepassword';
        $this->assertEquals($unencrypted, $this->project->decrypt($this->unmaskedType->saveValue($unencrypted, false, 'f1')));
    }

    /**
     * When decrypting a field that can not match, we should get our unaltered input back
     */
    public function testDecryptionUnencrypted()
    {
        $unencrypted = 'myvisiblepassword';
        $this->assertEquals($unencrypted, $this->unmaskedType->loadValue(':null:' . $unencrypted, false, 'f1'));
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
