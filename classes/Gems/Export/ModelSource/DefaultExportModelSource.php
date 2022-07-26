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
class DefaultExportModelSource extends \Gems\Export_ModelSource_ExportModelsourceAbstract
{
	/**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil\Model\ModelAbstract
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