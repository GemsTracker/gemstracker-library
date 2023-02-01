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

use Gems\AuthTfa\Method\OtpMethodInterface;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\SessionNamespace;
use Gems\User\User;
use Mezzio\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Snippets\Zend\ZendFormSnippetAbstract;
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
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->sessionNamespace = new SessionNamespace($this->session, __CLASS__);

        if ($this->sessionNamespace->has('keys')) {
            $this->sessionNamespace->set('keys', []);
        }
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        $this->saveLabel = $this->_('Save Two Factor Setup');

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
                'onchange' => 'this.form.submit();',
            ]);
        }
        $form->addElement($methodElement);

        if ($this->tfaMethod) {
            $this->tfaMethod->addSetupFormElements($form, $this->formData);
        }

        $options = [
            'label' => $this->_('Enabled'),
        ];
        if (!$this->user->canSaveTwoFactorKey()) {
            $options['disabled'] = true;
        }
        if ($this->config['twofactor']['required']) {
            $options['required'] = true;
        }

        $keyElement = $form->createElement('Checkbox', 'twoFactorEnabled', $options);
        $form->addElement($keyElement);
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
            'AppTotp' => $this->_('Google Authenticator'),
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

        $output['twoFactorEnabled'] = 0;
        if ($this->user->getTwoFactorAuthenticator() && $this->user->isTwoFactorEnabled()) {
            $output['twoFactorEnabled'] = 1;
        }

        if (! $output['twoFactorEnabled']) {
            $this->addMessage($this->_('Two factor authentication not active!'));
        }
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

        if ($tfaMethod = $this->otpMethodBuilder->buildOtpMethod($this->user)) {
            $tfaMethodName = (new \ReflectionClass($tfaMethod))->getShortName();
            if (isset($tfaMethods[$tfaMethodName])) {
                $this->tfaMethod = $tfaMethod;
                $this->formData['twoFactorMethod'] = $tfaMethodName;
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
            $sessionKeys = $this->sessionNamespace->get('keys', []);
            $authenticatorClassName = get_class($this->tfaMethod);
            if (isset($sessionKeys[$authenticatorClassName])) {
                $this->formData['twoFactorKey'] = $sessionKeys[$authenticatorClassName];
                return;
            }

            // No key exists. Generate key
            $authKey = $this->tfaMethod->generateSecret();
            $this->formData['twoFactorKey'] = $authKey;
            $sessionKeys[$authenticatorClassName] = $authKey;
            $this->sessionNamespace->set('keys', $sessionKeys);
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $newKey = $this->formData['twoFactorKey'];

        if ($newKey) {
            if ($this->user->canSaveTwoFactorKey()) {
                $enabled = $this->formData['twoFactorEnabled'] ? 1 : 0;
            } else {
                $enabled = null;
            }

            //$this->user->setTwoFactorKey($this->authenticator, $newKey, $enabled);
            $this->otpMethodBuilder->setOtpMethod(
                $this->user,
                (new \ReflectionClass($this->tfaMethod))->getShortName(),
                $enabled
            );

            $this->addMessage($this->_('Two factor authentication setting saved.'));
        }

        return 0;
    }
}
