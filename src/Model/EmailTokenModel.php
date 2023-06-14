<?php

namespace Gems\Model;

use Gems\Model\Transform\EmailToTransformer;
use Gems\Repository\CommJobRepository;
use Gems\Repository\CommRepository;
use Gems\Tracker;
use MUtil\Translate\Translator;

class EmailTokenModel extends JoinModel
{
    public function __construct(
        protected Translator $translator,
        protected CommJobRepository $commJobRepository,
        protected CommRepository $commRepository,
        protected Tracker $tracker,
    )
    {
        parent::__construct('emailTokenModel', 'gems__tokens', 'gto', false);
        $this->addTable('gems__tracks', ['gto_id_track' => 'gtr_id_track'], 'gtr', false);
        $this->addTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey'], 'gsu', false);
        $this->addTable('gems__respondents', ['gto_id_respondent' => 'grs_id_user'], 'grs', false);
        $this->addTable('gems__respondent2org', ['gto_id_respondent' => 'gr2o_id_user', 'gto_id_organization' => 'gr2o_id_organization'], 'gr2o', false);

        $this->set('gto_id_token', [
            'label' => $this->translator->_('Token'),
            'apiName' => 'id',
            'elementClass' => 'html',
        ]);

        $this->set('to', [
            'label' => $this->translator->_('To'),
            'elementClass' => 'html',
        ]);
        $this->set('gtr_track_name', [
            'label' => $this->translator->_('Track'),
            'apiName' => 'trackName',
            'elementClass' => 'html',
        ]);
        $this->set('gto_round_description', [
            'label' => $this->translator->_('Round'),
            'apiName' => 'roundName',
            'elementClass' => 'html',
        ]);
        $this->set('gsu_survey_name', [
            'label' => $this->translator->_('Survey'),
            'apiName' => 'surveyName',
            'elementClass' => 'html',
        ]);
        $this->set('gto_mail_sent_date', [
            'label' => $this->translator->_('Last contact'),
            'apiName' => 'lastContact',
            'elementClass' => 'html',
        ]);
        $this->set('grs_iso_country', [
            'label' => $this->translator->_('Preferred language'),
            'apiName' => 'preferredLanguage',
            'elementClass' => 'html',
        ]);
        $this->set('communicationTemplate', [
            'label' => $this->translator->_('Template'),
            'apiName' => 'template',
            'multiOptions' => $this->commJobRepository->getCommTemplates('token'),
        ]);
        /*$this->set('subject', [
            'label' => $this->translator->_('Subject'),
            'elementClass' => 'html',
        ]);
        $this->set('message', [
            'label' => $this->translator->_('Message'),
            'elementClass' => 'html',
        ]);*/

        $this->addTransformer(new EmailToTransformer());
    }

    protected function _save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        if (!isset($newValues['gto_id_token'])) {
            new \Exception('Missing token ID');
        }
        $token = $this->tracker->getToken($newValues['gto_id_token']);
        $templateId = $newValues['communicationTemplate'] ?? null;
        $from = $newValues['from'] ?? $token->getOrganization()->getEmail();
        $fromName = $newValues['fromName'] ?? $token->getOrganization()->getContactName();
        $to = $token->getEmail();

        if ($this->commRepository->sendEmail($token, $templateId, $from, $fromName, $to)) {
            return $newValues;
        }

        throw new \Exception('Sending token email failed');
    }
}