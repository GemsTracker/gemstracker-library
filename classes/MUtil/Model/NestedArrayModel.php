<?php

/**
 * Copyright (c) 201e, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
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
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $Id: SessionModel.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * A model that stores a nested data array in a session object
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Model_NestedArrayModel extends \MUtil_Model_ArrayModelAbstract
{
    /**
     * The array containing the data
     *
     * @var array
     */
    protected $_data = array();

    /**
     *
     * Save using a string as content, the first line contains the header for the field
     * order in the rest of the data.
     *
     * @param string $modelName Hopefully unique model name
     * @param array|string $content Either a nested array containing the data or a string to split
     * @param array|string $fieldSplit Either an array containing header field names for the content
     *                                 or a string split string.
     * @param string $lineSplit
     */
    public function __construct($modelName, $content = array(), $fieldSplit = "\t", $lineSplit = "\n")
    {
        parent::__construct($modelName);

        if (is_array($content)) {
            if (is_array($fieldSplit)) {
                $this->saveIndexed($content, $fieldSplit);
            } else {
                $this->_saveAllTraversable($content);
            }
        } else {
            $this->saveHeadedString($content, $fieldSplit, $lineSplit);
        }
    }

    /**
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return \Traversable Return an iterator over or an array of all the rows in this object
     */
    protected function _loadAllTraversable()
    {
        return $this->_data;
    }

    /**
     * When $this->_saveable is true a child class should either override the
     * delete() and save() functions of this class or override _saveAllTraversable().
     *
     * In the latter case this class will use _loadAllTraversable() and remove / add the
     * data to the data in the delete() / save() functions and pass that data on to this
     * function.
     *
     * @param array $data An array containing all the data that should be in this object
     * @return void
     */
    protected function _saveAllTraversable(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Save using a string as content, the first line contains the header for the field
     * order in the rest of the data.
     *
     * @param string $content
     * @param string $fieldSplit
     * @param string $lineSplit
     */
    public function saveHeadedString($content, $fieldSplit = "\t", $lineSplit = "\n")
    {
        $rows = explode($lineSplit, $content);
        $headers = explode($fieldSplit, trim(array_shift($rows), "\r\n"));

        $i    = 0;
        $data = array();
        foreach ($rows as $row) {
            $data[$i] = explode($fieldSplit, trim($row, "\r\n"));
            $i        = $i + 1;
        }

        $this->saveIndexed($data, $headers);
    }

    /**
     * Save using one array for field names and a nexted array containing the
     * fields in the same order.
     *
     * @param array $indices Array containing the field names
     * @param array $fields Nested array
     * @throws \MUtil_Model_ModelException
     */
    public function saveIndexed(array $rows, array $indices)
    {
        // Make sure the model knows the fields
        foreach ($indices as $name) {
            $this->set($name);
        }

        foreach ($rows as $id => $row) {
            if (!is_array($row)) {
                throw new \MUtil_Model_ModelException("Row item '$id' is not an array in.");
            }
            $max = min(array(count($indices), count($row)));
            $this->_data[$id] = array_combine(array_slice($indices, 0, $max), array_slice($row, 0, $max));
        }
    }
}
