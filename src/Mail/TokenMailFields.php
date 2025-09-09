<?php

namespace Gems\Mail;

use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Tracker\Token;
use Gems\Tracker\Token\LaminasTokenSelect;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;

class TokenMailFields extends RespondentMailFields
{
    public function __construct(
        private readonly Token $token,
        private readonly TranslatorInterface $translator,
        private readonly ResultFetcher $resultFetcher,
        array $config,
    )
    {
        parent::__construct($token->getRespondent(), $config);
    }

    public function getMaiLFields(string $language = null): array
    {
        $mailFields = parent::getMailFields();

        $organization = $this->token->getOrganization();
        $survey = $this->token->getSurvey();

        $tokenLink      = $organization->getLoginUrl() . '/ask/forward/' . $this->token->getTokenId();
        $tokenLoginLink = $organization->getLoginUrl() . '/respondent/' .
            $this->token->getPatientNumber() . '/' .
            $this->token->getOrganizationId() . '/track/' .
            $this->token->getRespondentTrackId() . '/token/' .
            $this->token->getTokenId();
        $askUrl         = $organization->getLoginUrl() . '/ask';

        $todoCounts = $this->getTodoCounts();

        $mailFields += [
            'round' => $this->token->getRoundDescription(),
            'site_ask_url' => $askUrl,
            'survey' => $survey->getExternalName(),
            'todo_all' => sprintf($this->translator->plural('%d survey', '%d surveys', $todoCounts['all'], $language), $todoCounts['all']),
            'todo_all_count' => $todoCounts['all'],
            'todo_track' => sprintf($this->translator->plural('%d survey', '%d surveys', $todoCounts['track'], $language), $todoCounts['track']),
            'todo_track_count' => $todoCounts['track'],
            'token' => strtoupper($this->token->getTokenId()),
            'token_from' => $this->token->getValidFrom() instanceof \DateTimeInterface ? $this->token->getValidFrom()->format('Y-m-d') : null,
            'token_link' => '<a href="' . $tokenLink . '">' . $survey->getExternalName() . '</a>',
            'token_login_link' => '<a href="' . $tokenLoginLink . '">' . $survey->getExternalName() . '</a>',
            'token_login_url'  => $tokenLoginLink,
            'token_until' => $this->token->getValidUntil() instanceof \DateTimeInterface ? $this->token->getValidUntil()->format('Y-m-d') : null,
            'token_url' => $tokenLink,
            'token_url_input' => $askUrl . 'index/' . $this->token->getTokenId(),
            'track' => $this->token->getTrackName(),
        ];

        // Add the code fields
        $codes  = $this->token->getRespondentTrack()->getCodeFields();
        foreach ($codes as $code => $data) {
            $key = 'track_' . $code;
            if (is_array($data)) {
                $data = implode(' ', $data);
            }
            if ($data instanceof DateTimeInterface) {
                $data = $data->format('d-m-Y');
            }
            $mailFields[$key] = $data;
        }

        $mailFields['relation_about'] = $mailFields['name'];
        $mailFields['relation_about_first_name'] = $mailFields['first_name'];
        $mailFields['relation_about_full_name'] = $mailFields['full_name'];
        $mailFields['relation_about_greeting'] = $mailFields['greeting'];
        $mailFields['relation_about_last_name'] = $mailFields['last_name'];
        $mailFields['relation_about_dear'] = $mailFields['dear'];
        $mailFields['relation_field_name'] = $this->token->getRelationFieldName();
        if ($this->token->hasRelation()) {
            if ($relation = $this->token->getRelation()) {
                // Now update all respondent fields to be of the relation
                $mailFields['dear']       = $relation->getDearGreeting($language);
                $mailFields['name']       = $relation->getName();
                $mailFields['first_name'] = $relation->getFirstName();
                $mailFields['last_name']  = $relation->getLastName();
                $mailFields['full_name']  = $relation->getHello($language);
                $mailFields['greeting']   = $relation->getGreeting($language);
                $mailFields['to']         = $relation->getEmail();
            } else {
                $mailFields['name']       = $this->translator->trans('Undefined relation', [], null, $language);
                $mailFields['dear']       = null;
                $mailFields['first_name'] = null;
                $mailFields['last_name']  = null;
                $mailFields['full_name']  = null;
                $mailFields['greeting']   = null;
                $mailFields['to']         = null;
            }
        }

        return $mailFields;
    }

    protected function getTodoCounts()
    {
        if ($this->token instanceof \Gems\Fake\Token) {
            return [
                'all' => 1,
                'track' => 1,
            ];
        }

        $tSelect = new LaminasTokenSelect($this->resultFetcher);

        $tSelect->columns([
            'all'   => new Expression('COUNT(*)'),
            'track' => new Expression(sprintf('SUM(CASE WHEN gto_id_respondent_track = %d THEN 1 ELSE 0 END)', $this->token->getRespondentTrackId())),
        ]);
        $tSelect->andSurveys(array())
            ->forRespondent($this->token->getRespondentId(), $this->token->getOrganizationId())
            ->forGroupId($this->token->getSurvey()->getGroupId())
            ->onlyValid();

        $todoCounts = $tSelect->fetchRow();
        $todoCounts['track'] = $todoCounts['track'] ?? 0;

        return $todoCounts;
    }

    public static function getRawFields(): array
    {
        $rawFields = parent::getRawFields();
        $rawFields[] = 'token_link';
        return $rawFields;
    }
}