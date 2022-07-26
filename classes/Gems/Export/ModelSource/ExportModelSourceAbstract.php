<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Export\ModelSource;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
abstract class ExportModelSourceAbstract extends \MUtil\Translate\TranslateableAbstract
{
	/**
	 * Get form elements for the specific Export
     *
	 * @param  \Gems\Form $form existing form type
	 * @param  array data existing options set in the form
	 * @return array of form elements
	 */
	public function getExtraDataFormElements(\Gems\Form $form, $data)
	{
		return array();
	}

	/**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil\Model\ModelAbstract
     */
	abstract public function getModel($filter = array(), $data = array());
}