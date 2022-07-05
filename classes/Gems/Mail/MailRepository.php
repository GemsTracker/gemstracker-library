<?php

namespace Gems\Mail;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class MailRepository
{
    private Adapter $db;

    public function __construct(Adapter $db, array $config)
    {
        $this->db = $db;
    }

    /**
     * Get the prefered template language
     * @return string language code
     */
    public function getMailLanguage(string $language = null): string
    {
        if (isset($this->config['email']['multiLanguage']) && $this->config['email']['multiLanguage'] === true && $language) {
            return $language;
        }

        return $this->getDefaultLanguage();
    }

    protected function getDefaultLanguage(): string
    {
        if (isset($this->config['locale'], $this->config['locale']['default'])) {
            return $this->config['locale']['default'];
        }

        return 'en';
    }

    public function getMailTexts(int $templateId, ?string $language=null): ?array
    {
        $language = $this->getMailLanguage($language);

        $sql = new Sql($this->db);
        $select = $sql->select('gems__comm_template_translations');
        $select->where([
            'gctt_id_template' => $templateId,
            'gctt_lang' => $language,
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            $template = $result->current();
            if ($template && !empty($template['gctt_subject'])) {
                return [
                    'subject' => $template['gctt_subject'],
                    'gctt_body' => $template['gctt_body'],
                ];
            }
        }

        if ($language !== $this->getDefaultLanguage()) {
            return $this->getMailTexts($templateId, $this->getDefaultLanguage());
        }

        return null;
    }

    public function getTemplate(\Gems_User_Organization $organization): string
    {
        $templateName = $organization->getStyle();
        if ($templateName !== null) {
            return 'mail::' . $organization->getStyle();
        }
        return 'default::mail';
    }
}