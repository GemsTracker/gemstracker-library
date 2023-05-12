<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class ExportSurveyAction extends \Gems\Actions\ExportSurveyActionAbstract
{
    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Export\\SurveyExportSearchFormSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $filter = $this->getSearchFilter();

        if (isset($filter['gto_id_survey']) && is_numeric($filter['gto_id_survey'])) {
            // Surveys have been selected
            $exportModelSource = $this->getExportModelSource();
            $model = $exportModelSource->getModel($filter, $filter);

            $noExportColumns = $model->getColNames('noExport');
            foreach($noExportColumns as $colName) {
                $model->remove($colName, 'label');
            }
        } else {
            $model = parent::createModel($detailed, $action);
            $model->set('gto_id_survey', 'label', $this->_('Please select a survey to start the export'));
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Export data from a single survey');
    }

    /**
     * Action for selecting a survey to export
     */
    public function indexAction()
    {
        $batch = $this->loader->getTaskRunnerBatch('export_data');
        $batch->reset();

        parent::indexAction();
    }
}
