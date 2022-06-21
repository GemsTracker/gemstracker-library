<?php


use Phinx\Seed\AbstractSeed;

class DefaultCommTemplates extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'gct_id_template' => 11,
                'gct_name' => 'Questions for your treatment at {organization}',
                'gct_target' => 'token',
                'gct_code' => null,
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1
            ],
            [
                'gct_id_template' => 12,
                'gct_name' => 'Reminder: your treatment at {organization}',
                'gct_target' => 'token',
                'gct_code' => null,
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1
            ],
            [
                'gct_id_template' => 13,
                'gct_name' => 'Questions for your treatment at {organization} with unsubscribe',
                'gct_target' => 'token',
                'gct_code' => null,
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1
            ],
            [
                'gct_id_template' => 14,
                'gct_name' => 'Reminder: your treatment at {organization} with unsubscribe',
                'gct_target' => 'token',
                'gct_code' => null,
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1
            ],
            [
                'gct_id_template' => 15,
                'gct_name' => 'Questions for treatment of {relation_about} at {organization}',
                'gct_target' => 'token',
                'gct_code' => null,
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1,
            ],
            [
                'gct_id_template' => 16,
                'gct_name' => 'Reminder: treatment of {relation_about} at {organization}',
                'gct_target' => 'token',
                'gct_code' => null,
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1,
            ],
            [
                'gct_id_template' => 17,
                'gct_name' => 'Global Password reset',
                'gct_target' => 'staffPassword',
                'gct_code' => 'passwordReset',
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1,
            ],
            [
                'gct_id_template' => 18,
                'gct_name' => 'Global Account created',
                'gct_target' => 'staffPassword',
                'gct_code' => 'accountCreate',
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1,
            ],
            [
                'gct_id_template' => 19,
                'gct_name' => 'Linked account created',
                'gct_target' => 'staff',
                'gct_code' => 'linkedAccountCreated',
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1,
            ],
            [
                'gct_id_template' => 20,
                'gct_name' => 'Continue later',
                'gct_target' => 'token',
                'gct_code' => 'continue',
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1,
            ],
            [
                'gct_id_template' => 21,
                'gct_name' => 'No open tokens',
                'gct_target' => 'respondent',
                'gct_code' => 'nothingToSend',
                'gct_changed' => $now->format('Y-m-d H:i:s'),
                'gct_changed_by' => 1,
                'gct_created' => $now->format('Y-m-d H:i:s'),
                'gct_created_by' => 1
            ],

        ];

        $commTemplates = $this->table('gems__comm_templates');
        $commTemplates->insert($data)
              ->saveData();



        $subData = [
    [
        'gctt_id_template' => 11,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Questions for your treatment at {organization}',
        'gctt_body' => 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}',
    ],
    [
        'gctt_id_template' => 11,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Vragen over uw behandeling bij {organization}',
        'gctt_body' => 'Beste {greeting},

Recent was u op bezoek bij [b]{organization}[/b] voor een behandeling. Om u goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.

Klik op [url={token_url}]deze link[/url] om te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}',
    ],
    [
        'gctt_id_template' => 12,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Reminder: your treatment at {organization}',
        'gctt_body' => 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}',
    ],
    [
        'gctt_id_template' => 12,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Herinnering: uw behandeling bij {organization}',
        'gctt_body' => 'Beste {greeting},

Wij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor uw behandeling bij [b]{organization}[/b].

Klik op [url={token_url}]deze link[/url] om te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}',
    ],
    [
        'gctt_id_template' => 13,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Questions for your treatment at {organization}',
        'gctt_body' => 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}

To unsubscribe from these mails [url={organization_unsubscribe_url}]click here[/url].',
    ],
    [
        'gctt_id_template' => 13,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Vragen over uw behandeling bij {organization}',
        'gctt_body' => 'Beste {greeting},

Recent was u op bezoek bij [b]{organization}[/b] voor een behandeling. Om u goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.

Klik op [url={token_url}]deze link[/url] om te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}

Om geen verdere email te ontvangen [url={organization_unsubscribe_url}]klik hier[/url].',
    ],
    [
        'gctt_id_template' => 14,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Reminder: your treatment at {organization}',
        'gctt_body' => 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}

To unsubscribe from these mails [url={organization_unsubscribe_url}]click here[/url].',
    ],
    [
        'gctt_id_template' => 14,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Herinnering: uw behandeling bij {organization}',
        'gctt_body' => 'Beste {greeting},

Wij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor uw behandeling bij [b]{organization}[/b].

Klik op [url={token_url}]deze link[/url] om te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}

Om geen verdere email te ontvangen [url={organization_unsubscribe_url}]klik hier[/url].',
    ],
    [
        'gctt_id_template' => 15,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Questions for the treatment of {relation_about} at {organization}',
        'gctt_body' => 'Dear {greeting},

Recently you visited [b]{organization}[/b] with {relation_about} for a treatment. For proper treatment you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}',
    ],
    [
        'gctt_id_template' => 15,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Vragen over de behandeling van {relation_about} bij {organization}',
        'gctt_body' => 'Beste {greeting},

Recent was u met {relation_about} op bezoek bij [b]{organization}[/b] voor een behandeling. Om goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.

Klik op [url={token_url}]deze link[/url] om te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}',
    ],
    [
        'gctt_id_template' => 16,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Reminder: treatment of {relation_about} at {organization}',
        'gctt_body' => 'Dear {greeting},

We remind you that for the proper treatment of {relation_about} at [b]{organization}[/b] we need answers to some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}',
    ],
    [
        'gctt_id_template' => 16,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Herinnering: behandeling van {relation_about} bij {organization}',
        'gctt_body' => 'Beste {greeting},

Wij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor de behandeling van {relation_about} bij [b]{organization}[/b].

Klik op [url={token_url}]deze link[/url] om te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}',
    ],
    [
        'gctt_id_template' => 17,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Password reset requested',
        'gctt_body' => 'To set a new password for the [b]{organization}[/b] site [b]{project}[/b], please click on this link:\n{reset_url}',
    ],
    [
        'gctt_id_template' => 17,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Wachtwoord opnieuw instellen aangevraagd',
        'gctt_body' => 'Om een nieuw wachtwoord in te stellen voor de [b]{organization}[/b] site [b]{project}[/b], klik op deze link:\n{reset_url}',
    ],
    [
        'gctt_id_template' => 18,
        'gctt_lang' => 'en',
        'gctt_subject' => 'New account created',
        'gctt_body' => 'A new account has been created for the [b]{organization}[/b] site [b]{project}[/b].
To set your password and activate the account please click on this link:\n{reset_url}',
    ],
    [
        'gctt_id_template' => 18,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Nieuw account aangemaakt',
        'gctt_body' => 'Een nieuw account is aangemaakt voor de [b]{organization}[/b] site [b]{project}[/b].
Om uw wachtwoord te kiezen en uw account te activeren, klik op deze link:\n{reset_url}',
    ],
    [
        'gctt_id_template' => 19,
        'gctt_lang' => 'en',
        'gctt_subject' => 'New account created',
        'gctt_body' => 'A new account has been created for the [b]{organization}[/b] website [b]{project}[/b].
To log in with your organization account {login_name} please click on this link:\r\n{login_url}',
    ],
    [
        'gctt_id_template' => 19,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Nieuw account aangemaakt',
        'gctt_body' => 'Er is voor u een nieuw account aangemaakt voor de [b]{organization}[/b] website [b]{project}[/b].
Om in te loggen met uw organisatie account {login_name} klikt u op onderstaande link:\r\n{login_url}',
    ],
    [
        'gctt_id_template' => 20,
        'gctt_lang' => 'en',
        'gctt_subject' => 'Continue later',
        'gctt_body' => 'Dear {greeting},\n\nClick on [url={token_url}]this link[/url] to continue filling out surveys or go to [url]{site_ask_url}[/url] and enter this token: [b]{token}[/b]\n\n{organization_signature}',
    ],
    [
        'gctt_id_template' => 20,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Later doorgaan',
        'gctt_body' => 'Beste {greeting},\n\nKlik op [url={token_url}]deze link[/url] om verder te gaan met invullen van vragenlijsten of ga naar [url]{site_ask_url}[/url] en voer dit kenmerk in: [b]{token}[/b]\n\n{organization_signature}',
    ],
    [
        'gctt_id_template' => 21,
        'gctt_lang' => 'en',
        'gctt_subject' => 'There is no survey waiting for your input at the moment',
        'gctt_body' => 'Dear {greeting},\n\nThere is no survey waiting for your input at the moment.\nIf you expected there to be survey, please reply to this mail.\n\n{organization_signature}',
    ],
    [
        'gctt_id_template' => 21,
        'gctt_lang' => 'nl',
        'gctt_subject' => 'Er staan op dit moment geen vragenlijsten voor u klaar',
        'gctt_body' => 'Beste {greeting},\n\nEr staan op dit moment geen vragenlijsten voor u klaar.\nIndien u toch vragenlijsten verwacht had, reageer dan s.v.p. gewoon op deze mail.\n\n{organization_signature}',
    ],
        ];

        $commTemplateTranslations = $this->table('gems__comm_template_translations');
        $commTemplateTranslations->insert($subData)
              ->saveData();
    }
}
