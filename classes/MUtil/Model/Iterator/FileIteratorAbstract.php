<?php

/**
 * Copyright (c) 2013, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TextFileIterator.php$
 */

/**
 * Iterate line by line through a file, with a separate output for the first header line
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Model_Iterator_FileIteratorAbstract implements \Iterator, \Serializable
{
    /**
     * The content file encoding, only set when different from internal encoding.
     *
     * Is stored here but should be used by
     *
     * @var string
     */
    protected $_encoding;

    /**
     *
     * @var array
     */
    protected $_fieldMap;

    /**
     * Count of the fieldmap
     *
     * @var int
     */
    protected $_fieldMapCount;

    /**
     *
     * @var \SplFileObject
     */
    protected $_file = null;

    /**
     * SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY
     *
     * @var int
     */
    protected $_fileFlags = 7;

    /**
     * The name of the content file
     *
     * @var string
     */
    protected $_filename;

    /**
     * The position of the current item in the file
     *
     * @var int
     */
    protected $_filepos = null;

    /**
     * The current key value
     *
     * @var type
     */
    protected $_key = -1;

    /**
     * Variables for stuff that should be serialized by sub classes
     *
     * @var mixed
     */
    protected $_serialized;

    /**
     * Initiate this file iterator
     *
     * @param string $filename
     * @param string $encoding An optional character encoding
     */
    public function __construct($filename, $encoding = null)
    {
        $this->_filename = $filename;

        if ($encoding && ($encoding !== mb_internal_encoding())) {
            $this->_encoding = $encoding;
        }
    }

    /**
     * Open the file and optionally restore the position
     *
     * @return void
     */
    private function _openFile()
    {
        $this->_fieldMap      = array();
        $this->_fieldMapCount = 0;

        if (! file_exists($this->_filename)) {
            $this->_file = false;
            return;
        }

        try {
            $this->_file = new \SplFileObject($this->_filename, 'r');
            $this->_file->setFlags($this->_fileFlags);
            $this->_skipBom();

            $firstline = $this->_file->current();

            if ($firstline) {
                $this->_fieldMap = $this->_recode($firstline);
                $this->_fieldMapCount = count($this->_fieldMap);

                // Check for fields, do not run when empty
                if (0 === $this->_fieldMapCount) {
                    $this->_file = false;
                    return;
                }
            }

            // Restore old file position if any
            if (null !== $this->_filepos) {
                $this->_file->fseek($this->_filepos, SEEK_SET);
            }

            // Always move to next, even if there was no first line
            $this->next();

        } catch (Exception $e) {
            $this->_file = false;
        }
    }

    /**
     * Skip any BOM in the file
     */
    private function _skipBom()
    {
        $bom = $this->_file->fgetc() . $this->_file->fgetc() . $this->_file->fgetc();

        // If there is no bom, then remove bom will return a 3 character string.
        // In that case the file position must be reset to the start of the file
        if (\MUtil_Encoding::removeBOM($bom)) {
            $this->_file->rewind();
        }
    }

    /**
     * Transform the input into an array and recode the input to the correct encoding
     * (if any, the encoding is only set when different from the internal encoding)
     *
     * @param mixed $line String or array depending on file flags
     * @return array
     */
    abstract protected function _recode($line);

    /**
     * Return the current element
     *
     * @return array or false
     */
    public function current()
    {
        if (null === $this->_file) {
            $this->_openFile();
        }

        if ((! $this->_file instanceof SplFileObject) || $this->_file->eof()) {
            return false;
        }

        $fields     = $this->_recode($this->_file->current());
        $fieldCount = count($fields);

        if (0 ===  $fieldCount) {
            return false;
        }

        if ($fieldCount > $this->_fieldMapCount) {
            // Remove extra fields from the input
            $fields = array_slice($fields, 0, $this->_fieldMapCount);

        } elseif ($fieldCount < $this->_fieldMapCount) {
            // Add extra nulls to the input
            $fields = $fields + array_fill($fieldCount, $this->_fieldMapCount - $fieldCount, null);
        }

        return array_combine($this->_fieldMap, $fields);
    }

    /**
     * Get the map array key value => field name to use
     *
     * This line can then be used to determined the mapping used by the mapping function.
     *
     * @return string Or boolean if file does not exist
     */
    public function getFieldMap()
    {
        if (null === $this->_file) {
            $this->_openFile();
        }

        return $this->_fieldMap;
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        if (null === $this->_file) {
            $this->_openFile();
        }

        return $this->_key;
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        if (null === $this->_file) {
            $this->_openFile();
        }

        if ($this->_file) {
            $this->_key = $this->_key + 1;
            if (! $this->_file->eof()) {
                $this->_file->next();
                $this->_filepos = $this->_file->ftell();
            }
        }
    }

    /**
     *  Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->_filepos = null;
        $this->_key = -1;

        if (null === $this->_file) {
            $this->_openFile();
        } elseif ($this->_file) {
            $this->_file->rewind();
            $this->_file->current(); // Reading line is nexessary for correct loading of file.
            $this->next();
        }
    }

    /**
     * Return the string representation of the object.
     *
     * @return string
     */
    public function serialize()
    {
        $data = array(
            'encoding'   => $this->_encoding,
            'filename'   => $this->_filename,
            'filepos'    => $this->_filepos,
            'key'        => $this->_key - 1,
            'serialized' => $this->_serialized,
        );

        return serialize($data);
    }

    /**
     * Called during unserialization of the object.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = @unserialize($serialized);
        if ($data === false) {
            $lastErr = error_get_last();
            error_log($lastErr['message'] . "\n", 3, ini_get('error_log'));
            return;
        }

        // WARNING! WARNING! WARNING!
        //
        // Do not reopen the file in the unserialize statement
        // 1 - the file gets locked
        // 2 - if the file is deleted you cannot reopen your session.
        //
        // Normally this is not a problem, but when testing...
        $this->_encoding   = $data['encoding'];
        $this->_file       = null;
        $this->_filename   = $data['filename'];
        $this->_filepos    = $data['filepos'];
        $this->_key        = $data['key'];
        $this->_serialized = $data['serialized'];
    }

    /**
     * True if not EOF
     *
     * @return boolean
     */
    public function valid()
    {
        if (null === $this->_file) {
            $this->_openFile();
        }

        return $this->_file && (! $this->_file->eof());
    }
}
