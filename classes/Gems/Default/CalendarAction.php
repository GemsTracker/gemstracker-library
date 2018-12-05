<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_CalendarAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'dateFormat'        => 'getDateFormat',
        'extraSort'         => array(
            'gap_admission_time' => SORT_ASC,
            'gor_name'           => SORT_ASC,
            'glo_name'           => SORT_ASC,
            ),
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Agenda_CalendarTableSnippet';

    /**
     *
     * @var \Gems_User_Organization
     */
    public $currentOrganization;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * Array of the actions that use the model in form version.
     *
     * This determines the value of forForm().
     *
     * @var array $formActions Array of the actions that use the model with a form.
     */
    public $formActions = array('create', 'delete', 'edit', 'import', 'simpleApi');

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Agenda_CalendarSearchSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->createAppointmentModel();
        $model->applyBrowseSettings();
        return $model;
    }

    /**
     * Get the date format used for the appointment date
     *
     * @return array
     */
    public function getDateFormat()
    {
        $model = $this->getModel();

        $format = $model->get('gap_admission_time', 'dateFormat');
        if (! $format) {
            $format = \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
        }

        return $format;
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults()
    {
        if (! $this->defaultSearchData) {
            $org = $this->currentOrganization;
            $this->defaultSearchData = array(
                'gap_id_organization' => $org->canHaveRespondents() ? $org->getId() : null,
                'dateused'            => 'gap_admission_time',
                'datefrom'            => new \MUtil_Date(),
                );
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter(
            $filter,
            $this->db,
            $this->getDateFormat(),
            'yyyy-MM-dd HH:mm:ss');

        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }
    
    public function getIndexTitle()
    {
        return $this->_('Calendar');
    }

    public function simpleApiAction()
    {
        $this->disableLayout();

        $data         = $this->getRequest()->getParams();
        $importLoader = $this->loader->getImportLoader();
        $model        = $this->getModel();
        $translator   = new \Gems_Model_Translator_AppointmentTranslator($this->_('Direct import'));

        $this->source->applySource($translator);
        $translator->setTargetModel($model)
                ->startImport();

        $raw    = $translator->translateRowValues($data, 1);
        if (false === $raw) {
            // No patient found
            echo "Patient does not exist";
            exit(0);
        }
        $row    = $translator->validateRowValues($raw, 1);
        $errors = $translator->getRowErrors(1);

        if ($errors) {
            echo "ERRORS Occured:\n" . implode("\n", $errors);
            exit(count($errors));

        } else {
            $output  = $model->save($row);
            $changed = $model->getChanged();
            // print_r($output);

            $appId = $output['gap_id_appointment'];
            if ($changed) {
                echo "Changes saved to appointment $appId.";
            }  else {
                echo "No changes to appointment $appId.";
            }
            exit(0);
        }
    }
}
