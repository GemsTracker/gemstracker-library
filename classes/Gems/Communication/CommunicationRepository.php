<?php

namespace Gems\Communication;

use Gems\Mail\ManualMailerFactory;
use Gems\Mail\TemplatedEmail;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Mezzio\Template\TemplateRendererInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;

class CommunicationRepository
{
    private Adapter $db;

    private array $config;

    private TemplateRendererInterface $template;

    public function __construct(Adapter $db, TemplateRendererInterface $template, array $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->template = $template;
    }

    /**
     * Get the prefered template language
     * @return string language code
     */
    public function getCommunicationLanguage(string $language = null): string
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

    public function getCommunicationTexts(int $templateId, ?string $language=null): ?array
    {
        $language = $this->getCommunicationLanguage($language);

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
            return $this->getCommunicationTexts($templateId, $this->getDefaultLanguage());
        }

        return null;
    }

    public function getMailer(string $from): Mailer
    {
        $factory = new ManualMailerFactory($this->db, $this->config);
        return $factory->getMailer($from);
    }

    public function getNewEmail(): TemplatedEmail
    {
        return new TemplatedEmail($this->template);
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