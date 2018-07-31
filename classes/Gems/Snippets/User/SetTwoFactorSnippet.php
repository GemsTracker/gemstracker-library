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
     *
     * @var \Gems_User_User
     */
    protected $user;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        $this->saveLabel = $this->_('Save Two Factor Setup');

        $this->authenticator->addSetupFormElements($form, $this->user, $this->formData);

        if ($this->user->canSaveTwoFactorKey()) {
            $options = [
                'label' => $this->_('Enabled'),
                ];

            $keyElement = $form->createElement('Checkbox', 'twoFactorEnabled', $options);
            $form->addElement($keyElement);
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

        $this->authenticator = $this->user->getTwoFactorAuthenticator();
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

        if (! $authKey) {
            $authKey = $this->authenticator->createSecret();

            $this->addMessage(sprintf(
                    $this->_('A new random two factor key was saved for %s.'),
                    $this->user->getFullName()
                    ));

            $this->addMessage($this->_('Click save to enable two factor authentication.'));
            $this->user->setTwoFactorKey($this->authenticator, $authKey, false);

            // Set on save
            $output['twoFactorEnabled'] = 1;
        } else {
            $output['twoFactorEnabled'] = $this->user->isTwoFactorEnabled() ? 1 : 0;

            if (! $output['twoFactorEnabled']) {
                $this->addMessage($this->_('Two factor authentication not active!'));
            }
        }
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
        if (parent::hasHtmlOutput()) {
            return $this->processForm();
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
