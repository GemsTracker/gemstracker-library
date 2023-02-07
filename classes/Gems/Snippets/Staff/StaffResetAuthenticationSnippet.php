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
use Gems\MenuNew\RouteHelper;
use Gems\Snippets\ZendFormSnippetAbstract;
use Gems\User\User;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
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
    protected User $user;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        private readonly OtpMethodBuilder $otpMethodBuilder,
        private readonly RouteHelper $routeHelper,
        private readonly CommunicationRepository $communicationRepository,
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
        if ($this->user->hasEmailAddress()) {
            $order = count($form->getElements())-1;
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

            $methodElement = $form->createElement('exhibitor', 'twoFactorMethod', [
                'label' => $this->_('Current Two Factor method'),
                'value' => $this->user->hasTfaConfigured() ? $this->user->getTfaMethodClass() : $this->_('None'),
            ]);
            $form->addElement($methodElement);

            $resetElement = new \MUtil\Form\Element\FakeSubmit('reset_tfa');
            $resetElement->setLabel($this->_('Change TFA method to SMS and send mail'))
                ->setAttrib('class', 'button btn btn-primary')
                ->setOrder($order++);
            if ($this->user->hasTfaConfigured() && $this->user->getTfaMethodClass() === 'SmsHotp') {
                $resetElement->setAttrib('disabled', 'disabled');
            }
            $form->addElement($resetElement);
        }
    }

    protected function addSaveButton(string $saveButtonId, string $saveLabel, string $buttonClass)
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
        $language = $this->communicationRepository->getCommunicationLanguage($this->user->getLocale());

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
        } elseif (isset($this->formData['reset_tfa']) && $this->formData['reset_tfa']) {
            if ($this->user->hasTfaConfigured() && $this->user->getTfaMethodClass() === 'SmsHotp') {
                $this->addMessage($this->_('This user already has SMS TFA enabled'));
            } else {
                $templateId = $this->communicationRepository->getResetTfaTemplate($organization);
                if (!$templateId) {
                    $this->addMessage($this->_('No default Reset TFA mail template set in organization or project'));
                } else {
                    $this->user->clearTwoFactorKey();

                    $this->otpMethodBuilder->setOtpMethod($this->user, 'SmsHotp');

                    $mailFields = $this->communicationRepository->getUserMailFields($this->user, $language);
                    $successMessage = sprintf(
                        $this->_('The two factor key for user %s has been reset and a notification mail has been sent'),
                        $this->user->getLoginName()
                    );
                }
            }
        }

        if ($templateId) {
            $email = $this->communicationRepository->getNewEmail();
            $email->addTo(new Address($this->user->getEmailAddress(), $this->user->getFullName()));
            $email->addFrom(new Address($organization->getEmail()));

            $template = $this->communicationRepository->getTemplate($organization);
            $mailer = $this->communicationRepository->getMailer($organization->getEmail());

            $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
            $email->subject($mailTexts['subject'], $mailFields);
            $email->htmlTemplate($template, $mailTexts['body'], $mailFields);

            $mailer->send($email);

            $this->addMessage($successMessage);
        }

        $this->redirectRoute = $this->routeHelper->getRouteUrl('setup.access.staff.reset', [
            \MUtil\Model::REQUEST_ID => intval($this->user->getUserId()),
        ]);
    }
}
