<?php

namespace Gems\Communication;

use Gems\Communication\Http\SmsClientInterface;
use Gems\Mail\ManualMailerFactory;
use Gems\Mail\OrganizationMailFields;
use Gems\Mail\ProjectMailFields;
use Gems\Mail\RespondentMailFields;
use Gems\Mail\TemplatedEmail;
use Gems\Mail\TokenMailFields;
use Gems\Mail\UserMailFields;
use Gems\Mail\UserPasswordMailFields;
use Gems\Tracker\Respondent;
use Gems\Tracker\Token;
use Gems\Tracker\Token\TokenSelect;
use Gems\User\Organization;
use Gems\User\User;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Translate\Translator;
use Symfony\Component\Mailer\Mailer;
use GuzzleHttp\Client;

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

    public function getCreateAccountTemplate(Organization $organization): ?int
    {
        $templateId = $organization->getCreateAccountTemplate();
        if ($templateId) {
            return (int)$templateId;

        } elseif ($this->config['email']['createAccountTemplate']) {
            return (int)$this->getTemplateIdFromCode($this->config['email']['createAccountTemplate']);
        }

        return null;
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

    public function getHttpClient($config=null)
    {
        $clientConfig = [];
        if ($config && isset($config['uri'])) {
            $clientConfig['base_uri'] = $config['uri'];
        }
        if ($config && isset($config['proxy'])) {
            $clientConfig['proxy'] = $config['proxy'];
        }

        return new Client($clientConfig);
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

    public function getOrganizationMailFields(Organization $organization): array
    {
        $mailFieldCreator = new OrganizationMailFields($organization, $this->config);
        return $mailFieldCreator->getMailFields();
    }

    public function getProjectMailFields(): array
    {
        $mailFieldCreator = new ProjectMailFields($this->config);
        return $mailFieldCreator->getMailFields();
    }

    public function getResetPasswordTemplate(Organization $organization): ?int
    {
        $templateId = $organization->getResetPasswordTemplate();
        if ($templateId) {
            return (int)$templateId;

        } elseif ($this->config['email']['resetPasswordTemplate']) {
            return (int)$this->getTemplateIdFromCode($this->config['email']['resetPasswordTemplate']);
        }

        return null;
    }

    public function getRespondentMailFields(Respondent $respondent, string $language = null): array
    {
        $mailFieldCreator = new RespondentMailFields($respondent, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }

    /**
     * @return SmsClientInterface
     * @throws \Gems\Exception|
     */
    public function getSmsClient($clientId='sms')
    {
        if (!$this->smsClient) {

            if (isset($this->config['sms'], $config['sms'][$clientId], $config['sms'][$clientId]['class'])) {
                $httpClient = $this->getHttpClient($config[$clientId]);
                if (class_exists($config[$clientId]['class'])) {
                    $class = $config[$clientId]['class'];
                    $smsClient = new $class($config[$clientId], $httpClient);
                }
                if (!($smsClient instanceof SmsClientInterface)) {
                    throw new \Gems\Exception('Sms client could not be loaded from config');
                }
                $this->smsClient = $smsClient;
            }
        }
        return $this->smsClient;
    }

    public function getTemplate(Organization $organization): string
    {
        $templateName = $organization->getStyle();
        if ($templateName !== null) {
            return 'mail::' . $organization->getStyle();
        }
        return 'default::mail';
    }

    public function getTemplateIdFromCode(string $code): ?int
    {
        $sql = new Sql($this->db);
        $select = $sql->select('gems__comm_templates');
        $select->where(['gct_code' => $code])
            ->columns(['gct_id_template']);


        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        if ($result->valid() && $result->current()) {
            $template = $result->current();
            return $template['gct_id_template'];
        }

        return null;
    }

    public function getTokenMailFields(Token $token, string $language = null): array
    {
        $mailFieldCreator = new TokenMailFields($token, $this->config, $this->translator, $this->tokenSelect);
        return $mailFieldCreator->getMailFields($language);
    }

    public function getUserMailFields(User $user, string $language = null): array
    {
        $mailFieldCreator = new UserMailFields($user, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }

    public function getUserPasswordMailFields(User $user, string $language = null): array
    {
        $mailFieldCreator = new UserPasswordMailFields($user, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }
}