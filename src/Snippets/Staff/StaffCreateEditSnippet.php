<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Snippets\Staff;

use Gems\Audit\AuditLog;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\Config\ConfigAccessor;
use Gems\Loader;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\ModelFormSnippet;
use Gems\User\User;
use Gems\User\UserLoader;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Description of StaffCreateEditSnippet
 *
 * @author 175780
 */
class StaffCreateEditSnippet extends ModelFormSnippet
{
    /**
     * When true this is the staff form
     *
     * @var boolean
     */
    protected $isStaff = true;

    /**
     * When true we're switching from staff user to system user
     *
     * @var boolean
     */
    protected $switch = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        AuditLog $auditLog,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected readonly ConfigAccessor $configAccessor,
        protected readonly UserLoader $userLoader,
        protected readonly OtpMethodBuilder $otpMethodBuilder,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->switch) {
            if ($this->isStaff) {
                return $this->_('Save as staff');
            } else {
                return $this->_('Save as system user');
            }
        }
        return parent::getTitle();
    }

    /**
     * Hook that allows actions when the input is invalid
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onInValid()
    {
        $form    = $this->_form;
        if ($element = $form->getElement('gsf_login')) {
            $errors = $element->getErrors();
            if (array_search('recordFound', $errors) !== false) {
                //We have a duplicate login!
                $model  = $this->getModel();
                $model->setFilter(array(
                    'gsf_login'           => $form->getValue('gsf_login'),
                    'gsf_id_organization' => $form->getValue('gsf_id_organization')
                ));
                $result = $model->load();

                if (count($result) == 1) {
                    $result = array_shift($result); //Get the first (only) row
                    if (($result['gsf_active'] == 0) || ($result['gul_can_login'] == 0)) {
                        //Ok we try to add an inactive user...
                        //now ask if this is the one we would like to reactivate?

                        $this->addMessage(sprintf($this->_('User with id %s already exists but is deleted, do you want to reactivate the account?'), $result['gsf_login']));
                        $this->afterSaveRoutePart = 'reactivate';

                        return;
                    } else {
                        //User is active... this is a real duplicate so continue the flow
                    }
                }
            }
        }

        parent::onInValid();
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData(): int
    {
        if ($this->switch && $this->isStaff) {
            $this->formData['gsf_logout_on_survey'] = 0;
            $this->formData['gsf_is_embedded'] = 0;
        }

        $output = parent::saveData();

        if ($this->isStaff) {
            $user = $this->userLoader->getUserByStaffId($this->formData['gsf_id_user']);
            if ($user instanceof User) {
                if ($this->formData['has_authenticator_tfa']) {
                    if (! ($this->formData['gul_two_factor_key'] ?? false)) {
                        if ($this->formData['gsf_phone_1'] && $this->configAccessor->hasTFAMethod('SmsHotp')) {
                            $this->otpMethodBuilder->setOtpMethod($user, 'SmsHotp');
                            return 1;
                        }
                        if ($this->formData['gsf_email']) {
                            $this->otpMethodBuilder->setOtpMethod($user, 'MailHotp');
                            return 1;
                        }
                        $this->addMessage($this->_('Could not set Two Factor Authentication as no e-mail was specified.'));
                    }
                } else {
                    if ($this->formData['gul_two_factor_key'] ?? false) {
                        if ($this->configAccessor->canTfaBeDisabled()) {
                            $user->clearTwoFactorKey();
                            $this->addMessage($this->_('Removed Two Factor Authentication!'));
                            return 1;
                        } else {
                            $this->addMessage($this->_('Two factor authentication cannot be removed.'));
                        }
                    }
                }
            }
        } else {
            if (isset($this->formData['gul_two_factor_key'], $this->formData['gsf_id_user']) &&
                    $this->formData['gul_two_factor_key']) {

                $user = $this->userLoader->getUserByStaffId($this->formData['gsf_id_user']);

                if ($user->canSetPassword()) {
                    $this->addMessage(sprintf($this->_('Password saved for: %s'), $user->getLoginName()));
                    $user->setPassword($this->formData['gul_two_factor_key']);
                }
            }
        }
        return $output;
    }
}
