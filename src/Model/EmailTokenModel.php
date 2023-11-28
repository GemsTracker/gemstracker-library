<?php

namespace Gems\Model;

use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Transform\AddValuesTransformer;
use Gems\Model\Transform\EmailToTransformer;
use Gems\Repository\CommJobRepository;
use Gems\Repository\CommRepository;
use Gems\Tracker;
use Gems\User\User;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class EmailTokenModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translator,
        protected CommJobRepository $commJobRepository,
        protected CommRepository $commRepository,
        protected Tracker $tracker,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct('gems__tokens', $metaModelLoader, $sqlRunner, $translator, 'emailTokenModel');

        $this->addTable('gems__tracks', ['gto_id_track' => 'gtr_id_track']);
        $this->addTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey']);
        $this->addTable('gems__respondents', ['gto_id_respondent' => 'grs_id_user']);
        $this->addTable('gems__respondent2org', ['gto_id_respondent' => 'gr2o_id_user', 'gto_id_organization' => 'gr2o_id_organization']);

        $this->metaModel->set('gto_id_token', [
            'label' => $this->translate->_('Token'),
            'apiName' => 'id',
            'elementClass' => 'html',
        ]);

        $this->metaModel->set('to', [
            'label' => $this->translate->_('To'),
            'elementClass' => 'html',
        ]);

        $this->metaModel->set('gtr_track_name', [
            'label' => $this->translate->_('Track'),
            'apiName' => 'trackName',
            'elementClass' => 'html',
        ]);
        $this->metaModel->set('gto_round_description', [
            'label' => $this->translate->_('Round'),
            'apiName' => 'roundName',
            'elementClass' => 'html',
        ]);
        $this->metaModel->set('gsu_survey_name', [
            'label' => $this->translate->_('Survey'),
            'apiName' => 'surveyName',
            'elementClass' => 'html',
        ]);
        $this->metaModel->set('gto_mail_sent_date', [
            'label' => $this->translate->_('Last contact'),
            'apiName' => 'lastContact',
            'elementClass' => 'html',
        ]);
        $this->metaModel->set('grs_iso_country', [
            'label' => $this->translate->_('Preferred language'),
            'apiName' => 'preferredLanguage',
            'elementClass' => 'html',
        ]);
        $this->metaModel->set('communicationTemplate', [
            'label' => $this->translate->_('Template'),
            'apiName' => 'template',
            //'multiOptions' => $this->commJobRepository->getCommTemplates('token'),
            'multiOptionSettings' => [
                'reference' => 'single-language-comm-template',
                'key' => 'id',
                'value' => 'name',
                'onChange' => [
                    'subject' => [
                        'valueFromField' => [
                            'subject',
                        ],
                    ],
                    'body' => [
                        'valueFromField' => [
                            'body',
                        ],
                    ],
                ],
            ],
        ]);

        $this->metaModel->addTransformer(new EmailToTransformer());

        $currentUser = $currentUserRepository->getCurrentUser();
        if ($currentUser !== null && $currentUser->hasPrivilege('pr.token.mail.freetext')) {
            $this->metaModel->set('subject', [
                'label' => $this->translate->_('Subject'),
                'apiName' => 'subject',
                'size' => 100,
                'value' => null,
                'default' => null,
            ]);
            $this->metaModel->set('body', [
                'label' => $this->translate->_('Message'),
                'apiName' => 'body',
                'elementClass' => 'EmailNowMessage',
            ]);
            $this->metaModel->addTransformer(new AddValuesTransformer([
                'subject' => null,
                'body' => null,
            ]));
        }
    }

    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        if (!isset($newValues['gto_id_token'])) {
            throw new \Exception('Missing token ID');
        }
        $token = $this->tracker->getToken($newValues['gto_id_token']);
        $templateId = $newValues['communicationTemplate'] ?? null;
        $from = $newValues['from'] ?? $token->getOrganization()->getEmail();
        $fromName = $newValues['fromName'] ?? $token->getOrganization()->getContactName();
        $to = $token->getEmail();
        $subject = $newValues['subject'] ?? null;
        $body = $newValues['body'] ?? null;

        if ($this->commRepository->sendEmail($token, $templateId, $from, $fromName, $to, $subject, $body)) {
            return $newValues;
        }

        throw new \Exception('Sending token email failed');
    }
}