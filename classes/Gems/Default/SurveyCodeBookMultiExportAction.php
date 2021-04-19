<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2021 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.9,1
 */
class Gems_Default_SurveyCodeBookMultiExportAction extends \Gems_Default_ExportMultiSurveysAction 
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialisation
     */
    protected $autofilterParameters = array(
        'containingId'      => null,
        'exportModelSource' => 'getExportModelSource',
        'extraSort'         => 'gto_start_time ASC',
        'forCodeBooks'      => true,
    );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = array('Export\\SurveyCodeBookMultiExportSnippet');

    /**
     *
     * @param array $surveys
     * @param array $filter
     * @param array $data
     * @return array
     */
    protected function getExportModels(array $surveys, array $filter, array $data)
    {
        $modelCreator = $this->loader->getModels();
        $models = [];

        foreach($surveys as $surveyId) {
            $filter['gto_id_survey'] = $surveyId;
            $models[$surveyId] = $modelCreator->getSurveyCodeBookModel($surveyId);
        }

        return $models;
    }
    
    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle() {
        return $this->_('Export codebooks from multiple surveys');
    }
}
