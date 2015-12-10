<?php

/*
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * * Neither the name of Erasmus MC nor the
 *   names of its contributors may be used to endorse or promote products
 *   derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

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
        $settings = new Zend_Config_Ini(GEMS_ROOT_DIR . '/application/configs/project.ini', APPLICATION_ENV);
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
