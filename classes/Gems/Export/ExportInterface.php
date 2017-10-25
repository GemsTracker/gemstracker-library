<?php
/**
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

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
interface Gems_Export_ExportInterface
{
    /**
     * Return an array of Form Elements for this specific export
     *
     * @param type $form
     * @param type $data
     * @return \Zend_Form_Element[]
     */
    public function getFormElements(&$form, &$data);

    /**
     * Sets the default form values when this export type is first chosen
     *
     * @return array
     */
    public function getDefaults();

    /**
     * Returns the unique name for this class
     *
     * It will be used for handling this export's specific options
     *
     * @return string
     */
    public function getName();

    /**
     * This method handles the export with the given options
     *
     * The method takes care of rendering the right script by using $this->export->controller to
     * access the controller object.
     *
     * @param array                     $data        The formdata
     * @param \Gems_Tracker_Survey       $survey      The survey object we are exporting
     * @param array                     $answers     The array of answers
     * @param \MUtil_Model_ModelAbstract $answerModel The modified answermodel that includes info about extra attributes
     * @param string                    $language    The language used / to use for the export
     */
    public function handleExport($data, $survey, $answers, $answerModel, $language);
}