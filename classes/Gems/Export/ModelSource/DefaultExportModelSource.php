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
class Gems_Export_ModelSource_DefaultExportModelSource extends \Gems_Export_ModelSource_ExportModelsourceAbstract
{
	/**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil_Model_ModelAbstract
     */
	public function getModel($filter = array(), $data = array())
	{
		return false;
	}

	/**
     * Get the proposed filename for the export of a model with specific filter options
     * @param  array  $filter Filter for the model
     * @return string   proposed filename
     */
	public function getFileName($filter)
	{
		return false;
	}
}