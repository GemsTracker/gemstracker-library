<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

use Gems\Util\Translated;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5 09-Oct-2018 13:48:01
 */
class AgendaDiagnosisAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gad_diagnosis_code' => SORT_ASC, 'gad_description' => SORT_ASC],
        'searchFields' => 'getSearchFields',
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = ['diagnosis', 'diagnoses'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Agenda\\AutosearchFormSnippet'];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showParameters = [
        'calSearchFilter' => 'getShowFilter',
        'caption'         => 'getShowCaption',
        'onEmpty'         => 'getShowOnEmpty',
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Agenda\\CalendarTableSnippet',
    ];

    /**
     * @var Translated
     */
    public $translatedUtil;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * Cleanup appointments
     */
    public function cleanupAction()
    {
        $params = $this->_processParameters($this->showParameters);
        $params['contentTitle'] = $this->_('Clean up existing appointments?');
        $params['filterOn']     = 'gap_diagnosis_code';
        $params['filterWhen']   = 'gad_filter';

        $snippets = [
            'Generic\\ContentTitleSnippet',
            'Agenda\\AppointmentCleanupSnippet',
            'Agenda\\CalendarTableSnippet',
        ];

        $this->addSnippets($snippets, $params);
    }
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
        $model      = new \MUtil\Model\TableModel('gems__agenda_diagnoses');

        \Gems\Model::setChangeFieldsByPrefix($model, 'gad');

        $model->setDeleteValues('gapr_active', 0);

        $model->set('gad_diagnosis_code',           'label', $this->_('Diagnosis code'),
            'description', $this->_('A code as defined by the coding system'),
            'required', true
        );
        $model->set('gad_description',              'label', $this->_('Activity'),
            'description', $this->_('Description of the diagnosis'),
            'required', true
        );

        $model->setIfExists('gad_coding_method',    'label', $this->_('Coding system'),
            'description', $this->_('The coding system used.'),
            'multiOptions', $this->translatedUtil->getEmptyDropdownArray() + $this->loader->getAgenda()->getDiagnosisCodingSystems()
        );

        $model->setIfExists('gad_code',             'label', $this->_('Diagnosis code'),
            'size', 10,
            'description', $this->_('Optional code name to link the diagnosis to program code.'));

        $model->setIfExists('gad_active',           'label', $this->_('Active'),
            'description', $this->_('Inactive means assignable only through automatich processes.'),
            'elementClass', 'Checkbox',
            'multiOptions', $this->translatedUtil->getYesNo()
        );
        $model->setIfExists('gad_filter',      'label', $this->_('Filter'),
            'description', $this->_('When checked appointments with these diagnoses are not imported.'),
            'elementClass', 'Checkbox',
            'multiOptions', $this->translatedUtil->getYesNo()
        );

        $model->addColumn("CASE WHEN gad_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Agenda diagnoses');
    }

    /**
     * Returns the fields for autosearch with
     *
     * @return array
     */
    public function getSearchFields()
    {
        return [
            'gad_coding_method' => $this->_('(all coding systems)'),
            'gad_filter'        => $this->_('(all filters)'),
        ];
    }

    /**
     *
     * @return string
     */
    public function getShowCaption()
    {
        return $this->_('Example appointments');
    }

    /**
     *
     * @return string
     */
    public function getShowOnEmpty()
    {
        return $this->_('No example diagnosis found');

    }
    /**
     * Get an agenda filter for the current shown item
     *
     * @return array
     */
    public function getShowFilter()
    {
        return [
            \MUtil\Model::SORT_DESC_PARAM => 'gap_admission_time',
            'gap_diagnosis_code' => $this->_getIdParam(),
            'limit' => 10,
        ];
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('diagnosis', 'diagnoses', $count);
    }
}
