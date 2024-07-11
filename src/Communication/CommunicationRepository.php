<?php

namespace Gems\Communication;

use Gems\Communication\Http\SmsClientInterface;
use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Helper\Env;
use Gems\Mail\MailBouncer;
use Gems\Mail\ManualMailerFactory;
use Gems\Mail\OrganizationMailFields;
use Gems\Mail\ProjectMailFields;
use Gems\Mail\RespondentMailFields;
use Gems\Mail\TemplatedEmail;
use Gems\Mail\TokenMailFields;
use Gems\Mail\UserMailFields;
use Gems\Mail\UserPasswordMailFields;
use Gems\Repository\CommFieldRepository;
use Gems\Tracker\Respondent;
use Gems\Tracker\Token;
use Gems\User\Organization;
use Gems\User\User;
use Laminas\Db\Sql\Expression;
use Mezzio\Template\TemplateRendererInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Mailer;
use GuzzleHttp\Client;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Zalt\Base\TranslatorInterface;

class CommunicationRepository
{
    const MAIL_NO_ENCRYPT = 0;
    const MAIL_SSL = 1;
    const MAIL_TLS = 2;

    protected Client|null $smsClient = null;

    public function __construct(
        protected ResultFetcher $resultFetcher,
        protected CachedResultFetcher $cachedResultFetcher,
        protected TemplateRendererInterface $template,
        protected TranslatorInterface $translator,
        protected ManualMailerFactory $mailerFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected MessageBusInterface $messageBus,
        protected readonly CommFieldRepository $commFieldRepository,
        MailBouncer $mailBouncer,
        protected array $config)
    {
        if ($this->eventDispatcher instanceof EventDispatcher) {
            $this->eventDispatcher->addListener(MessageEvent::class, [$this, 'addTransportHeaderToMail']);
        }
    }

    public function addTransportHeaderToMail(MessageEvent $event)
    {
        $mail = $event->getMessage();
        if (!$mail instanceof Email) {
            return;
        }
        $from = $mail->getFrom();
        $firstFrom = reset($from);
        if ($firstFrom instanceof Address) {
            $firstFrom = $firstFrom->getAddress();
        }
        $transportId = $this->getTransportIdFromFrom($firstFrom);
        if (isset($transportId)) {
            $headers = $mail->getHeaders();
            $headers->addHeader('X-Transport', $transportId);
        }
    }

    public function filterRawVariables(string $text, string $type): string
    {
        $rawFields = $this->commFieldRepository->getRawCommFields($type);
        $curlyFields = array_map(function($fieldName) {
            return '{{' . $fieldName . '}}';
        }, $rawFields);
        $rawedFields = array_map(function($fieldName) {
            return '{{' . $fieldName . '|raw}}';
        }, $rawFields);

        return str_replace($curlyFields, $rawedFields, $text);
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
        $select->join('gems__comm_templates', 'gctt_id_template = gct_id_template', ['gct_target'])
            ->where([
                'gctt_id_template' => $templateId,
                'gctt_lang' => $language,
            ]);

        $template = $this->resultFetcher->fetchRow($select);
        if ($template && !empty($template['gctt_subject'])) {
            return [
                'subject' => $template['gctt_subject'],
                'body' => $this->filterRawVariables($template['gctt_body'], $template['gct_target']),
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

    protected function getMailServers(): array
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

    public function getProjectEmailAddress(): ?string
    {
        if ($this->config['email']['site']) {
            return $this->config['email']['site'];
        }
        return null;
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

    public function getConfirmChangeEmailTemplate(Organization $organization): ?int
    {
        $templateId = $organization->getConfirmChangeEmailTemplate();
        if ($templateId) {
            return (int)$templateId;

        } elseif ($this->config['email']['confirmChangeEmailTemplate']) {
            return (int)$this->getTemplateIdFromCode($this->config['email']['confirmChangeEmailTemplate']);
        }

        return null;
    }

    public function getConfirmChangePhoneTemplate(Organization $organization): ?int
    {
        $templateId = $organization->getConfirmChangePhoneTemplate();
        if ($templateId) {
            return (int)$templateId;

        } elseif ($this->config['email']['confirmChangePhoneTemplate']) {
            return (int)$this->getTemplateIdFromCode($this->config['email']['confirmChangePhoneTemplate']);
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

            if (isset($this->config['sms'][$clientId]['class'])) {
                $httpClient = $this->getHttpClient($this->config['sms'][$clientId]);
                if (class_exists($this->config['sms'][$clientId]['class'])) {
                    $class = $this->config['sms'][$clientId]['class'];
                    $smsClient = new $class($this->config['sms'][$clientId], $httpClient);
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

    public function getTemplateName(int $templateId): string
    {
        $select = $this->resultFetcher->getSelect('gems__comm_templates');
        $select->where(['gct_id_template' => $templateId,]);

        $template = $this->resultFetcher->fetchRow($select);
        if ($template && !empty($template['gct_name'])) {
            return $template['gct_name'];
        }

        return '(unknwon template)';
    }

    public function getTokenMailFields(Token $token, string $language = null): array
    {
        $mailFieldCreator = new TokenMailFields($token, $this->translator, $this->resultFetcher, $this->config);
        return $mailFieldCreator->getMailFields($language);
    }

    protected function getTransportIdFromFrom(string $from): int|null
    {
        $select = $this->resultFetcher->getSelect('gems__mail_servers');
        $platform = $this->resultFetcher->getPlatform();
        $select
            ->columns(['gms_id_server'])
            ->where([
                $platform->quoteValue($from) . ' LIKE gms_from',
            ])
            ->order(new Expression('LENGTH(gms_from) DESC'))
            ->limit(1);

        return $this->resultFetcher->fetchOne($select);
    }

    /**
     * @return TransportInterface[]
     */
    public function getTransports(): array
    {
        $transports = [];
        $dsn = Env::get('MAILER_DSN');
        if (!$dsn) {
            $dsn = $this->config['email']['dsn'] ?? null;
        }

        if ($dsn) {
            $transports['config'] = Transport::fromDsn($dsn, $this->eventDispatcher);
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
