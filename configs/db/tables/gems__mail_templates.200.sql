
CREATE TABLE if not exists gems__mail_templates (
      gmt_id_message bigint unsigned not null auto_increment,

      gmt_subject    varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gmt_body       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      -- Yes, quick and dirty, will correct later (probably)
      gmt_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

      gmt_changed timestamp not null default current_timestamp on update current_timestamp,
      gmt_changed_by bigint unsigned not null,
      gmt_created timestamp not null default '0000-00-00 00:00:00',
      gmt_created_by bigint unsigned not null,

      PRIMARY KEY (gmt_id_message),
      UNIQUE KEY (gmt_subject)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 20
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__mail_templates (gmt_subject, gmt_body, gmt_changed, gmt_changed_by, gmt_created, gmt_created_by)
    VALUES
    ('Questions for your treatement at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('Reminder: your treatement at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
