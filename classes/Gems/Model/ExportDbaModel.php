<?php


namespace Gems\Model;


class ExportDbaModel extends \Gems_Model_DbaModel
{
    /**
     * List of groups (database prefixes) to exclude from export
     *
     * @var array
     */
    protected $excludeGroups = ['gemsdata'];

    /**
     * List of groups (database prefixes) to include in export
     *
     * @var array
     */
    protected $includeGroups = ['gems'];

    /**
     * @var array List of database tables that do not contain respondent data
     */
    protected $gemsDataExportWhitelist = [
        'gems__agenda_activities',
        'gems__agenda_diagnoses',
        'gems__agenda_procedures',
        'gems__agenda_staff',
        'gems__api_permissions',
        'gems__appointment_filters',
        'gems__chart_config',
        'gems__comm_jobs',
        'gems__comm_templates',
        'gems__comm_template_translations',
        'gems__conditions',
        'gems__consents',
        'gems__groups',
        'gems__locations',
        'gems__log_actions',
        'gems__log_setup',
        'gems__mail_codes',
        'gems__oauth_scope',
        'gems__organizations',
        'gems__patches',
        'gems__patch_levels',
        'gems__reception_codes',
        'gems__roles',
        'gems__rounds',
        'gems__sources',
        'gems__surveys',
        'gems__survey_questions',
        'gems__survey_question_options',
        'gems__tracks',
        'gems__track_appointments',
        'gems__track_fields',
    ];

    /**
     * List of project specific database tables that do not contain respondent data
     *
     * @var array
     */
    protected $projectDataExportWhitelist = [
    ];

    /**
     * Stores the raw parameters from applyParameters
     *
     * @var array
     */
    protected $rawParameters = [];


    /**
     * Stores the fields that can be used for sorting or filtering in the
     * sort / filter objects attached to this model.
     *
     * @param array $parameters
     * @param boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     * @return array The $parameters minus the sort & textsearch keys
     */
    public function applyParameters(array $parameters, $includeNumericFilters = false)
    {
        $this->rawParameters = $parameters;
        return parent::applyParameters($parameters, $includeNumericFilters);
    }

    /**
     * Get a whitelist of database tables that do no contain respondent data
     *
     * @return array
     */
    protected function getDataExportWhitelist()
    {
        return array_merge($this->gemsDataExportWhitelist, $this->projectDataExportWhitelist);
    }

    /**
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return \Traversable Return an iterator over or an array of all the rows in this object
     */
    protected function _loadAllTraversable()
    {
        $data = parent::_loadAllTraversable();
        $dataExportWhitelist = $this->getDataExportWhitelist();
        foreach($data as $key=>$row) {
            if (in_array($row['group'], $this->excludeGroups)) {
                unset($data[$key]);
                continue;
            }
            if (!in_array($row['group'], $this->includeGroups)) {
                unset($data[$key]);
                continue;
            }

            $data[$key]['exportTable'] = true;
            $data[$key]['respondentData'] = true;
            $data[$key]['data'] = true;
            if (in_array($row['name'], $dataExportWhitelist)) {
                // In the whitelist so contains no respondent data
                $data[$key]['respondentData'] = false;
            } elseif (array_key_exists('include_respondent_data', $this->rawParameters) && $this->rawParameters['include_respondent_data'] != '1') {
                // Not in the whitelist so contains respondent data, block export unless asked to include it
                $data[$key]['data'] = false;
            }

        }
        return $data;
    }
}
