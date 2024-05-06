<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use MUtil\Markup;

class GemsTemplateToHtmlPatch extends PatchAbstract
{
    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
    )
    {}
    public function getDescription(): string|null
    {
        return 'Update gems templates to html';
    }

    public function getOrder(): int
    {
        return 20240417130000;
    }

    public function up(): array
    {
        $select = $this->resultFetcher->getSelect('gems__comm_template_translations');
        $select->columns(['gctt_id_template', 'gctt_lang', 'gctt_body', 'gctt_subject']);

        $templates = $this->resultFetcher->fetchAll($select);

        $platform = $this->resultFetcher->getPlatform();
        $queries = [];
        foreach ($templates as $template) {
            $body = Markup::render($template['gctt_body'], 'Bbcode', 'Html');
            $subject = Markup::render($template['gctt_subject'], 'Bbcode', 'Html');

            $sql = 'UPDATE gems__comm_template_translations SET gctt_body = %s, gctt_subject = %s WHERE gctt_id_template = %d AND gctt_lang = %s';
            $queries[] = sprintf($sql, $platform->quoteValue($body), $platform->quoteValue($subject), $template['gctt_id_template'], $platform->quoteValue($template['gctt_lang']));
        }

        $queries[] = 'UPDATE gems__comm_template_translations SET gctt_body = NULL WHERE gctt_body = \'\'';
        $queries[] = 'UPDATE gems__comm_template_translations SET gctt_subject = NULL WHERE gctt_subject = \'\'';

        return $queries;
    }
}
