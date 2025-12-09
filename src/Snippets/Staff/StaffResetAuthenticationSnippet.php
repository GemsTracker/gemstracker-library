<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Staff;

use Gems\AuthTfa\OtpMethodBuilder;
use Gems\Communication\CommunicationRepository;
use Gems\Config\ConfigAccessor;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\ZendFormSnippetAbstract;
use Gems\User\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 11:47:11
 */
class StaffResetAuthenticationSnippet extends ZendFormSnippetAbstract
{
    protected bool|User $user;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly ConfigAccessor $configAccessor,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly OtpMethodBuilder $otpMethodBuilder,
        protected readonly StatusMessengerInterface $statusMessenger,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        if (false === $this->user) {
            $this->messenger->addMessage($this->_('This account cannot login, so it cannot be reset..'));
            return;
        }
        if (!$this->user->hasEmailAddress()) {
            $this->messenger->addMessage($this->_('This account has no e-mail address configured. An e-mail address is required to reset authentication.'));
            return;
        }

        $order = count($form->getElements())-1;
        $labelElement = new \MUtil\Form\Element\Html('user_name');
        $labelElement->setValue($this->_('User') . ' ' . $this->user->getFullName(true));
        $form->addElement($labelElement);

        $createElement = new \MUtil\Form\Element\FakeSubmit('create_account');
        $createElement->setLabel($this->_('Create account mail'))
                    ->setAttrib('class', 'button btn btn-primary')
                    ->setOrder($order++);
        $form->addElement($createElement);

        $resetElement = new \MUtil\Form\Element\FakeSubmit('reset_password');
        $resetElement->setLabel($this->_('Reset password mail'))
                    ->setAttrib('class', 'button btn btn-primary')
                    ->setOrder($order++);
        $form->addElement($resetElement);

        $currentTfa = $this->user->hasTwoFactorConfigured() ? $this->user->getTfaMethodClass() : $this->_('None');
        $methodElement = $form->createElement('exhibitor', 'twoFactorMethod', [
            'label' => $this->_('Current Two Factor method'),
            'value' => $this->_($this->user->getTfaMethodDescription()),
        ]);
        $form->addElement($methodElement);

//        if ($this->configAccessor->hasTFAMethod('AuthenticatorTotp')) {
//            $active = $currentTfa === 'AuthenticatorTotp';
//            $authenticatorElement = new \MUtil\Form\Element\FakeSubmit('authenticator_tfa');
//            $authenticatorElement->setLabel($active ? $this->_('Resend TFA setup mail') : $this->_('Change TFA method to Authenticator and send setup mail'))
//                ->setAttrib('class', 'button btn btn-primary')
//                ->setOrder($order++);
//            $form->addElement($authenticatorElement);
//        }

        if ($this->configAccessor->hasTFAMethod('MailHotp')) {
            $active = $currentTfa === 'MailHotp';
            $mailElement = new \MUtil\Form\Element\FakeSubmit('mail_tfa');
            $mailElement->setLabel($active ? $this->_('TFA is already reset to mail, yet resend!') : $this->_('Reset TFA method using Mail for code'))
                ->setAttrib('class', 'button btn btn-primary')
                ->setOrder($order++);
            $form->addElement($mailElement);
        }

        if ($this->configAccessor->hasTFAMethod('SmsHotp')) {
            $active = $currentTfa === 'SmsHotp';
            $resetElement = new \MUtil\Form\Element\FakeSubmit('reset_tfa');
            $resetElement->setLabel($active ? $this->_('TFA is already reset to SMS, yet resend!') : $this->_('Reset TFA method using SMS for code'))
                ->setAttrib('class', 'button btn btn-primary')
                ->setOrder($order++);
            if ($active) {
                $resetElement->setAttrib('disabled', 'disabled');

                if (empty($this->user->getPhonenumber())) {
                    $this->statusMessenger->addWarning($this->_('TFA Method is set to SMS but no mobile number is configured'));
                }
            }
        }
        $form->addElement($resetElement);
    }

    protected function addSaveButton(string $saveButtonId, ?string $saveLabel, string $buttonClass)
    {
        parent::addSaveButton($saveButtonId, $saveLabel, $buttonClass);
        $this->_saveButton->setAttrib('class', 'd-none');
    }

    /**
     * Hook that allows actions when the form is submitted, but it was not the submit button that was checked
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onFakeSubmit()
    {
        $organization = $this->user->getBaseOrganization();
        $language     = $this->communicationRepository->getCommunicationLanguage($this->user->getLocale());
        $useMethod    = null;

        $templateId = $successMessage = null;
        $mailFields = [];
        if (isset($this->formData['create_account']) && $this->formData['create_account']) {
            $templateId = $this->communicationRepository->getCreateAccountTemplate($organization);
            if (!$templateId) {
                $this->addMessage($this->_('No default Create Account mail template set in organization or project'));
            } else {
                $mailFields = $this->communicationRepository->getUserPasswordMailFields($this->user, $language);
                $successMessage = $this->_('Create account mail sent');
            }
        } elseif (isset($this->formData['reset_password']) && $this->formData['reset_password']) {
            $templateId = $this->communicationRepository->getResetPasswordTemplate($organization);
            if (!$templateId) {
                $this->addMessage($this->_('No default Reset Password mail template set in organization or project'));
            } else {
                $mailFields = $this->communicationRepository->getUserPasswordMailFields($this->user, $language);
                $successMessage = $this->_('Reset password mail sent');
            }
        } elseif (isset($this->formData['authenticator_tfa']) && $this->formData['authenticator_tfa']) {
            $useMethod = 'AuthenticatorTotp';

        } elseif (isset($this->formData['mail_tfa']) && $this->formData['mail_tfa']) {
            $useMethod = 'MailHotp';

        } elseif (isset($this->formData['reset_tfa']) && $this->formData['reset_tfa']) {
            if ($this->user->hasTwoFactorConfigured() && $this->user->getTfaMethodClass() === 'SmsHotp') {
                $this->addMessage($this->_('This user already has SMS TFA enabled'));
            } elseif (empty($this->user->getPhonenumber())) {
                $this->statusMessenger->addError($this->_('Please first add a mobile telephone number before activating SMS TFA'));
            } else {
                $useMethod = 'SmsHotp';
            }
        }
        if ($useMethod !== null) {
            $templateId = $this->communicationRepository->getResetTfaTemplate($organization);
            if (!$templateId) {
                $this->addMessage($this->_('No default Reset TFA mail template set in organization or project'));
            } else {
                $this->user->clearTwoFactorKey();
                $this->otpMethodBuilder->setOtpMethod($this->user, $useMethod);

                $mailFields = $this->communicationRepository->getUserPasswordMailFields($this->user, $language);
                $successMessage = sprintf(
                    $this->_('The two factor key for user %s has been reset and a notification mail has been sent'),
                    $this->user->getLoginName()
                );
            }
        }

        if ($templateId) {
            $email = $this->communicationRepository->getNewEmail();
            $email->addTo(new Address($this->user->getEmailAddress(), $this->user->getFullName()));
            $email->addFrom(new Address($organization->getEmail()));

            $template = $this->communicationRepository->getTemplate($organization);
            $mailer = $this->communicationRepository->getMailer();

            $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
            if ($mailTexts) {
                $email->subject($mailTexts['subject'], $mailFields);
                $email->htmlTemplate($template, $mailTexts['body'], $mailFields);

               try {
                  $mailer->send($email);

                  $this->addMessage($successMessage);
               } catch(TransportExceptionInterface $e) {
                  $this->statusMessenger->addError($this->_('Mail could not be sent, the error message was: ' . $e->getMessage()));
               }
            } else {
                $this->addMessage(sprintf($this->_('Default Reset TFA mail template id %d does not contain translations'), $templateId));
            }
        }

        $this->redirectRoute = $this->menuSnippetHelper->getRouteUrl('setup.access.staff.reset', [
            MetaModelInterface::REQUEST_ID => intval($this->user->getUserId()),
        ]);
    }
}
