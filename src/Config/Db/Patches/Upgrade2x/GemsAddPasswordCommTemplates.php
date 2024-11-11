<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;

class GemsAddPasswordCommTemplates extends PatchAbstract
{
    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add confirm data change communication templates';
    }

    public function up(): array
    {
        if ($this->resultFetcher->fetchOne("SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangeEmail'") !== null) {
            return [];
        }

        return [
            "INSERT INTO `gems__comm_templates` (`gct_name`, `gct_target`, `gct_code`, `gct_changed`, `gct_changed_by`, `gct_created`, `gct_created_by`)
                VALUES
                    ('Staff change email confirmation', 'staffPassword', 'confirmChangeEmail', now(), '0', now(), '0'),
                    ('Staff change phone confirmation', 'staffPassword', 'confirmChangePhone', now(), '0', now(), '0')",
            "INSERT INTO `gems__comm_template_translations` (`gctt_id_template`, `gctt_lang`, `gctt_subject`, `gctt_body`) VALUES
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangeEmail'), 'en', 'The confirmation code for your e-mail change', 'Please use the following code to confirm your e-mail change for {{organization}} site {{project}}: {{confirmation_code}}'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangeEmail'), 'nl', 'De bevestigingscode voor je email wijziging', 'Gebruik de volgende code om de email wijziging voor de {{organization}} site {{project}} te bevestigen: {{confirmation_code}}'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangePhone'), 'en', 'Your confirmation code', 'Verify your new phone number using this code: {{confirmation_code}}'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangePhone'), 'nl', 'Je bevestigingscode', 'Bevestig je nieuwe telefoonnummer met deze code: {{confirmation_code}}')",
        ];
    }
}