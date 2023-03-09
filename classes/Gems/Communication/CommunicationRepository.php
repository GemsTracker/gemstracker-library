<?php

namespace Gems\Communication;

use Gems\Communication\Http\SmsClientInterface;
use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Mail\MailBouncer;
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
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use GuzzleHttp\Client;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Messenger\MessageBusInterface;

class CommunicationRepository
{
    public function __construct(
        protected ResultFetcher $resultFetcher,
        protected CachedResultFetcher $cachedResultFetcher,
        protected TemplateRendererInterface $template,
        protected Translator $translator,
        protected TokenSelect $tokenSelect,
        protected ManualMailerFactory $mailerFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected MessageBusInterface $messageBus,
        MailBouncer $mailBouncer,
        protected array $config)
    {}

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

    public function getCombinedTransport(): TransportInterface
    {
        $allTransports = $this->getTransports();
        return new Transports($allTransports);
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

        $select = $this->resultFetcher->getSelect('gems__comm_template_translations');
        $select->where([
            'gctt_id_template' => $templateId,
            'gctt_lang' => $language,
        ]);

        $template = $this->resultFetcher->fetchRow($select);
        if ($template && !empty($template['gctt_subject'])) {
            return [
                'subject' => $template['gctt_subject'],
                'body' => $template['gctt_body'],
            ];
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

    protected function getMailDsnFromDbServerInfo(array $serverInfo): string
    {
        $dsn = sprintf('smtp://%s:%s', $serverInfo['gms_server'], $serverInfo['gms_port']);
        if (isset($serverInfo['gms_user'], $serverInfo['gsm_password'])) {
            $dsn = sprintf('smtp://%s:%s@%s:%s', $serverInfo['gms_user'], $serverInfo['gms_password'], $serverInfo['gms_server'], $serverInfo['gms_port']);
        }
        return $dsn;
    }

    public function getMailer(): Mailer
    {
        $combinedTransport = $this->getCombinedTransport();
        return new Mailer($combinedTransport, $this->messageBus, $this->eventDispatcher);
    }

    protected function getMailServers(): ?array
    {
        $select = $this->resultFetcher->getSelect('gems__mail_servers');
        $select->columns(['gms_id_server', 'gms_server', 'gms_port', 'gms_user', 'gms_password']);
        return $this->resultFetcher->fetchAll($select);
    }

    public function getNewEmail(): TemplatedEmail
    {
        $email = new TemplatedEmail($this->template);
        return $email;
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

    public function getResetTfaTemplate(Organization $organization): ?int
    {
        $templateId = $organization->getResetTfaTemplate();
        if ($templateId) {
            return (int)$templateId;

        } elseif ($this->config['email']['resetTfaTemplate']) {
            return (int)$this->getTemplateIdFromCode($this->config['email']['resetTfaTemplate']);
        }

        return null;
    }

    public function getRespondentMailFields(Respondent $respondent, string $language = null): array
    {
        $mailFieldCreator = new RespondentMailFields($respondent, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }

    public function getRespondentMailCodes(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__mail_codes');
        $select->columns([
            'gmc_id',
            'gmc_mail_to_target',
        ])->where([
            'gmc_for_respondents' => 1,
            'gmc_active' => 1,
        ]);

        $result = $this->cachedResultFetcher->fetchPairs(
            'respondentMailCodes',
            $select,
            null,
            ['mailcodes'],
        );
        if ($result) {
            ksort($result);
            return $result;
        }
        return [];
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
        $select = $this->resultFetcher->getSelect('gems__comm_templates');
        $select->where(['gct_code' => $code])
            ->columns(['gct_id_template']);

        return $this->resultFetcher->fetchOne($select);
    }

    public function getTokenMailFields(Token $token, string $language = null): array
    {
        $mailFieldCreator = new TokenMailFields($token, $this->config, $this->translator, $this->tokenSelect);
        return $mailFieldCreator->getMailFields($language);
    }

    /**
     * @return TransportInterface[]
     */
    public function getTransports(): array
    {
        $transports = [];
        if (isset($this->config['email']['dsn'])) {
            $transports['config'] = Transport::fromDsn($this->config['email']['dsn'], $this->eventDispatcher);
        }

        $mailservers = $this->getMailServers();
        if ($mailservers) {
            foreach($mailservers as $mailserver) {
                $dsn = $this->getMailDsnFromDbServerInfo($mailserver);
                $transports[$mailserver['gms_id_server']] = Transport::fromDsn($dsn, $this->eventDispatcher);
            }
        }

        return $transports;
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