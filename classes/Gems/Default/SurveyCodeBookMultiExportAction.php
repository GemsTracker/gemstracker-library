<?php

class Gems_Default_SurveyCodeBookMultiExportAction extends \Gems_Default_ExportMultiSurveysAction {

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = array('Export\\SurveyCodeBookMultiExportSnippet');

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle() {
        return $this->_('Export codebooks from multiple surveys');
    }

}
