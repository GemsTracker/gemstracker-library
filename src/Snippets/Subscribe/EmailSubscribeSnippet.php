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

use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\MailRepository;
use Gems\Snippets\FormSnippetAbstract;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
     * @var \Gems\User\Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

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
        MenuSnippetHelper $menuHelper,
        protected readonly Locale $locale,
        protected readonly Adapter $db,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly MailRepository $mailRepository,
        protected readonly RespondentModel $respondentModel,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);

        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->currentOrganization = $currentUserRepository->getCurrentOrganization();
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
                ->addValidator('SimpleEmail');

        $form->addElement($element);

        return $form;
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $this->addMessage($this->_('You have been subscribed succesfully.'));

        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns([
                'gr2o_id_user',
                'gr2o_patient_nr',
            ])
            ->from('gems__respondent2org')
            ->where([
                'gr2o_email' => $this->formData['email'],
                'gr2o_id_organization' => $this->currentOrganization->getId(),
            ]);

        $resultSet = $sql->prepareStatementForSqlObject($select)->execute();
        $resultSet->buffer();
        $userIds = $resultSet->current(); // Take only the first one

        $mailCodes = $this->mailRepository->getRespondentMailCodes();
        // Use the second mailCode, the first is the no-mail code.
        next($mailCodes);
        $mailable = key($mailCodes);
        
        $values['grs_iso_lang']         = $this->locale->getLanguage();
        $values['gr2o_id_organization'] = $this->currentOrganization->getId();
        $values['gr2o_email']           = $this->formData['email'];
        $values['gr2o_mailable']        = $mailable;
        $values['gr2o_comments']        = $this->_('Created by subscription');
        $values['gr2o_opened_by']       = $this->currentUserRepository->getCurrentUserId();

        // \MUtil\EchoOut\EchoOut::track($userIds, $this->formData['email']);
        if ($userIds) {
            $values['grs_id_user']     = $userIds['gr2o_id_user'];
            $values['gr2o_id_user']    = $userIds['gr2o_id_user'];
            $values['gr2o_patient_nr'] = $userIds['gr2o_patient_nr'];
        } else {
            $func = $this->patientNrGenerator;
            $values['gr2o_patient_nr'] = $func();
        }
        // \MUtil\EchoOut\EchoOut::track($values);

        $this->respondentModel->save($values);

        return $this->respondentModel->getChanged();
    }
}
