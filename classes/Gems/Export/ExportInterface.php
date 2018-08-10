<?php
/**
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Export;

/**
 * The export interface
 *
 * Exporting survey-data can be done for various sorts of output formats, this interface
 * describes the methods needed to implement an output format
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
interface ExportInterface
{
    /**
     * Add an export command with specific details. Can be batched.
     * @param array $data    Data submitted by export form
     * @param array $modelId Model Id when multiple models are passed
     */
    public function addExport($data, $modelId = false);
            
    /**
     * Finalizes the files stored in $this->files.
     * If it has 1 file, it will return that file, if it has more, it will return a zip containing all the files, named as the first file in the array.
     * @return File with download headers
     */
    public function finalizeFiles();
            
    /**
     * Return an array of Form Elements for this specific export
     *
     * @param type $form
     * @param type $data
     * @return \Zend_Form_Element[]
     */
    public function getFormElements(&$form, &$data);
    
    /**
     * Get the model to export
     * @return \MUtil_Model_ModelAbstract
     */
    public function getModel();

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues();

    /**
     * Returns the unique name for this class
     *
     * It will be used for handling this export's specific options
     *
     * @return string
     */
    public function getName();
    
    /**
     * Set the model when not in batch mode
     * 
     * @param \MUtil_Model_ModelAbstract $model
     */
    public function setModel(\MUtil_Model_ModelAbstract $model);

}