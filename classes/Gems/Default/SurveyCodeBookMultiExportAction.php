<?php

class Gems_Default_SurveyCodeBookMultiExportAction extends \Gems_Default_ExportMultiSurveysAction {

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
        $models = [];

        $function = function($loader, $filter, $sort, $data, $settings=null) {
            $model = $this->loader->getModels()->getSurveyCodeBookModel($filter['gto_id_survey']);
            return $model;
        };

        foreach($surveys as $surveyId) {
            $filter['gto_id_survey'] = $surveyId;
            $models[$surveyId] = [
                'data'      => $data,
                'model'     => $function,
                'filter'    => $filter,
                'sort'      => $this->autofilterParameters['extraSort'],
            ];
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
