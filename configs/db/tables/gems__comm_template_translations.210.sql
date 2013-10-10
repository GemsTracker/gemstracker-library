CREATE TABLE if not exists gems__comm_template_translations (
      gctt_id_template  bigint unsigned not null, 
      gctt_lang      varchar(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gctt_subject      varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gctt_body         text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null, 
     

      PRIMARY KEY (gctt_id_template,gctt_lang)
   )
   ENGINE=InnoDB
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__comm_template_translations (gctt_id_template, gctt_lang, gctt_subject, gctt_body)
    VALUES
    (20, 'en', 'Questions for your treatement at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (21, 'en', 'Reminder: your treatement at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}');
