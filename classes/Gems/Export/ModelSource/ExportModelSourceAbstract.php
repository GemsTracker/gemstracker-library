<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
abstract class Gems_Export_ModelSource_ExportModelSourceAbstract extends \MUtil_Translate_TranslateableAbstract
{
	/**
	 * @var \Gems_Loader
	 */
	protected $loader;

	/**
	 * Get form elements for the specific Export
	 * @param  \Gems_Form $form existing form type
	 * @param  array data existing options set in the form
	 * @return array of form elements
	 */
	public function getFormElements(\Gems_Form $form, &$data)
	{
		return array();
	}


	/**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil_Model_ModelAbstract
     */
	abstract public function getModel($filter = array(), $data = array());
}