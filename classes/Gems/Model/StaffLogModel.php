<?php

namespace Gems\Model;

class StaffLogModel extends LogModel
{
    /**
     * Create a model for the log
     */
    public function __construct()
    {
        \Gems\Model\HiddenOrganizationModel::__construct('StaffLog', 'gems__staff', 'gsf', true);
        $this->addTable('gems__log_activity', ['gla_by' => 'gsf_id_user'], 'gla', true);
        $this->addTable('gems__log_setup', ['gla_action' => 'gls_id_action'])
            ->addLeftTable('gems__respondents', ['gla_respondent_id' => 'grs_id_user']);
    }
}
