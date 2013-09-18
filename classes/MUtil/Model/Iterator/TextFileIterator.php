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
class MUtil_Model_Iterator_TextFileIterator implements Iterator, Serializable
{
    /**
     * The current value
     *
     * @var array
     */
    // protected $_current;

    /**
     *
     * @var SplFileObject
     */
    protected $_file;

    /**
     *
     * @var string
     */
    protected $_firstline;

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
    protected $_filepos;

    /**
     * The function that maps the string to the output values
     *
     * @var callable
     */
    protected $_mapFunction;

    /**
     * The current position used for the key
     *
     * @var type
     */
    protected $_position = -2;

    /**
     * Is the position is valid
     *
     * @var boolean
     */
    protected $_valid = false;

    /**
     * Initiate this line by line file iterator
     *
     * @param string $filename
     * @param callable $mapFunction function(string currentLine, string firstLine) => named row array
     */
    public function __construct($filename, $mapFunction)
    {
        $this->_filename    = $filename;
        $this->_mapFunction = $mapFunction;

        if (!is_callable($mapFunction)) {
            throw new MUtil_Model_ModelException(__CLASS__ . " needs a callable mapFunction argument.");
        }
    }

    /**
     * Return the current element
     *
     * @return array
     */
    public function current()
    {
        if (! $this->_file instanceof SplFileObject) {
            $this->rewind();
        }
        return call_user_func($this->_mapFunction, trim($this->_file->current(), "\r\n"));
    }

    /**
     * Opens or rewinds the file and returns the first line.
     *
     * This line can then be used to determined the mapping used by the mapping function.
     *
     * @return string Or boolean if file does not exist
     */
    public function getFirstLine()
    {
        if (! $this->_firstline) {
            $this->rewind();
        }

        return $this->_firstline;
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        if (! $this->_file instanceof SplFileObject) {
            $this->rewind();
        }

        if (! $this->_valid) {
            return;
        }

        $this->_file->next();
        $this->_position++;
        $this->_filepos = $this->_file->ftell();

        if (! ($this->_file->valid() && $this->_file->current())) {
            // $this->_current = false;
            $this->_valid   = false;
            return;
        }

        // $this->_current = call_user_func($this->_mapFunction, $this->_file->current());
    }

    /**
     *  Rewind the Iterator to the first element
     */
    public function rewind()
    {
        if (0 === $this->_position) {
            // At the beginning read position
            return;
        }
        if (! $this->_file instanceof SplFileObject) {
            if (! file_exists($this->_filename)) {
                $this->_valid = false;
                return;
            }

            $this->_file      = new SplFileObject($this->_filename, 'r');
            $this->_firstline = trim($this->_file->current(), "\r\n");
        } else {
            $this->_file->rewind();
        }
        $this->_valid = true;

        // Go to the first line after the header
        $this->_position = -1;
        $this->next();
    }

    /**
     * Return the string representation of the object.
     *
     * @return string
     */
    public function serialize()
    {
        $data = array(
            'filename' => $this->_filename,
            'filepos'  => $this->_filepos,
            'mapper'   => $this->_mapFunction,
            'position' => $this->_position,
            'valid'    => $this->_valid,
        );

        return Zend_Serializer::getDefaultAdapter()->serialize($data);
    }

    /**
     * Called during unserialization of the object.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = Zend_Serializer::getDefaultAdapter()->unserialize($serialized);

        $this->_filename    = $data['filename'];
        $this->_mapFunction = $data['mapper'];

        $this->rewind();

        $this->_position = $data['position'] - 1;
        $this->_valid    = $data['valid'];
        $this->_file->fseek($data['filepos'], SEEK_SET);
        $this->next();
    }

    /**
     * True if not EOF
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_valid;
    }
}
