<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\Audit\AuditLog;
use Gems\AuthNew\Adapter\GemsTrackerAuthentication;
use Gems\AuthNew\LoginThrottleBuilder;
use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Gems\Communication\CommunicationRepository;
use Gems\Communication\Http\SmsClientInterface;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\SessionNamespace;
use Gems\Snippets\FormSnippetAbstract;
use Gems\User\Filter\DutchPhonenumberFilter;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Validator\Digits;
use Laminas\Validator\StringLength;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use MUtil\Bootstrap\Form\Element\Password;
use MUtil\Bootstrap\Form\Element\Text;
use MUtil\Model\TableModel;
use MUtil\Ra;
use MUtil\Validator\SimpleEmail;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 15:15:07
 */
class OwnAccountEditAuthSnippet extends FormSnippetAbstract
{
    private const MAX_ATTEMPTS = 10;

    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    protected User $currentUser;

    protected ServerRequestInterface $request;

    protected SessionInterface $session;

    private readonly SessionNamespace $sessionNamespace;

    private readonly FlashMessagesInterface $flash;

    private readonly bool $verify;

    private readonly string $defaultCountryCode;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        private readonly array $config,
        private readonly Adapter $db,
        private readonly UserLoader $userLoader,
        private readonly TranslatorInterface $translator,
        private readonly LoginThrottleBuilder $loginThrottleBuilder,
        private readonly CurrentUserRepository $currentUserRepository,
        private readonly MenuSnippetHelper $menuSnippetHelper,
        private readonly CommunicationRepository $communicationRepository,
        private readonly SmsClientInterface $smsClient,
        private readonly HelperAdapter $throttleCache,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->sessionNamespace = new SessionNamespace($this->session, __CLASS__);

        $this->verify = $this->sessionNamespace->has('new_email') || $this->sessionNamespace->has('new_phone');

        $this->flash = $this->request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $this->defaultCountryCode = '+' . $phoneUtil->getMetadataForRegion($this->config['account']['edit-auth']['defaultRegion'])->getCountryCode();
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        if ($changed) {
            $this->logChanges($changed);

            // Reload the current user data
            $user = $this->userLoader->getUser($this->currentUser->getLoginName(), $this->currentUser->getBaseOrganizationId());
            $this->currentUserRepository->setCurrentUser($user);


            $this->addMessage($this->_('Saved your setup data'));
        } else {
            $this->addMessage($this->_('No changes to save!'));
        }

        if ($this->cacheTags && ($this->cache instanceof HelperAdapter)) {
            $this->cache->invalidateTags([$this->cacheTags]);
        }

        $this->afterSaveRouteUrl = $this->request->getUri();
    }

    protected function validateForm(array $formData): bool
    {
        if (!parent::validateForm($formData)) {
            return false;
        }

        if (!$this->verify) {
            if ($this->checkPassword($formData['password']) === false) {
                return false;
            }

            $newEmail = trim($formData['gsf_email'] ?: '');
            $newPhone = trim($formData['gsf_phone_1'] ?: '');

            if ($newPhone === $this->defaultCountryCode) {
                $newPhone = '';
            }

            if ($newPhone !== $this->currentUser->getPhonenumber() && $newPhone !== '') {
                $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
                try {
                    $parsedPhone = $phoneUtil->parse($newPhone, $this->config['account']['edit-auth']['defaultRegion']);
                    $valid = $phoneUtil->isValidNumber($parsedPhone);
                } catch (NumberParseException) {
                    $valid = false;
                }

                if (!$valid) {
                    $this->addMessage($this->_('Please provide a valid telephone number'));
                    return false;
                }

                $newPhone = $phoneUtil->format($parsedPhone, PhoneNumberFormat::E164);
            }

            if ($newEmail !== $this->currentUser->getEmailAddress()) {
                $code = (string)random_int(100000, 999999);

                if (!$this->sendMailCode($newEmail, $code)) {
                    return false;
                }

                $this->sessionNamespace->set('new_email', [
                    'email' => $newEmail,
                    'secret' => $code,
                    'attempts' => 0,
                ]);
            }

            if ($newPhone !== ($this->currentUser->getPhonenumber() ?? '')) {
                if ($newPhone === '') {
                    $code = null;
                } else {
                    $code = (string)random_int(100000, 999999);

                    if (!$this->sendPhoneCode($newPhone, $code)) {
                        $this->sessionNamespace->unset('new_email'); // Might have been set above
                        return false;
                    }
                }

                $this->sessionNamespace->set('new_phone', [
                    'phone' => $newPhone,
                    'secret' => $code,
                    'attempts' => 0,
                ]);
            }

            if (!$this->sessionNamespace->has('new_email') && !$this->sessionNamespace->has('new_phone')) {
                $this->addMessage($this->_('No changes to save!'));
            }

            return false;
        } else {
            if ($this->sessionNamespace->has('new_email')) {
                $emailSession = $this->sessionNamespace->get('new_email');

                $newEmailSecret = $formData['new_email_secret'] ?: '';
                if ($newEmailSecret !== $emailSession['secret']) {
                    $emailSession['attempts']++;
                    if ($emailSession['attempts'] >= self::MAX_ATTEMPTS) {
                        $this->sessionNamespace->unset('new_email');
                        $this->sessionNamespace->unset('new_phone');

                        $this->addMessage($this->_('Too many failed attempts, please try again.'));
                        return false;
                    }

                    $this->sessionNamespace->set('new_email', $emailSession);

                    $this->addMessage($this->_('Please enter the 6-digit code we e-mailed to you.'));
                    return false;
                }
            }

            if ($this->sessionNamespace->has('new_phone') && $this->sessionNamespace->get('new_phone')['phone'] !== '') {
                $phoneSession = $this->sessionNamespace->get('new_phone');

                $newPhoneSecret = $formData['new_phone_secret'] ?: '';
                if ($newPhoneSecret !== $phoneSession['secret']) {
                    $phoneSession['attempts']++;
                    if ($phoneSession['attempts'] >= self::MAX_ATTEMPTS) {
                        $this->sessionNamespace->unset('new_email');
                        $this->sessionNamespace->unset('new_phone');

                        $this->addMessage($this->_('Too many failed attempts, please try again.'));
                        return false;
                    }

                    $this->sessionNamespace->set('new_phone', $phoneSession);

                    $this->addMessage($this->_('Please enter the 6-digit code we sent to you by SMS.'));
                    return false;
                }
            }
        }

        return true;
    }

    private function checkPassword(string $password): bool
    {
        $loginThrottle = $this->loginThrottleBuilder->buildLoginThrottle(
            $this->currentUser->getLoginName(),
            $this->currentUser->getBaseOrganizationId(),
        );

        $blockMinutes = $loginThrottle->checkBlock();
        if ($blockMinutes > 0) {
            $this->addMessage($this->blockMessage($blockMinutes));
            return false;
        }

        $result = GemsTrackerAuthentication::fromUser($this->db, $this->currentUser, $password)->authenticate();

        $blockMinutes = $loginThrottle->processAuthenticationResult($result);

        if (!$result->isValid()) {
            $this->addMessage($this->_('Please provide your current password.'));
            if ($blockMinutes > 0) {
                $this->addMessage($this->blockMessage($blockMinutes));
            }
            return false;
        }

        return true;
    }

    private function blockMessage(int $minutes)
    {
        return $this->translator->plural(
            'Too many failed attempts, please wait a minute.',
            'Too many failed attempts, please wait %count% minutes.',
            $minutes
        );
    }

    private function sendMailCode(string $mailAddress, string $code): bool
    {
        $rateLimiter = new RateLimiter($this->throttleCache);
        $rateLimitKey = sha1($this->currentUser->getUserId()) . '_email_change_confirm_max';
        $config = $this->config['account']['edit-auth']['throttle-email'];

        if ($rateLimiter->tooManyAttempts($rateLimitKey, $config['maxAttempts'])) {
            $this->addMessage($this->_('Too many attempts, please try again later'));
            return false;
        }

        $organization = $this->currentUser->getBaseOrganization();
        $language = $this->communicationRepository->getCommunicationLanguage($this->currentUser->getLocale());
        $templateId = $this->communicationRepository->getConfirmChangeEmailTemplate($organization);

        $variables = $this->communicationRepository->getUserMailFields($this->currentUser, $language);
        $variables += [
            'confirmation_code' => $code,
        ];

        $email = $this->communicationRepository->getNewEmail();
        $email->addTo(new Address($mailAddress, $this->currentUser->getFullName()));
        $email->addFrom(new Address($organization->getEmail()));

        $template = $this->communicationRepository->getTemplate($organization);
        $mailer = $this->communicationRepository->getMailer($organization->getEmail());

        $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
        $email->subject($mailTexts['subject'], $variables);
        $email->htmlTemplate($template, $mailTexts['body'], $variables);

        $mailer->send($email);

        $rateLimiter->hit($rateLimitKey, $config['maxAttemptsPerPeriod']);

        return true;
    }

    private function sendPhoneCode(string $phoneNumber, string $code): bool
    {
        $rateLimiter = new RateLimiter($this->throttleCache);
        $rateLimitKey = sha1($this->currentUser->getUserId()) . '_phone_change_confirm_max';
        $config = $this->config['account']['edit-auth']['throttle-sms'];

        if ($rateLimiter->tooManyAttempts($rateLimitKey, $config['maxAttempts'])) {
            $this->addMessage($this->_('Too many attempts, please try again later'));
            return false;
        }

        $organization = $this->currentUser->getBaseOrganization();
        $language = $this->communicationRepository->getCommunicationLanguage($this->currentUser->getLocale());
        $templateId = $this->communicationRepository->getConfirmChangePhoneTemplate($organization);

        $variables = $this->communicationRepository->getUserMailFields($this->currentUser, $language);
        $variables += [
            'confirmation_code' => $code,
        ];

        $texts = $this->communicationRepository->getCommunicationTexts($templateId, $language);

        if (! $texts) {
            $this->addMessage($this->_('No phone code send as no communication template exists!'));
            return false;
        }

        $twigLoader = new ArrayLoader([
            'message' => trim($texts['subject'] . PHP_EOL . PHP_EOL . $texts['body']),
        ]);
        $twig = new Environment($twigLoader, [
            'autoescape' => false,
        ]);
        $message = $twig->render('message', $variables);

        $filter = new DutchPhonenumberFilter();
        try {
            $this->smsClient->sendMessage($filter->filter($phoneNumber), $message);
        } catch (\Throwable) {
            $this->addMessage($this->_('An error occurred while sending the verification code to your mobile number'));
            return false;
        }

        $rateLimiter->hit($rateLimitKey, $config['maxAttemptsPerPeriod']);

        return true;
    }

    protected function onInValid()
    {
        $this->redirectRoute = $this->menuSnippetHelper->getRouteUrl('option.edit-auth');

        $this->flash->flash('own_account_edit_auth_input', Ra::filterKeys($this->formData, [
            'gsf_email',
            'gsf_phone_1',
        ]));

        foreach (Ra::flatten($this->_form->getMessages()) as $message) {
            $this->addMessage($message);
        }
    }

    protected function saveData(): int
    {
        $newEmail = null;
        if ($this->sessionNamespace->has('new_email')) {
            $newEmail = $this->sessionNamespace->get('new_email')['email'];
        }

        $newPhone = null;
        if ($this->sessionNamespace->has('new_phone')) {
            $newPhone = $this->sessionNamespace->get('new_phone')['phone'];
        }

        $model = new TableModel('gems__staff', 'staffModel');

        $newValues = [];
        if ($newEmail) {
            $newValues['gsf_email'] = $newEmail;
        }

        if ($newPhone !== null) {
            $newValues['gsf_phone_1'] = $newPhone;
        }

        $model->save($newValues, [
            'gsf_id_user' => $this->currentUser->getUserId(),
        ]);

        $this->sessionNamespace->unset('new_email');
        $this->sessionNamespace->unset('new_phone');

        return $model->getChanged();
    }

    /**
     * @param \Gems\Form $form
     */
    protected function addFormElements(mixed $form): void
    {
        if (!$this->verify) {
            $flashValues = $this->flash->getFlash('own_account_edit_auth_input');

            $element = new Text('gsf_email');
            $element
                ->setLabel($this->_('E-Mail'))
                ->setAttrib('size', 30)
                ->addValidator(new SimpleEmail())
                ->setRequired()
                ->setValue($flashValues['gsf_email'] ?? $this->currentUser->getEmailAddress())
            ;
            $form->addElement($element);

            $element = new Text('gsf_phone_1');
            $element
                ->setLabel($this->_('Mobile phone'))
                ->setValue($flashValues['gsf_phone_1'] ?? $this->currentUser->getPhonenumber() ?? $this->defaultCountryCode)
            ;
            $form->addElement($element);

            $element = new Password('password');
            $element
                ->setLabel($this->_('Current password'))
                ->setAttrib('renderPassword', true)
                ->setRequired()
            ;
            $form->addElement($element);

            return;
        }

        if ($this->sessionNamespace->has('new_email')) {
            $element = new \MUtil\Form\Element\Html('email_explanation');
            $element->div(sprintf(
                $this->_('Enter the 6-digit verification code we e-mailed to you to verify your new e-mail address %s'),
                $this->sessionNamespace->get('new_email')['email'],
            ));
            $form->addElement($element);

            $element = new \MUtil\Form\Element\Text('new_email_secret');
            $element
                ->setLabel($this->_('E-mail code'))
                ->setAttrib('size', 6)
                ->addValidator(new Digits())
                ->addValidator(new StringLength(6, 6))
            ;
            $form->addElement($element);
        }

        if ($this->sessionNamespace->has('new_phone')) {
            if ($this->sessionNamespace->get('new_phone')['phone'] !== '') {
                $element = new \MUtil\Form\Element\Html('phone_explanation');
                $element->div(sprintf(
                    $this->_('Enter the 6-digit verification code we sent to you by SMS to verify your new mobile number %s'),
                    $this->sessionNamespace->get('new_phone')['phone'],
                ));
                $form->addElement($element);

                $element = new \MUtil\Form\Element\Text('new_phone_secret');
                $element
                    ->setLabel($this->_('Phone code'))
                    ->setAttrib('size', 6)
                    ->addValidator(new Digits())
                    ->addValidator(new StringLength(6, 6))
                ;
                $form->addElement($element);
            } else {
                $element = new \MUtil\Form\Element\Html('phone_explanation');
                $element->div($this->_('By clicking Save you will erase your currently configured phone number'));
                $form->addElement($element);
            }
        }

        $element = new \MUtil\Form\Element\FakeSubmit('cancel');
        $element
            ->setLabel($this->_('Cancel'))
            ->setAttrib('class', 'button btn btn-primary')
        ;
        $form->addElement($element);
    }

    protected function onFakeSubmit()
    {
        if (isset($this->formData['cancel']) && $this->formData['cancel']) {
            $this->sessionNamespace->unset('new_email');
            $this->sessionNamespace->unset('new_phone');

            $this->redirectRoute = $this->menuSnippetHelper->getRouteUrl('option.edit-auth');
        }
    }

    /**
     * The message to display when the change is not allowed
     *
     * @return string
     */
    protected function getNotAllowedMessage()
    {
        return $this->_('System account can not be changed.');
    }

    /**
     * If the current user is the system user, present a message and don't allow to edit
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if ($this->currentUser->getUserId() == \Gems\User\UserLoader::SYSTEM_USER_ID) {
            $this->addMessage($this->getNotAllowedMessage());
            return false;
        }

        return parent::hasHtmlOutput();
    }
}
