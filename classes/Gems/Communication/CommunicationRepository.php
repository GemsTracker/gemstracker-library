<?php

namespace Gems\Communication;

use Gems\Mail\ManualMailerFactory;
use Gems\Mail\OrganizationMailFields;
use Gems\Mail\ProjectMailFields;
use Gems\Mail\RespondentMailFields;
use Gems\Mail\TemplatedEmail;
use Gems\Mail\TokenMailFields;
use Gems\Mail\UserMailFields;
use Gems\Mail\UserPasswordMailFields;
use Gems\Tracker\Token\TokenSelect;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Translate\Translator;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;

class CommunicationRepository
{
    private Adapter $db;

    private array $config;

    private TemplateRendererInterface $template;

    private Translator $translator;
    private TokenSelect $tokenSelect;

    public function __construct(Adapter $db, TemplateRendererInterface $template, Translator $translator, TokenSelect $tokenSelect, array $config)
    {
        $this->db = $db;
        $this->template = $template;
        $this->translator = $translator;
        $this->tokenSelect = $tokenSelect;
        $this->config = $config;
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

    public function getOrganizationMailFields(\Gems_User_Organization $organization): array
    {
        $mailFieldCreator = new OrganizationMailFields($organization, $this->config);
        return $mailFieldCreator->getMailFields();
    }

    public function getProjectMailFields(): array
    {
        $mailFieldCreator = new ProjectMailFields($this->config);
        return $mailFieldCreator->getMailFields();
    }

    public function getRespondentMailFields(\Gems_Tracker_Respondent $respondent, string $language = null): array
    {
        $mailFieldCreator = new RespondentMailFields($respondent, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }

    public function getTemplate(\Gems_User_Organization $organization): string
    {
        $templateName = $organization->getStyle();
        if ($templateName !== null) {
            return 'mail::' . $organization->getStyle();
        }
        return 'default::mail';
    }

    public function getTokenMailFields(\Gems_Tracker_Token $token, string $language = null): array
    {
        $mailFieldCreator = new TokenMailFields($token, $this->config, $this->translator, $this->tokenSelect);
        return $mailFieldCreator->getMailFields($language);
    }

    public function getUserMailFields(\Gems_User_User $user, string $language = null): array
    {
        $mailFieldCreator = new UserMailFields($user, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }

    public function getUserPasswordMailFields(\Gems_User_User $user, string $language = null): array
    {
        $mailFieldCreator = new UserPasswordMailFields($user, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }
}