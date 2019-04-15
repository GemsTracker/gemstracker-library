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
            $data[$key]['exportTable'] = true;
            $data[$key]['respondentData'] = true;
            if (in_array($row['name'], $dataExportWhitelist)) {
                $data[$key]['respondentData'] = false;
            }
        }
        return $data;
    }
}
