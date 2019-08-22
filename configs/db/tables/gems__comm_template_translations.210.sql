
CREATE TABLE if not exists gems__comm_template_translations (
      gctt_id_template  bigint unsigned not null,
      gctt_lang      varchar(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gctt_subject      varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
      gctt_body         text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,


      PRIMARY KEY (gctt_id_template,gctt_lang)
   )
   ENGINE=InnoDB
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__comm_template_translations (gctt_id_template, gctt_lang, gctt_subject, gctt_body)
    VALUES
    (11, 'en', 'Questions for your treatment at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (11, 'nl', 'Vragen over uw behandeling bij {organization}', 'Beste {greeting},

Recent was u op bezoek bij [b]{organization}[/b] voor een behandeling. Om u goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.

Klik op [url={token_url}]deze link[/url] op te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}'),
    (12, 'en', 'Reminder: your treatment at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (12, 'nl', 'Herinnering: uw behandeling bij {organization}', 'Beste {greeting},

Wij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor uw behandeling bij [b]{organization}[/b].

Klik op [url={token_url}]deze link[/url] op te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}'),
    (13, 'en', 'Questions for your treatment at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}

To unsubscribe from these mails [url={organization_unsubscribe_url}]click here[/url].'),
    (13, 'nl', 'Vragen over uw behandeling bij {organization}', 'Beste {greeting},

Recent was u op bezoek bij [b]{organization}[/b] voor een behandeling. Om u goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.

Klik op [url={token_url}]deze link[/url] op te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}

Om geen verdere email te ontvangen [url={organization_unsubscribe_url}]klik hier[/url].'),
    (14, 'en', 'Reminder: your treatment at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}

To unsubscribe from these mails [url={organization_unsubscribe_url}]click here[/url].'),
    (14, 'nl', 'Herinnering: uw behandeling bij {organization}', 'Beste {greeting},

Wij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor uw behandeling bij [b]{organization}[/b].

Klik op [url={token_url}]deze link[/url] op te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}

Om geen verdere email te ontvangen [url={organization_unsubscribe_url}]klik hier[/url].'),
    (15, 'en', 'Questions for the treatment of {relation_about} at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] with {relation_about} for a treatment. For your proper treatment you need to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (15, 'nl', 'Vragen over de behandeling van {relation_about} bij {organization}', 'Beste {greeting},

Recent was u met {relation_about} op bezoek bij [b]{organization}[/b] voor een behandling. Om goed te kunnen behandelen verzoeken wij u enkele vragen te beantwoorden.

Klik op [url={token_url}]deze link[/url] op te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}'),
    (16, 'en', 'Reminder: treatment of {relation_about} at {organization}', 'Dear {greeting},

We remind you that for the proper treatment of {relation_about} at [b]{organization}[/b] we need answers to some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (16, 'nl', 'Herinnering: behandeling van {relation_about} bij {organization}', 'Beste {greeting},

Wij herinneren u eraan dat u nog enkele vragen moet beantwoorden voor de behandeling van {relation_about} bij [b]{organization}[/b].

Klik op [url={token_url}]deze link[/url] op te beginnen of ga naar [url]{site_ask_url}[/url] en voer het kenmerk "{token}" in.

{organization_signature}'),
    (17, 'en', 'Password reset requested', 'To set a new password for the [b]{organization}[/b] site [b]{project}[/b], please click on this link:\n{reset_url}'),
    (17, 'nl', 'Wachtwoord opnieuw instellen aangevraagd', 'Om een nieuw wachtwoord in te stellen voor de [b]{organization}[/b] site [b]{project}[/b], klik op deze link:\n{reset_url}'),
    (18, 'en', 'New account created', 'A new account has been created for the [b]{organization}[/b] site [b]{project}[/b].
To set your password and activate the account please click on this link:\n{reset_url}'),
    (18, 'nl', 'Nieuw account aangemaakt', 'Een nieuw account is aangemaakt voor de [b]{organization}[/b] site [b]{project}[/b].
Om uw wachtwoord te kiezen en uw account te activeren, klik op deze link:\n{reset_url}'),
    (19, 'en', 'New account created', 'A new account has been created for the [b]{organization}[/b] website [b]{project}[/b].
To log in with your organization account {login_name} please click on this link:\r\n{login_url}'),
    (19, 'nl', 'Nieuw account aangemaakt', 'Er is voor u een nieuw account aangemaakt voor de [b]{organization}[/b] website [b]{project}[/b].
Om in te loggen met uw organisatie account {login_name} klikt u op onderstaande link:\r\n{login_url}'),
    (20, 'en', 'Continue later', 'Dear {greeting},\n\nClick on [url={token_url}]this link[/url] to continue filling out surveys or go to [url]{site_ask_url}[/url] and enter this token: [b]{token}[/b]\n\n{organization_signature}'),
    (20, 'nl', 'Later doorgaan', 'Beste {greeting},\n\nKlik op [url={token_url}]deze link[/url] om verder te gaan met invullen van vragenlijsten of ga naar [url]{site_ask_url}[/url] en voer dit kenmerk in: [b]{token}[/b]\n\n{organization_signature}');
