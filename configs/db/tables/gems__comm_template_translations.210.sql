
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
    (15, 'en', 'Questions for your treatement at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (16, 'en', 'Reminder: your treatement at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (17, 'en', 'Password reset requested', 'To set a new password for the [b]{organization}[/b] site [b]{project}[/b], please click on this link:\n{reset_url}'),
    (17, 'nl', 'Wachtwoord opnieuw instellen aangevraagd', 'Om een nieuw wachtwoord in te stellen voor de [b]{organization}[/b] site [b]{project}[/b], klik op deze link:\n{reset_url}'),
    (18, 'en', 'New account created', 'A new account has been created for the [b]{organization}[/b] site [b]{project}[/b].
To set your password and activate the account please click on this link:\n{reset_url}'),
    (18, 'nl', 'Nieuw account aangemaakt', 'Een nieuw account is aangemaakt voor de [b]{organization}[/b] site [b]{project}[/b].
Om uw wachtwoord te kiezen en uw account te activeren, klik op deze link:\n{reset_url}'),
    (19, 'en', 'New account created', 'A new account has been created for the [b]{organization}[/b] website [b]{project}[/b].
To log in with your organization account {login_name} please click on this link:\r\n{login_url}'),
    (19, 'nl', 'Nieuw account aangemaakt', 'Er is voor u een nieuw account aangemaakt voor de [b]{organization}[/b] website [b]{project}[/b].
Om in te loggen met uw organisatie account {login_name} klikt u op onderstaande link:\r\n{login_url}');