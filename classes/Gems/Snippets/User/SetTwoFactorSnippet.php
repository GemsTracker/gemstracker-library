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

use Gems\Snippets\FormSnippetAbstract;
use Gems\User\TwoFactor\TwoFactorAuthenticatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 29-Jun-2018 19:05:43
 */
class SetTwoFactorSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var TwoFactorAuthenticatorInterface
     */
    protected $authenticator;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Zend_Session_Namespace
     */
    protected $_session;

    /**
     *
     * @var \Gems_User_User
     */
    protected $user;

    /**
     *
     * @var \Gems_User_UserLoader
     */
    protected $userLoader;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        $this->saveLabel = $this->_('Save Two Factor Setup');

        $methods = $this->getTwoFactorMethods();

        if (count($methods) > 1) {
            $options = [
                'label' => $this->_('Two Factor method'),
                'multiOptions' => $methods,
                'onchange' => 'this.form.submit();',
            ];
            $methodElement = $form->createElement('select', 'twoFactorMethod', $options);
            $form->addElement($methodElement);
        } else {
            $firstValue = reset($methods);
            $options = [
                'label' => $this->_('Two Factor method'),
                'value' => $firstValue,
                'onchange' => 'this.form.submit();',
            ];
            $methodElement = $form->createElement('exhibitor', 'twoFactorMethod', $options);
            $form->addElement($methodElement);
        }

        $canEnableTwoFactor = true;

        if ($this->authenticator) {
            try {
                $this->authenticator->addSetupFormElements($form, $this->user, $this->formData);
            } catch (\Gems_Exception $e) {

                $this->addMessage($e->getMessage());
                $canEnableTwoFactor = false;
            }
        }

        if (!$this->user->canSaveTwoFactorKey()) {
            $canEnableTwoFactor = false;
        }

        $options = [
            'label' => $this->_('Enabled'),
        ];
        if (!$canEnableTwoFactor) {
            $options['disabled'] = true;
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

        $this->userLoader = $this->loader->getUserLoader();

        $this->_session = new \Zend_Session_Namespace(__CLASS__);
        if (!isset($this->_session->keys)) {
            $this->_session->keys = [];
        }


        //$this->authenticator = $this->user->getTwoFactorAuthenticator();
    }

    /**
     * Return a list of Two Factor methods with Authenticator class name as key and label as value
     *
     * @return array
     */
    protected function getTwoFactorMethods()
    {
        $enabledMethods = $this->project->getTwoFactorMethods();

        // For now register labels here. Could be added as class method per authenticator at loading all authenticator classes cost

        $registeredMethods = [
            'MailTotp' => $this->_('Mail'),
            'MailHotp' => $this->_('Mail'),
            'GoogleAuthenticator' => $this->_('Google Authenticator'),
            'SmsTotp' => $this->_('SMS'),
            'SmsHotp' => $this->_('SMS'),
        ];

        return array_intersect_key($registeredMethods, array_flip($enabledMethods));
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues()
    {
        if ($this->formData) {
            return $this->formData;
        }

        if ($this->user->hasTwoFactor()) {
            $authKey = $this->user->getTwoFactorKey();

        } else {
            $authKey   = null;
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
        $output['twoFactorKey']     = $authKey;

        return $output;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        // Show nothing
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
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
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

    protected function loadFormData()
    {
        parent::loadFormData();

        $this->loadAuthenticator();
        $this->loadFormKey();
    }

    /**
     * Load the selected two factor method, or the first available
     *
     * @throws \Gems_Exception_Coding
     */
    protected function loadAuthenticator()
    {
        if (isset($this->formData['twoFactorMethod'])) {
            $this->authenticator = $this->userLoader->getTwoFactorAuthenticator($this->formData['twoFactorMethod']);
            return;
        }

        $authenticators = $this->getTwoFactorMethods();

        if ($authenticator = $this->user->getTwoFactorAuthenticator()) {
            $authenticatorName = (new \ReflectionClass($authenticator))->getShortName();
            if (isset($authenticators[$authenticatorName])) {
                $this->authenticator = $authenticator;
                $this->formData['twoFactorMethod'] = $authenticatorName;
                return;
            }
        }

        // Get the first available authenticator
        //$authenticators = $this->getTwoFactorMethods();
        $firstAuthenticator = key($authenticators);
        $this->authenticator = $this->userLoader->getTwoFactorAuthenticator($firstAuthenticator);
    }

    /**
     * Load the current authenticator secret, or generate a new one for the currently selected authenticator
     */
    protected function loadFormKey()
    {
        if ($this->authenticator instanceof TwoFactorAuthenticatorInterface) {
            if ($this->authenticator == $this->user->getTwoFactorAuthenticator()) {
                if ($key = $this->user->getTwoFactorKey()) {
                    $this->formData['twoFactorKey'] = $key;
                    return;
                }
            }

            $authenticatorClassName = get_class($this->authenticator);
            if (isset($this->_session->keys[$authenticatorClassName])) {
                $this->formData['twoFactorKey'] = $this->_session->keys[$authenticatorClassName];
                return;
            }

            // No key exists. Generate key
            $authKey = $this->authenticator->createSecret();
            $this->formData['twoFactorKey'] = $authKey;
            $this->_session->keys[$authenticatorClassName] = $authKey;
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        $newKey = $this->formData['twoFactorKey'];

        if ($newKey) {
            if ($this->user->canSaveTwoFactorKey()) {
                $enabled = $this->formData['twoFactorEnabled'] ? 1 : 0;
            } else {
                $enabled = null;
            }

            $this->user->setTwoFactorKey($this->authenticator, $newKey, $enabled);

            $this->addMessage($this->_('Two factor authentication setting saved.'));
        }

        return 0;
    }
}
