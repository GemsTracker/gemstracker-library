<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestCommTemplatesSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            "gems__comm_templates" => [
                [
                    "gct_name" => "Questions for your treatment at {{organization}}",
                    "gct_target" => "token",
                    "gct_code" => null,
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Reminder: your treatment at {{organization}}",
                    "gct_target" => "token",
                    "gct_code" => null,
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Questions for your treatment at {{organization}} with unsubscribe",
                    "gct_target" => "token",
                    "gct_code" => null,
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Reminder: your treatment at {{organization}} with unsubscribe",
                    "gct_target" => "token",
                    "gct_code" => null,
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Questions for treatment of {{relation_about}} at {{organization}}",
                    "gct_target" => "token",
                    "gct_code" => null,
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Reminder: treatment of {{relation_about}} at {{organization}}",
                    "gct_target" => "token",
                    "gct_code" => null,
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Global Password reset",
                    "gct_target" => "staffPassword",
                    "gct_code" => "passwordReset",
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Global Account created",
                    "gct_target" => "staffPassword",
                    "gct_code" => "accountCreate",
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Linked account created",
                    "gct_target" => "staff",
                    "gct_code" => "linkedAccountCreated",
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Continue later",
                    "gct_target" => "token",
                    "gct_code" => "continue",
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "No open tokens",
                    "gct_target" => "respondent",
                    "gct_code" => "nothingToSend",
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                [
                    "gct_name" => "Global TFA reset",
                    "gct_target" => "staffPassword",
                    "gct_code" => "tfaReset",
                    "gct_changed_by" => 1,
                    "gct_created_by" => 1,
                ],
                "gems__comm_template_translations" => [
                    [
                        "gctt_id_template" => "{{gems__comm_templates.0}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Questions for your treatment at {{organization}}",
                        "gctt_body" => "Dear {{greeting}},\r\n\r\nRecently you visited <strong>{{organization}}</strong> for treatment. For your proper treatment you need to answer some questions.\r\n\r\nClick on <a href=\"{{token_url}}\">this link</a> to start or go to <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> and enter your token \"{{token}}\".\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.0}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Vragen over uw behandeling bij {{organization}}",
                        "gctt_body" => "Beste {{greeting}},\r\n\r\nRecent was u op bezoek bij <strong>{{organization}}</strong> voor een behandeling. Om u goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.\r\n\r\nKlik op <a href=\"{{token_url}}\">deze link</a> om te beginnen of ga naar <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> en voer het kenmerk \"{{token}}\" in.\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.1}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Reminder: your treatment at {{organization}}",
                        "gctt_body" => "Dear {{greeting}},\r\n\r\nWe remind you that for your proper treatment at <strong>{{organization}}</strong> you need to answer some questions.\r\n\r\nClick on <a href=\"{{token_url}}\">this link</a> to start or go to <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> and enter your token \"{{token}}\".\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.1}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Herinnering: uw behandeling bij {{organization}}",
                        "gctt_body" => "Beste {{greeting}},\r\n\r\nWij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor uw behandeling bij <strong>{{organization}}</strong>.\r\n\r\nKlik op <a href=\"{{token_url}}\">deze link</a> om te beginnen of ga naar <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> en voer het kenmerk \"{{token}}\" in.\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.2}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Questions for your treatment at {{organization}}",
                        "gctt_body" => "Dear {{greeting}},\r\n\r\nRecently you visited <strong>{{organization}}</strong> for treatment. For your proper treatment you need to answer some questions.\r\n\r\nClick on <a href=\"{{token_url}}\">this link</a> to start or go to <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> and enter your token \"{{token}}\".\r\n\r\n{{organization_signature}}\r\n\r\nTo unsubscribe from these mails <a href=\"{{organization_unsubscribe_url}}\">click here</a>."
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.2}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Vragen over uw behandeling bij {{organization}}",
                        "gctt_body" => "Beste {{greeting}},\r\n\r\nRecent was u op bezoek bij <strong>{{organization}}</strong> voor een behandeling. Om u goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.\r\n\r\nKlik op <a href=\"{{token_url}}\">deze link</a> om te beginnen of ga naar <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> en voer het kenmerk \"{{token}}\" in.\r\n\r\n{{organization_signature}}\r\n\r\nOm geen verdere email te ontvangen <a href=\"{{organization_unsubscribe_url}}\">klik hier</a>."
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.3}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Reminder: your treatment at {{organization}}",
                        "gctt_body" => "Dear {{greeting}},\r\n\r\nWe remind you that for your proper treatment at <strong>{{organization}}</strong> you need to answer some questions.\r\n\r\nClick on <a href=\"{{token_url}}\">this link</a> to start or go to <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> and enter your token \"{{token}}\".\r\n\r\n{{organization_signature}}\r\n\r\nTo unsubscribe from these mails <a href=\"{{organization_unsubscribe_url}}\">click here</a>."
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.3}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Herinnering: uw behandeling bij {{organization}}",
                        "gctt_body" => "Beste {{greeting}},\r\n\r\nWij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor uw behandeling bij <strong>{{organization}}</strong>.\r\n\r\nKlik op <a href=\"{{token_url}}\">deze link</a> om te beginnen of ga naar <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> en voer het kenmerk \"{{token}}\" in.\r\n\r\n{{organization_signature}}\r\n\r\nOm geen verdere email te ontvangen <a href=\"{{organization_unsubscribe_url}}\">klik hier</a>."
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.4}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Questions for the treatment of {relation_about} at {{organization}}",
                        "gctt_body" => "Dear {{greeting}},\r\n\r\nRecently you visited <strong>{{organization}}</strong> with {relation_about} for a treatment. For proper treatment you need to answer some questions.\r\n\r\nClick on <a href=\"{{token_url}}\">this link</a> to start or go to <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> and enter your token \"{{token}}\".\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.4}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Vragen over de behandeling van {relation_about} bij {{organization}}",
                        "gctt_body" => "Beste {{greeting}},\r\n\r\nRecent was u met {relation_about} op bezoek bij <strong>{{organization}}</strong> voor een behandeling. Om goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.\r\n\r\nKlik op <a href=\"{{token_url}}\">deze link</a> om te beginnen of ga naar <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> en voer het kenmerk \"{{token}}\" in.\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.5}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Reminder: treatment of {relation_about} at {{organization}}",
                        "gctt_body" => "Dear {{greeting}},\r\n\r\nWe remind you that for the proper treatment of {relation_about} at <strong>{{organization}}</strong> we need answers to some questions.\r\n\r\nClick on <a href=\"{{token_url}}\">this link</a> to start or go to <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> and enter your token \"{{token}}\".\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.5}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Herinnering: behandeling van {relation_about} bij {{organization}}",
                        "gctt_body" => "Beste {{greeting}},\r\n\r\nWij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor de behandeling van {relation_about} bij <strong>{{organization}}</strong>.\r\n\r\nKlik op <a href=\"{{token_url}}\">deze link</a> om te beginnen of ga naar <a href=\"{{site_ask_url}}\">{{site_ask_url}}</a> en voer het kenmerk \"{{token}}\" in.\r\n\r\n{{organization_signature}}"
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.6}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Password reset requested",
                        "gctt_body" => 'To set a new password for the <strong>{{organization}}</strong> site <strong>{project}</strong>, please click on this link:\n{reset_url}'
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.6}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Wachtwoord opnieuw instellen aangevraagd",
                        "gctt_body" => 'Om een nieuw wachtwoord in te stellen voor de <strong>{{organization}}</strong> site <strong>{project}</strong>, klik op deze link:\n{reset_url}'
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.7}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "New account created",
                        "gctt_body" => "A new account has been created for the <strong>{{organization}}</strong> site <strong>{project}</strong>.\r\nTo set your password and activate the account please click on this link:\\n{reset_url}",
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.7}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Nieuw account aangemaakt",
                        "gctt_body" => "Een nieuw account is aangemaakt voor de <strong>{{organization}}</strong> site <strong>{project}</strong>.\r\nOm uw wachtwoord te kiezen en uw account te activeren, klik op deze link:\\n{reset_url}",
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.8}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "New account created",
                        "gctt_body" => "A new account has been created for the <strong>{{organization}}</strong> website <strong>{project}</strong>.\r\nTo log in with your organization account {login_name} please click on this link:\\r\\n{login_url}",
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.8}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Nieuw account aangemaakt",
                        "gctt_body" => "Er is voor u een nieuw account aangemaakt voor de <strong>{{organization}}</strong> website <strong>{project}</strong>.\r\nOm in te loggen met uw organisatie account {login_name} klikt u op onderstaande link:\\r\\n{login_url}",
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.9}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Continue later",
                        "gctt_body" => 'Dear {{greeting}},\n\nClick on <a href="{{token_url}}">this link</a> to continue filling out surveys or go to <a href="{{site_ask_url}}">{{site_ask_url}}</a> and enter this token: <strong>{{token}}</strong>\n\n{{organization_signature}}',
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.9}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Later doorgaan",
                        "gctt_body" => 'Beste {{greeting}},\n\nKlik op <a href="{{token_url}}">deze link</a> om verder te gaan met invullen van vragenlijsten of ga naar <a href="{{site_ask_url}}">{{site_ask_url}}</a> en voer dit kenmerk in: <strong>{{token}}</strong>\n\n{{organization_signature}}',
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.10}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "There is no survey waiting for your input at the moment",
                        "gctt_body" => 'Dear {{greeting}},\n\nThere is no survey waiting for your input at the moment.\nIf you expected there to be survey, please reply to this mail.\n\n{{organization_signature}}',
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.10}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => "Er staan op dit moment geen vragenlijsten voor u klaar",
                        "gctt_body" => 'Beste {{greeting}},\n\nEr staan op dit moment geen vragenlijsten voor u klaar.\nIndien u toch vragenlijsten verwacht had, reageer dan s.v.p. gewoon op deze mail.\n\n{{organization_signature}}',
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.11}}",
                        "gctt_lang" => "en",
                        "gctt_subject" => "Your TFA has been reset",
                        "gctt_body" => 'Your Authenticator TFA has been reset for the <strong>{{organization}}</strong> site <strong>{{project}}</strong>. Next time you log in, you will need to verify your login using SMS TFA, after which you can reactivate Authenticator TFA.',
                    ],
                    [
                        "gctt_id_template" => "{{gems__comm_templates.11}}",
                        "gctt_lang" => "nl",
                        "gctt_subject" => 'Je TFA is gereset',
                        "gctt_body" => 'Je Authenticator TFA voor <strong>{{organization}}</strong> site <strong>{{project}}</strong> is zojuist gewist. De volgende keer dat je inlogt zul je met SMS TFA inloggen, waarna je weer Authenticator TFA kunt instellen.',
                    ],
                ],
            ],
        ];
    }
}