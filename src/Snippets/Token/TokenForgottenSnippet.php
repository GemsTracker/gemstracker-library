<?php

/**
 *
 * @package    Gem
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Snippets\Token;

use Gems\Audit\AuditLog;
use Gems\Communication\CommunicationRepository;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\CommJobRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\FormSnippetAbstract;
use Symfony\Component\Mime\Address;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gem
 * @subpackage Snippets\Token
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class TokenForgottenSnippet extends FormSnippetAbstract
{
    protected ?string $clientIp = null;

    protected int $layoutFixedWidth = 20;

    /**
     * The field name for the organization element.
     *
     * @var string
     */
    public $organizationFieldName = 'organization';

    /**
     *
     * @var callable
     */
    protected $patientNrGenerator;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'index';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected CurrentUserRepository $currentUserRepository,
        protected OrganizationRepository $organizationRepository,
        protected ResultFetcher $resultFetcher,
        protected RespondentRepository $respondentRepository,
        protected CommunicationRepository $communicationRepository,
        protected CommJobRepository $commJobRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        $form->addElement($this->getOrganizationElement($form));
        $form->addElement($this->getEmailElement($form));

        $this->saveLabel = $this->getSubmitButtonLabel();

        return $form;
    }

    /**
     * Returns the organization id that should currently be used for this form.
     *
     * @return int Returns the current organization id, if any
     */
    public function getCurrentOrganizationId()
    {
        if ($this->requestInfo->isPost()) {
            $orgId = $this->requestInfo->getParam($this->organizationFieldName);

            if ($orgId) {
                return $orgId;
            }
        }
        return $this->currentUserRepository->getCurrentOrganizationId();
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues(): array
    {
        return [$this->organizationFieldName => $this->currentUserRepository->getCurrentOrganizationId()];
    }

    /**
     * @return string
     */
    public function getEmailDescription()
    {
        return $this->_('The email address you use with this organization.');
    }

    /**
     * @param \Zend_Form $form
     * @return \Zend_Form_Element
     * @throws \Zend_Form_Exception
     */
    public function getEmailElement(\Zend_Form $form)
    {
        // Veld inlognaam
        $element = $form->createElement('text', 'email');
        $element->setLabel($this->_('Enter your E-Mail address'))
                ->setDescription($this->getEmailDescription())
                ->setAttrib('size', 30)
                ->setRequired(true)
                ->addValidator('SimpleEmail');

        return $element;
    }

    public function getInvalidFormMessage(): mixed
    {
        return $this->_('Input error! No mail could be sent!');
    }

    /**
     * Returns/sets an element for determining / selecting the organization.
     *
     * @return \Zend_Form_Element_Xhtml
     */
    public function getOrganizationElement(\Zend_Form $form)
    {
        $element = $form->getElement($this->organizationFieldName);
        $orgId   = $this->getCurrentOrganizationId();
        $orgs    = $this->getRespondentOrganizations();
        $hidden  = count($orgs) < 2;

        if ($hidden) {
            if (! $element instanceof \Zend_Form_Element_Hidden) {
                $element = new \Zend_Form_Element_Hidden($this->organizationFieldName);
            }

        } elseif (! $element instanceof \Zend_Form_Element_Select) {
            $element = $form->createElement('select', $this->organizationFieldName);
            $element->setLabel($this->translate->_('Organization'));
            $element->setRegisterInArrayValidator(true);
            $element->setRequired(true);
            $element->setMultiOptions($orgs);
        }

        return $element;
    }

    /**
     *  Returns a list with the organizations the user can select for login.
     *
     * @return array orgId => Name
     */
    public function getRespondentOrganizations()
    {
        return $this->organizationRepository->getOrganizationsWithRespondents();
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        return $this->_('E-Mail the token');
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->_('Token lost?');
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $orgId = $this->formData[$this->organizationFieldName];

        if ($orgId) {
            $sql      = "SELECT gr2o_id_user, gr2o_patient_nr FROM gems__respondent2org
                            WHERE (gr2o_email = ? OR gr2o_id_user in (SELECT grr_id_respondent FROM gems__respondent_relations WHERE grr_email = ?)) 
                                 AND gr2o_id_organization = ?";
            $userData = $this->resultFetcher->fetchRow($sql, [$this->formData['email'], $this->formData['email'], $orgId]);

            if ($userData) {
                $sent       = 0;
                $respondent = $this->respondentRepository->getRespondent($userData['gr2o_patient_nr'], $orgId, $userData['gr2o_id_user']);

                if ($respondent->exists && $respondent->canBeMailed()) {

                    $sentTokens = $this->commJobRepository->sendAllCommunications($respondent->getId(), $respondent->getOrganizationId(), true);
                    $sent = count($sentTokens);
                    // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): xx ' .  print_r($sentTokens, true) . "\n", FILE_APPEND);
                }
                if (0 === $sent) {
                    // Try to sent a "nothingToSend" mail
                    $templateId = $this->resultFetcher->fetchOne(
                        "SELECT gct_id_template
                                FROM gems__comm_templates
                                WHERE gct_target = 'respondent' AND gct_code = 'nothingToSend'"
                    );
                    if ($templateId) {

                        $email = $this->communicationRepository->getNewEmail();
                        $email->addTo(new Address($respondent->getEmailAddress(), $respondent->getName()));
                        $email->addFrom(new Address($respondent->getOrganization()->getEmail()));

                        $template = $this->communicationRepository->getTemplate($respondent->getOrganization());
                        $language = $this->communicationRepository->getCommunicationLanguage($respondent->getLanguage());
                        $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
                        $mailFields = $this->communicationRepository->getRespondentMailFields($respondent, $language);
                        $mailer = $this->communicationRepository->getMailer($respondent->getOrganization()->getEmail());
                        $email->subject($mailTexts['subject'], $mailFields);
                        $email->htmlTemplate($template, $mailTexts['body'], $mailFields);
                        $mailer->send($email);
                    }
                }
            }
        }

        $this->showCompletionMessages();

        return 0;
    } // */

    /**
     * Show the messages after save (always the same)
     */
    protected function showCompletionMessages()
    {
        $this->addMessage(
            $this->_('You will receive an email with your token in a few minutes, if one is open for answering.')
        );
        $this->addMessage(
            $this->_('If you do not receive an email (not even in the spam folder), please contact your organization.')
        );
    } // */
}
