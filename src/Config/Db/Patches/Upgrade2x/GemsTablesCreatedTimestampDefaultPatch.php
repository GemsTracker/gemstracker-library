<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsTablesCreatedTimestampDefaultPatch extends PatchAbstract
{
    private array $columns = [
        [ 'gems__appointments', 'gap_created' ],
        [ 'gems__chart_config', 'gcc_created' ],
        [ 'gems__data_set_mappings', 'gdsm_created' ],
        [ 'gems__data_sets', 'gds_created' ],
        [ 'gems__diagnosis_treatment', 'gdtrt_created' ],
        [ 'gems__episodes_of_care', 'gec_created' ],
        [ 'gems__groups', 'ggp_created' ],
        [ 'gems__log_respondent2track2field', 'glrtf_created' ],
        [ 'gems__log_respondent_communications', 'grco_created' ],
        [ 'gems__log_respondent_consents', 'glrc_created' ],
        [ 'gems__log_setup', 'gls_created' ],
        [ 'gems__mail_codes', 'gmc_created' ],
        [ 'gems__organizations', 'gor_created' ],
        [ 'gems__patches', 'gpa_created' ],
        [ 'gems__reception_codes', 'grc_created' ],
        [ 'gems__log_respondent_communications', 'grco_created' ],
        [ 'gems__respondent_id_merges', 'grim_created' ],
        [ 'gems__reference_data', 'grfd_created' ],
        [ 'gems__respondent2track', 'gr2t_created' ],
        [ 'gems__respondent2track2appointment', 'gr2t2a_created' ],
        [ 'gems__respondent2track2field', 'gr2t2f_created' ],
        [ 'gems__respondent_relations', 'grr_created' ],
        [ 'gems__roles', 'grl_created' ],
        [ 'gems__rounds', 'gro_created' ],
        [ 'gems__sites', 'gsi_created' ],
        [ 'gems__survey_question_options', 'gsqo_created' ],
        [ 'gems__survey_questions', 'gsq_created' ],
        [ 'gems__surveys', 'gsu_created' ],
        [ 'gems__systemuser_setup', 'gsus_created' ],
        [ 'gems__tokens', 'gto_created' ],
        [ 'gems__track_appointments', 'gtap_created' ],
        [ 'gems__track_fields', 'gtf_created' ],
        [ 'gems__tracks', 'gtr_created' ],
        [ 'gems__user_passwords', 'gup_created' ],

    ];

    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Set proper default of _created columns';
    }

    public function getOrder(): int
    {
        return 20231221000001;
    }

    public function up(): array
    {
        $statements = [
        ];
        foreach ($this->columns as $alterData) {
            list($table, $column) = $alterData;
            if ($this->databaseInfo->tableExists($table)) {
                $statements[] = sprintf('ALTER TABLE %s MODIFY %s timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP', $table, $column);
            }
        }

        return $statements;
    }
}
