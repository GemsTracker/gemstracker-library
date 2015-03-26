<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ModelTranslatorInterface.php 203 2012-01-01 12:51:32Z matijs $
 */

/**
 * Translators can translate the data from one model to be saved using another
 * model.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Interface available since MUtil version 1.3
 */
interface MUtil_Model_ModelTranslatorInterface extends \MUtil_Registry_TargetInterface
{
    /**
     * Add the current row to a (possibly separate) batch that does the importing.
     *
     * @param \MUtil_Task_TaskBatch $importBatch The import batch to impor this row into
     * @param string $key The current iterator key
     * @param array $row translated and validated row
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function addSaveTask(\MUtil_Task_TaskBatch $importBatch, $key, array $row);

    /**
     * Returns a description of the translator to enable users to choose
     * the translator they need.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Returns error messages from the transformation.
     *
     * @return array of String messages
     */
    public function getErrors();

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getFieldsTranslations();

    /**
     * Returns an array of the field names that are required
     *
     * @return array of fields sourceName => targetName
     */
    public function getRequiredFields();

    /**
     * Returns a description of the translator errors for the row specified.
     *
     * @param mixed $row
     * @return array of String messages
     */
    public function getRowErrors($row);

    /**
     * Get the source model, where the data is coming from.
     *
     * @return \MUtil_Model_ModelAbstract $sourceModel The source of the data
     */
    public function getSourceModel();

    /**
     * Get a form for filtering and validation, populating it
     * with elements.
     *
     * @return \Zend_Form
     */
    public function getTargetForm();

    /**
     * Get the target model, where the data is going to.
     *
     * @return \MUtil_Model_ModelAbstract $sourceModel The target of the data
     */
    public function getTargetModel();

    /**
     * True when the transformation generated errors.
     *
     * @return boolean True when there are errora
     */
    public function hasErrors();

    /**
     * Set the source model, where the data is coming from.
     *
     * @param \MUtil_Model_ModelAbstract $sourceModel The source of the data
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setSourceModel(\MUtil_Model_ModelAbstract $sourceModel);

    /**
     * Set the target model, where the data is going to.
     *
     * @param \MUtil_Model_ModelAbstract $sourceModel The target of the data
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setTargetModel(\MUtil_Model_ModelAbstract $targetModel);

    /**
     * Prepare for the import.
     *
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function startImport();

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key);

    /**
     * Validate the data against the target form
     *
     * @param array $row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function validateRowValues(array $row, $key);
}
