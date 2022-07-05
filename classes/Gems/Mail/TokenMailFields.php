<?php

namespace Gems\Mail;

use Gems\Tracker\Token\TokenSelect;
use MUtil\Translate\Translator;

class TokenMailFields extends RespondentMailFields
{
    private \Gems_Tracker_Token $token;

    private Translator $translator;

    private TokenSelect $tokenSelect;

    public function __construct(\Gems_Tracker_Token $token, array $config, \MUtil\Translate\Translator $translator, TokenSelect $tokenSelect)
    {
        $this->token = $token;
        parent::__construct($token->getRespondent(), $config);
        $this->translator = $translator;
        $this->tokenSelect = $tokenSelect;
    }

    public function getMaiLFields(string $language = null): array
    {
        $mailFields = parent::getMailFields();

        $organization = $this->token->getOrganization();
        $survey = $this->token->getSurvey();

        $tokenLink = $organization->getLoginUrl() . '/ask/forward/' . $this->token->getTokenId();
        $askUrl = $organization->getLoginUrl() . '/ask/';

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
            'token_from' => \MUtil_Date::format($this->token->getValidFrom(), \Zend_Date::DATE_LONG, 'yyyy-MM-dd'),
            'token_link' => '[url=' . $tokenLink . ']' . $survey->getExternalName() . '[/url]',
            'token_until' => \MUtil_Date::format($this->token->getValidUntil(), \Zend_Date::DATE_LONG, 'yyyy-MM-dd'),
            'token_url' => $tokenLink,
            'token_url_input' => $askUrl . 'index/' . $this->token->getTokenId(),
            'track' => $this->token->getTrackName(),
        ];

        // Add the code fields
        $codes  = $this->token->getRespondentTrack()->getCodeFields();
        foreach ($codes as $code => $data) {
            $key = 'track.' . $code;
            if (is_array($data)) {
                $data = implode(' ', $data);
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
        $tSelect = $this->tokenSelect;
        $db = $tSelect->getSelect()->getAdapter();
        $tSelect->columns([
            'all'   => 'COUNT(*)',
            'track' => $db->quoteInto(
                'SUM(CASE WHEN gto_id_respondent_track = ? THEN 1 ELSE 0 END)',
                $this->token->getRespondentTrackId())
        ]);
        $tSelect->andSurveys(array())
            ->forRespondent($this->token->getRespondentId(), $this->token->getOrganizationId())
            ->forGroupId($this->token->getSurvey()->getGroupId())
            ->onlyValid();
        return $tSelect->fetchRow();
    }
}