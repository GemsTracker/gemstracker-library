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
 * Allow encryption of some database fields like passwords
 * 
 * Only use for passwords the application needs to use like database passwords
 * etc. The user passwords are stored using a one-way encryption.
 *
 * @package    Gems
 * @subpackage Model\Type
 * @author     175780
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Model_Type_EncryptedField {

    protected $_salt         = null;
    protected $_sharedSecret = null;

    public function __construct($sharedSecret = null, $salt = null) {
        $this->_salt         = $salt;
        $this->_sharedSecret = $sharedSecret;
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param string $name The field to set the seperator character
     * @return Gems_Model_Type_EncryptedField (continuation pattern)
     */
    public function apply(MUtil_Model_ModelAbstract $model, $name) {
        $model->setOnLoad($name, array($this, 'loadValue'));
        $model->setOnSave($name, array($this, 'saveValue'));

        if ($model instanceof MUtil_Model_DatabaseModelAbstract) {
            $model->setOnTextFilter($name, array($this, 'noTextFilter'));
        }

        return $this;
    }

    /**
     * Taken from http://www.php.net/manual/en/book.mcrypt.php#107483
     * 
     * @param type $encrypted
     * @param type $password
     * @param type $salt
     * @return boolean
     */
    public function decrypt($encrypted, $password, $salt = '!kQm*fF3pXe1Kbm%9') {
        if (strlen($encrypted) < 55) {
            // Impossible this was encrypted
            return false;
        }
        // Build a 256-bit $key which is a SHA256 hash of $salt and $password.
        $key       = hash('SHA256', $salt . $password, true);
        // Retrieve $iv which is the first 22 characters plus ==, base64_decoded.
        $iv        = base64_decode(substr($encrypted, 0, 22) . '==');
        // Remove $iv from $encrypted.
        $encrypted = substr($encrypted, 22);
        // Decrypt the data.  rtrim won't corrupt the data because the last 32 characters are the md5 hash; thus any \0 character has to be padding.
        $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
        // Retrieve $hash which is the last 32 characters of $decrypted.
        $hash      = substr($decrypted, -32);
        // Remove the last 32 characters from $decrypted.
        $decrypted = substr($decrypted, 0, -32);
        // Integrity check.  If this fails, either the data is corrupted, or the password/salt was incorrect.
        if (md5($decrypted) != $hash)
            return false;

        // Yay!
        return $decrypted;
    }

    /**
     * Taken from http://www.php.net/manual/en/book.mcrypt.php#107483
     * 
     * @param type $decrypted
     * @param type $password
     * @param type $salt
     * @return boolean
     */
    public function encrypt($decrypted, $password, $salt = '!kQm*fF3pXe1Kbm%9') {
        // Build a 256-bit $key which is a SHA256 hash of $salt and $password.
        $key       = hash('SHA256', $salt . $password, true);
        // Build $iv and $iv_base64.  We use a block size of 128 bits (AES compliant) and CBC mode.  (Note: ECB mode is inadequate as IV is not used.)
        $iv        = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
        if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22)
            return false;

        // Encrypt $decrypted and an MD5 of $decrypted using $key.  MD5 is fine to use here because it's just to verify successful decryption.
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));
        // We're done!
        return $iv_base64 . $encrypted;
    }

    /**
     * A ModelAbstract->setOnLoad() function that concatenates the
     * value if it is an array.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when populated from a post
     * @return string The decrypted value
     */
    public function loadValue($value, $isNew = false, $name = null, array $context = array(), $isPost = false) {
        // We could have a value that was not encoded
        if (!$isPost) {
            $newValue = $this->decrypt($value, $this->_sharedSecret, $this->_salt);
            if ($newValue === false) {
                // Decryption failed, could be a value that was not encrypted or secret/salt don't match
                return $value;
            }
            return $newValue;
        }

        return $value;
    }

    /**
     * Calculated fields can not exists in a where clause. 
     * 
     * We don't need to search on them with the text filter so we return
     * an empty array to disable text search.
     * 
     * @param type $filter
     * @param type $name
     * @param type $field
     * @param type $model
     * @return type
     */
    public function noTextFilter($filter, $name, $field, $model) {
        return array();
    }

    /**
     * A ModelAbstract->setOnSave() function that concatenates the
     * value if it is an array.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The encrypted value
     */
    public function saveValue($value, $isNew = false, $name = null, array $context = array()) {
        $newValue = $this->encrypt($value, $this->_sharedSecret, $this->_salt);
        if ($newValue == false)
            return $value;

        return $newValue;
    }

}
