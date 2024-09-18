<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Subscribe;

use Gems\Audit\AuditLog;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\MailRepository;
use Gems\Snippets\FormSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;
use Zalt\Validator\SimpleEmail;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:35:38
 */
class EmailSubscribeSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var int
     */
    protected int $currentOrganizationId;

    /**
     *
     * @var int
     */
    protected int $currentUserId;

    /**
     *
     * @var callable
     */
    protected $patientNrGenerator;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected readonly Locale $locale,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly MailRepository $mailRepository,
        protected readonly RespondentModel $respondentModel,
        protected readonly ResultFetcher $resultFetcher,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->currentOrganizationId = $currentUserRepository->getCurrentOrganizationId();
    }

    /**
     * Add the elements to the form
     *
     * @param mixed $form
     */
    protected function addFormElements(mixed $form)
    {
//        \MUtil\EchoOut\EchoOut::track('EmailSubscribeSnippet');
        // Veld inlognaam
        $element = $form->createElement('text', 'email');
        $element->setLabel($this->_('Your E-Mail address'))
                ->setAttrib('size', 30)
                ->setRequired(true)
                ->addValidator(SimpleEmail::class);

        $form->addElement($element);

        $form->addElement($form->createElement('hidden', 'org'));

        return $form;
    }

    protected function afterRegistration(array $respondentData)
    { }

    protected function getDefaultFormValues(): array
    {
        return [
            'org' => $this->currentOrganizationId,
            ];
    }

    protected function getSaveData(): array
    {
        $select = $this->resultFetcher->getSelect()
            ->columns([
                'gr2o_id_user',
                'gr2o_patient_nr',
            ])
            ->from('gems__respondent2org')
            ->where([
                'gr2o_email' => $this->formData['email'],
                'gr2o_id_organization' => $this->formData['org'],
            ]);
        $userId = $this->resultFetcher->fetchRow($select);

        $values['grs_iso_lang']         = $this->locale->getLanguage();
        $values['gr2o_id_organization'] = $this->formData['org'];
        $values['gr2o_email']           = $this->formData['email'];
        $values['gr2o_mailable']        = $this->mailRepository->getDefaultRespondentMailCode();
        $values['gr2o_comments']        = $this->_('Created by subscription');
        $values['gr2o_opened_by']       = $this->currentUserId;

        // dump($userId, $this->formData['email']);
        if ($userId) {
            $values['grs_id_user']     = $userId['gr2o_id_user'];
            $values['gr2o_id_user']    = $userId['gr2o_id_user'];
            $values['gr2o_patient_nr'] = $userId['gr2o_patient_nr'];
        } else {
            $func = $this->patientNrGenerator;
            $values['gr2o_patient_nr'] = $func($values['gr2o_id_organization']);
        }

        return $values;
    }

    protected function getSubscribeMessage(): string
    {
        return $this->_('You have been subscribed successfully.');
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $this->addMessage($this->getSubscribeMessage());

        $values = $this->getSaveData();
        $result = $this->respondentModel->save($values);

        $this->afterRegistration($result);

        return $this->respondentModel->getChanged();
    }
}
