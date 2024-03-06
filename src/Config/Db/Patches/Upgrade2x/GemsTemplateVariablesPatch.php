<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTemplateVariablesPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems template variables from single to double curly brackets';
    }

    public function getOrder(): int
    {
        return 20230102000103;
    }

    public function up(): array
    {
        return [
            "ALTER TABLE gems__comm_templates MODIFY COLUMN gct_name varchar(120) NOT NULL",
            "ALTER TABLE gems__comm_template_translations MODIFY COLUMN gctt_subject varchar(120) NULL",
            "UPDATE gems__comm_templates SET gct_name = REPLACE(REPLACE(REPLACE(REPLACE(gct_name, '{', '{{'), '}', '}}'), '{{{{', '{{'), '}}}}', '}}')",
            "UPDATE gems__comm_template_translations SET gctt_body = REPLACE(REPLACE(REPLACE(REPLACE(gctt_body, '{', '{{'), '}', '}}'), '{{{{', '{{'), '}}}}', '}}')",
            "UPDATE gems__comm_template_translations SET gctt_subject = REPLACE(REPLACE(REPLACE(REPLACE(gctt_subject, '{', '{{'), '}', '}}'), '{{{{', '{{'), '}}}}', '}}')",
            "UPDATE gems__comm_templates SET gct_name = REPLACE(gct_name, '{greetingNL}', '{greeting}')",
            "UPDATE gems__comm_template_translations SET gctt_body = REPLACE(gctt_body, '{greetingNL}', '{greeting}')",
            "UPDATE gems__comm_template_translations SET gctt_subject = REPLACE(gctt_subject, '{greetingNL}', '{greeting}')",
        ];
    }

    public function down(): array
    {
        return [
            "ALTER TABLE gems__comm_templates MODIFY COLUMN gct_name varchar(100) NOT NULL",
        ];
    }
}
