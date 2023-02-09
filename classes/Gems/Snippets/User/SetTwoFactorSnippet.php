<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\AuthNew\LoginStatusTracker;
use Gems\AuthTfa\Method\OtpMethodInterface;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\Layout\LayoutSettings;
use Gems\MenuNew\RouteHelper;
use Gems\SessionNamespace;
use Gems\Snippets\ZendFormSnippetAbstract;
use Gems\User\User;
use Mezzio\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 29-Jun-2018 19:05:43
 */
class SetTwoFactorSnippet extends ZendFormSnippetAbstract
{
    protected User $user;

    protected SessionInterface $session;

    private readonly SessionNamespace $sessionNamespace;

    private ?OtpMethodInterface $tfaMethod = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        private readonly array $config,
        private readonly OtpMethodBuilder $otpMethodBuilder,
        private readonly RouteHelper $routeHelper,
        private readonly LayoutSettings $layoutSettings,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->sessionNamespace = new SessionNamespace($this->session, __CLASS__);

        if (!$this->sessionNamespace->has('keys')) {
            $this->sessionNamespace->set('keys', []);
        }

        if (LoginStatusTracker::make($this->session, $this->user)->isRequireAppTotpActive()) {
            $this->layoutSettings->disableMenu();
        }
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        $this->saveLabel = $this->_('Save new Two Factor Setup');

        $methods = $this->getTwoFactorMethods();

        if (count($methods) !== 1) {
            $methodElement = $form->createElement('select', 'twoFactorMethod', [
                'label' => $this->_('Two Factor method'),
                'multiOptions' => $methods,
                'onchange' => 'this.form.submit();',
            ]);
        } else {
            $methodElement = $form->createElement('exhibitor', 'twoFactorMethod', [
                'label' => $this->_('Two Factor method'),
                'value' => reset($methods),
            ]);
        }
        $form->addElement($methodElement);

        if ($this->tfaMethod) {
            $this->tfaMethod->addSetupFormElements($form, $this->formData);
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        //$this->authenticator = $this->user->getTwoFactorAuthenticator();
    }

    /**
     * Return a list of Two Factor methods with Authenticator class name as key and label as value
     *
     * @return array
     */
    protected function getTwoFactorMethods()
    {
        $enabledMethods = array_keys($this->config['twofactor']['methods']);

        if ($this->config['twofactor']['requireAppTotp']) {
            $enabledMethods = array_intersect($enabledMethods, ['AppTotp']);
        }

        // For now register labels here. Could be added as class method per authenticator at loading all authenticator classes cost

        $registeredMethods = [
            'MailHotp' => $this->_('Mail'),
            'SmsHotp' => $this->_('SMS'),
            'AppTotp' => $this->_('Authenticator app'),
        ];

        return array_intersect_key($registeredMethods, array_flip($enabledMethods));
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues(): array
    {
        if ($this->formData) {
            return $this->formData;
        }

        /*if (! $authKey) {
            $authKey = $this->authenticator->createSecret();

            $this->addMessage(sprintf(
                    $this->_('A new random two factor key was saved for %s.'),
                    $this->user->getFullName()
                    ));

            $this->addMessage($this->_('Click save to enable two factor authentication.'));
            $this->user->setTwoFactorKey($this->authenticator, $authKey, false);

            // Set on save
            $output['twoFactorEnabled'] = 1;
        } else {*/
        // }
        $output['twoFactorKey'] = null;

        return $output;
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->_('two factor setup');
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if (! ($this->user->hasTwoFactor() || $this->user->canSaveTwoFactorKey())) {
            $this->addMessage(sprintf(
                $this->_('A two factor key cannot be set for %s.'),
                $this->user->getFullName()
            ));
            return false;
        }
        return parent::hasHtmlOutput();
    }

    protected function loadFormData(): array
    {
        parent::loadFormData();

        $this->loadAuthenticator();
        $this->loadFormKey();

        return $this->formData;
    }

    /**
     * Load the selected two factor method, or the first available
     *
     * @throws \Gems\Exception\Coding
     */
    protected function loadAuthenticator()
    {
        $tfaMethods = $this->getTwoFactorMethods();

        if (isset($this->formData['twoFactorMethod']) && isset($tfaMethods[$this->formData['twoFactorMethod']])) {
            $this->tfaMethod = $this->otpMethodBuilder->buildSpecificOtpMethod($this->formData['twoFactorMethod'], $this->user);
            return;
        }

        if ($this->user->hasTfaConfigured() && $tfaMethod = $this->otpMethodBuilder->buildOtpMethod($this->user)) {
            $tfaMethodName = (new \ReflectionClass($tfaMethod))->getShortName();
            if (isset($tfaMethods[$tfaMethodName])) {
                $this->tfaMethod = $tfaMethod;
                $this->formData['twoFactorMethod'] = count($tfaMethods) === 1 ? $tfaMethods[$tfaMethodName] : $tfaMethodName;
                return;
            }
        }

        // Get the first available TFA method
        $this->tfaMethod = $this->otpMethodBuilder->buildSpecificOtpMethod(
            array_key_first($tfaMethods),
            $this->user
        );
    }

    /**
     * Load the current authenticator secret, or generate a new one for the currently selected authenticator
     */
    protected function loadFormKey()
    {
        if ($this->tfaMethod) {
            $key = $this->getKeyFromSession();
            if ($key) {
                $this->formData['twoFactorKey'] = $key;
                return;
            }

            $this->generateNewKey();
        }
    }

    private function getKeyFromSession(): ?string
    {
        $sessionKeys = $this->sessionNamespace->get('keys', []);
        $authenticatorClassName = get_class($this->tfaMethod);
        return $sessionKeys[$authenticatorClassName] ?? null;
    }

    private function generateNewKey(): void
    {
        if (!$this->tfaMethod) {
            throw new \Exception();
        }

        $sessionKeys = $this->sessionNamespace->get('keys', []);
        $authenticatorClassName = get_class($this->tfaMethod);

        $authKey = $this->tfaMethod->generateSecret();
        $this->formData['twoFactorKey'] = $authKey;
        $sessionKeys[$authenticatorClassName] = $authKey;
        $this->sessionNamespace->set('keys', $sessionKeys);
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $newKey = $this->getKeyFromSession();
        $code = $this->formData['twoFactorCode'] ?? null;
        $this->formData['twoFactorCode'] = '';

        if ($newKey && !empty($code)) {
            $className = (new \ReflectionClass($this->tfaMethod))->getShortName();
            $otpMethod = $this->otpMethodBuilder->buildSpecificOtpMethod($className, $this->user);
            if (!$otpMethod->verifyForSecret($newKey, $code)) {
                $this->addMessage($this->_('Please enter the TFA passcode from the new TFA'));
                return 0;
            }

            //$this->user->setTwoFactorKey($this->authenticator, $newKey, $enabled);
            $this->otpMethodBuilder->setOtpMethodAndSecret(
                $this->user,
                $className,
                $newKey
            );
            LoginStatusTracker::make($this->session, $this->user)->setRequireAppTotpActive(false);
            $this->layoutSettings->enableMenu();

            $this->addMessage($this->_('Two factor authentication setting saved.'));
            $this->generateNewKey();
        }

        return 0;
    }

    protected function onFakeSubmit()
    {
        if (isset($this->formData['new_key']) && $this->formData['new_key']) {
            $this->generateNewKey();
        }

        $this->redirectRoute = $this->routeHelper->getRouteUrl('option.two-factor');
    }
}
