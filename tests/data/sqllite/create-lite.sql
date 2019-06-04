
CREATE TABLE gems__agenda_activities (
        gaa_id_activity     INTEGER not null ,
        gaa_name            varchar(250) ,

        gaa_id_organization INTEGER,

        gaa_name_for_resp   varchar(50) ,
        gaa_match_to        varchar(250) ,
        gaa_code            varchar(40) ,

        gaa_active          TINYINT(1) not null default 1,
        gaa_filter          TINYINT(1) not null default 0,

        gaa_changed         TEXT not null default current_timestamp,
        gaa_changed_by      INTEGER not null,
        gaa_created         TEXT not null default '0000-00-00 00:00:00',
        gaa_created_by      INTEGER not null,

        PRIMARY KEY (gaa_id_activity)
    )
    ;


CREATE TABLE gems__agenda_diagnoses (
        gad_diagnosis_code  varchar(50) not null,
        gad_description     varchar(250),

        gad_coding_method   varchar(10) not null default 'DBC',
        gad_code            varchar(40),

        gad_source          varchar(20) not null default 'manual',
        gad_id_in_source    varchar(40),

        gad_active          TINYINT(1) not null default 1,
        gad_filter          TINYINT(1) not null default 0,

        gad_changed         TEXT not null default current_timestamp,
        gad_changed_by      INTEGER not null,
        gad_created         TEXT not null default '0000-00-00 00:00:00',
        gad_created_by      INTEGER not null,

        PRIMARY KEY (gad_diagnosis_code)
    )
    ;


CREATE TABLE gems__agenda_procedures (
        gapr_id_procedure    INTEGER not null ,
        gapr_name            varchar(250) ,

        gapr_id_organization INTEGER,

        gapr_name_for_resp   varchar(50) ,
        gapr_match_to        varchar(250) ,
        gapr_code            varchar(40) ,

        gapr_active          TINYINT(1) not null default 1,
        gapr_filter          TINYINT(1) not null default 0,

        gapr_changed         TEXT not null default current_timestamp,
        gapr_changed_by      INTEGER not null,
        gapr_created         TEXT not null default '0000-00-00 00:00:00',
        gapr_created_by      INTEGER not null,

        PRIMARY KEY (gapr_id_procedure)
    )
    ;


CREATE TABLE gems__agenda_staff (
        gas_id_staff        INTEGER not null ,
        gas_name            varchar(250) ,
        gas_function        varchar(50) ,

        gas_id_organization INTEGER not null,
        gas_id_user         INTEGER,

        gas_match_to        varchar(250) ,

        gas_source          varchar(20) not null default 'manual',
        gas_id_in_source    varchar(40),

        gas_active          TINYINT(1) not null default 1,
        gas_filter          TINYINT(1) not null default 0,

        gas_changed         TEXT not null default current_timestamp,
        gas_changed_by      INTEGER not null,
        gas_created         TEXT not null default '0000-00-00 00:00:00',
        gas_created_by      INTEGER not null,

        PRIMARY KEY (gas_id_staff)
    )
    ;


CREATE TABLE gems__appointments (
        gap_id_appointment      INTEGER not null ,
        gap_id_user             INTEGER not null,
        gap_id_organization     INTEGER not null,

        gap_id_episode          INTEGER,

        gap_source              varchar(20) not null default 'manual',
        gap_id_in_source        varchar(40),
        gap_manual_edit         TINYINT(1) not null default 0,

        gap_code                varchar(1) not null default 'A',
        -- one off A => Ambulatory, E => Emergency, F => Field, H => Home, I => Inpatient, S => Short stay, V => Virtual
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        -- Not implemented
        -- moodCode http://wiki.ihe.net/index.php?title=1.3.6.1.4.1.19376.1.5.3.1.4.14
        -- one of  PRMS Scheduled, ARQ requested but no TEXT, EVN has occurred

        gap_status              varchar(2) not null default 'AC',
        -- one off AB => Aborted, AC => active, CA => Cancelled, CO => completed
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        gap_admission_time      TEXT not null,
        gap_discharge_time      TEXT,

        gap_id_attended_by      INTEGER,
        gap_id_referred_by      INTEGER,
        gap_id_activity         INTEGER,
        gap_id_procedure        INTEGER,
        gap_id_location         INTEGER,
        gap_diagnosis_code      varchar(50),

        gap_subject             varchar(250),
        gap_comment             TEXT,

        gap_changed             TEXT not null default current_timestamp,
        gap_changed_by          INTEGER not null,
        gap_created             TEXT not null,
        gap_created_by          INTEGER not null,

        PRIMARY KEY (gap_id_appointment),
        UNIQUE (gap_id_in_source, gap_id_organization, gap_source)
    )
    ;

CREATE TABLE gems__appointment_filters (
        gaf_id                  INTEGER not null,
        gaf_class               varchar(200) not null,

        gaf_manual_name         varchar(200),
        gaf_calc_name           varchar(200) not null,

        gaf_id_order            INTEGER not null default 10,

        -- Generic text fields so the classes can fill them as they please
        gaf_filter_text1        varchar(200),
        gaf_filter_text2        varchar(200),
        gaf_filter_text3        varchar(200),
        gaf_filter_text4        varchar(200),

        gaf_active              TINYINT(1) not null default 1,

        gaf_changed             TEXT not null default current_timestamp,
        gaf_changed_by          INTEGER not null,
        gaf_created             TEXT not null default '0000-00-00 00:00:00',
        gaf_created_by          INTEGER not null,

        PRIMARY KEY (gaf_id)
    )
    ;
CREATE TABLE "gems__chart_config" (
  "gcc_id" bigint(20) NOT NULL ,
  "gcc_tid" bigint(20),
  "gcc_rid" bigint(20),
  "gcc_sid" bigint(20),
  "gcc_code" varchar(16),
  "gcc_config" text,
  "gcc_description" varchar(64),

  "gcc_changed"          TEXT not null default current_timestamp,
  "gcc_changed_by"       INTEGER not null,
  "gcc_created"          TEXT not null,
  "gcc_created_by"       INTEGER not null,

  PRIMARY KEY ("gcc_id")

)  ;
CREATE TABLE gems__comm_jobs (
        gcj_id_job INTEGER not null ,
        gcj_id_order      INTEGER not null default 10,

        gcj_id_message INTEGER not null,

        gcj_id_user_as INTEGER not null,

        gcj_active TINYINT(1) not null default 1,

        -- O Use organization from address
        -- S Use site from address
        -- U Use gcj_id_user_as from address
        -- F Fixed gcj_from_fixed
        gcj_from_method varchar(1) not null,
        gcj_from_fixed varchar(254),

        -- M => multiple per respondent, one for each token
        -- O => One per respondent, mark all tokens as send
        -- A => Send only one token, do not mark
        gcj_process_method varchar(1) not null,

        -- N => notmailed
        -- R => reminder
        gcj_filter_mode          VARCHAR(1) not null,
        gcj_filter_days_between  INTEGER NOT NULL DEFAULT 7,
        gcj_filter_max_reminders INTEGER NOT NULL DEFAULT 3,

        -- Optional filters
        gcj_target tinyint(1) NOT NULL DEFAULT '0',
        gcj_id_organization INTEGER,
        gcj_id_track        INTEGER,
        gcj_round_description varchar(100),
        gcj_id_survey       INTEGER,

        gcj_changed TEXT not null default current_timestamp,
        gcj_changed_by INTEGER not null,
        gcj_created TEXT not null default '0000-00-00 00:00:00',
        gcj_created_by INTEGER not null,

        PRIMARY KEY (gcj_id_job)
   )
   ;

CREATE TABLE gems__comm_templates (
      gct_id_template INTEGER not null ,

      gct_name        varchar(100) not null,
      gct_target      varchar(32) not null,
      gct_code        varchar(64),

      gct_changed     TEXT not null default current_timestamp,
      gct_changed_by  INTEGER not null,
      gct_created     TEXT not null default '0000-00-00 00:00:00',
      gct_created_by  INTEGER not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE (gct_name)
   )
   ;

INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (15, 'Questions for your treatement at {organization}', 'token',,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (16, 'Reminder: your treatement at {organization}', 'token',,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (17, 'Global Password reset', 'staffPassword', 'passwordReset', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (18, 'Global Account created', 'staffPassword', 'accountCreate', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (19, 'Linked account created', 'staff', 'linkedAccountCreated', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (20, 'Continue later', 'token', 'continue', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__comm_template_translations (
      gctt_id_template  INTEGER not null,
      gctt_lang      varchar(2) not null,
      gctt_subject      varchar(100),
      gctt_body         text,


      PRIMARY KEY (gctt_id_template,gctt_lang)
   )
   ;

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
    (19, 'en', 'New account created', 'A new account has been created for the [b]{organization}[/b] website [b]{project}[/b].
To log in with your organization account {login_name} please click on this link:\r\n{login_url}'),
    (19, 'nl', 'Nieuw account aangemaakt', 'Er is voor u een nieuw account aangemaakt voor de [b]{organization}[/b] website [b]{project}[/b].
Om in te loggen met uw organisatie account {login_name} klikt u op onderstaande link:\r\n{login_url}'),
    (20, 'en', 'Continue later', 'Dear {greeting},\n\nClick on [url={token_url}]this link[/url] to continue filling out surveys or go to [url]{site_ask_url}[/url] and enter this token: [b]{token}[/b]\n\n{organization_signature}'),
    (20, 'nl', 'Later doorgaan', 'Beste {greeting},\n\nKlik op [url={token_url}]deze link[/url] om verder te gaan met invullen van vragenlijsten of ga naar [url]{site_ask_url}[/url] en voer dit kenmerk in: [b]{token}[/b]\n\n{organization_signature}');

CREATE TABLE gems__conditions (
        gcon_id                  INTEGER not null,

        gcon_type                varchar(200) not null,
        gcon_class               varchar(200) not null,
        gcon_name                varchar(200) not null,
        
        -- Generic text fields so the classes can fill them as they please
        gcon_condition_text1        varchar(200),
        gcon_condition_text2        varchar(200),
        gcon_condition_text3        varchar(200),
        gcon_condition_text4        varchar(200),

        gcon_active              TINYINT(1) not null default 1,

        gcon_changed             TEXT not null default current_timestamp,
        gcon_changed_by          INTEGER not null,
        gcon_created             TEXT not null default '0000-00-00 00:00:00',
        gcon_created_by          INTEGER not null,

        PRIMARY KEY (gcon_id)
    )
    ;


CREATE TABLE gems__consents (
      gco_description varchar(20) not null,
      gco_order smallint not null default 10,
      gco_code varchar(20) not null default 'do not use',

      gco_changed TEXT not null default current_timestamp,
      gco_changed_by INTEGER not null,
      gco_created TEXT not null,
      gco_created_by INTEGER not null,

      PRIMARY KEY (gco_description)
    )
    ;


INSERT INTO gems__consents 
    (gco_description, gco_order, gco_code, gco_changed, gco_changed_by, gco_created, gco_created_by) 
    VALUES
    ('Yes', 10, 'consent given', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('No', 20, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('Unknown', 30, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__episodes_of_care (
        gec_episode_of_care_id      INTEGER not null ,
        gec_id_user                 INTEGER not null,
        gec_id_organization         INTEGER not null,

        gec_source                  varchar(20) not null default 'manual',
        gec_id_in_source            varchar(40),
        gec_manual_edit             TINYINT(1) not null default 0,

        gec_status                  varchar(1) not null default 'A',
        -- one off A => active, C => Cancelled, E => Error, F => Finished, O => Onhold, P => Planned, W => Waitlist
        -- see https://www.hl7.org/fhir/episodeofcare.html

        gec_startdate               TEXT not null,
        gec_enddate                 TEXT,

        gec_id_attended_by          INTEGER,

        gec_subject                 varchar(250),
        gec_comment                 text,

        gec_diagnosis               varchar(250),
        gec_diagnosis_data          text,
        gec_extra_data              text,

        gec_changed                 TEXT not null default current_timestamp,
        gec_changed_by              INTEGER not null,
        gec_created                 TEXT not null,
        gec_created_by              INTEGER not null,

        PRIMARY KEY (gec_episode_of_care_id)
    )
    ;


CREATE TABLE gems__groups (
        ggp_id_group              INTEGER not null ,
        ggp_name                  varchar(30) not null,
        ggp_description           varchar(50) not null,

        ggp_role                  varchar(150) not null default 'respondent',
        -- The ggp_role value(s) determines someones roles as set in the bootstrap

        ggp_may_set_groups        varchar(250),
        ggp_default_group         INTEGER,

        ggp_group_active          TINYINT(1) not null default 1,
        ggp_staff_members         TINYINT(1) not null default 0,
        ggp_respondent_members    TINYINT(1) not null default 1,
        ggp_allowed_ip_ranges     text,
        ggp_no_2factor_ip_ranges  text,
        ggp_2factor_set           tinyint not null default 50,
        ggp_2factor_not_set       tinyint not null default 0,

        ggp_respondent_browse     varchar(255),
        ggp_respondent_edit       varchar(255),
        ggp_respondent_show       varchar(255),

        ggp_mask_settings         text,

        ggp_changed               TEXT not null default current_timestamp,
        ggp_changed_by            INTEGER not null,
        ggp_created               TEXT not null,
        ggp_created_by            INTEGER not null,

        PRIMARY KEY(ggp_id_group)
    )
    ;

-- Default groups
INSERT ignore INTO gems__groups
    (ggp_id_group, ggp_name, ggp_description, ggp_role, ggp_may_set_groups, ggp_default_group, ggp_no_2factor_ip_ranges, ggp_group_active, ggp_staff_members, ggp_respondent_members, ggp_changed_by, ggp_created, ggp_created_by)
    VALUES
    (900, 'Super Administrators', 'Super administrators with access to the whole site', 809, '900,901,902,903', 903, '127.0.0.1', 1, 1, 0, 0, current_timestamp, 0),
    (901, 'Site Admins', 'Site Administrators', 808, '901,902,903', 903, '127.0.0.1', 1, 1, 0, 0, current_timestamp, 0),
    (902, 'Local Admins', 'Local Administrators', 807, '903', 903, '127.0.0.1', 1, 1, 0, 0, current_timestamp, 0),
    (903, 'Staff', 'Health care staff', 804,,, '127.0.0.1', 1, 1, 0, 0, current_timestamp, 0),
    (904, 'Respondents', 'Respondents', 802,,, '127.0.0.1', 1, 0, 1, 0, current_timestamp, 0);

CREATE TABLE gems__locations (
        glo_id_location     INTEGER not null ,
        glo_name            varchar(40) ,

        -- Yes, quick and dirty, will correct later (probably)
        glo_organizations     varchar(250) ,

        glo_match_to        varchar(250) ,
        glo_code            varchar(40) ,

        glo_url             varchar(250) ,
        glo_url_route       varchar(250) ,

        glo_address_1       varchar(80) ,
        glo_address_2       varchar(80) ,
        glo_zipcode         varchar(10) ,
        glo_city            varchar(40) ,
        -- glo_region          varchar(40) ,
        glo_iso_country     char(2) not null default 'NL',
        glo_phone_1         varchar(25) ,
        -- glo_phone_2         varchar(25) ,
        -- glo_phone_3         varchar(25) ,
        -- glo_phone_4         varchar(25) ,

        glo_active          TINYINT(1) not null default 1,
        glo_filter          TINYINT(1) not null default 0,

        glo_changed         TEXT not null default current_timestamp,
        glo_changed_by      INTEGER not null,
        glo_created         TEXT not null default '0000-00-00 00:00:00',
        glo_created_by      INTEGER not null,

        PRIMARY KEY (glo_id_location)
    )
    ;

CREATE TABLE gems__log_activity (
        gla_id              INTEGER not null ,

        gla_action          INTEGER not null,
        gla_respondent_id   INTEGER,

        gla_by              INTEGER,
        gla_organization    INTEGER not null,
        gla_role            varchar(20) not null,

        gla_changed         TINYINT(1) not null default 0,
        gla_message         text,
        gla_data            text,
        gla_method          varchar(10) not null,
        gla_remote_ip       varchar(20) not null,

        gla_created         TEXT not null default current_timestamp,

        PRIMARY KEY (gla_id)
   )
   ;


CREATE TABLE gems__log_respondent_communications (
        grco_id_action    INTEGER not null ,

        grco_id_to        INTEGER not null,
        grco_id_by        INTEGER default 0,
        grco_organization INTEGER not null,

        grco_id_token     varchar(9),

        grco_method       varchar(12) not null,
        grco_topic        varchar(120) not null,
        grco_address      varchar(120),
        grco_sender       varchar(120),
        grco_comments     varchar(120),

        grco_id_message   INTEGER,

        grco_changed      TEXT not null default current_timestamp,
        grco_changed_by   INTEGER not null,
        grco_created      TEXT not null,
        grco_created_by   INTEGER not null,

        PRIMARY KEY (grco_id_action)
    )
    ;


CREATE TABLE gems__log_setup (
        gls_id_action       INTEGER not null ,
        gls_name            varchar(64) not null unique,

        gls_when_no_user    TINYINT(1) not null default 0,
        gls_on_action       TINYINT(1) not null default 0,
        gls_on_post         TINYINT(1) not null default 0,
        gls_on_change       TINYINT(1) not null default 1,

        gls_changed         TEXT not null default current_timestamp,
        gls_changed_by      INTEGER not null,
        gls_created         TEXT not null,
        gls_created_by      INTEGER not null,

        PRIMARY KEY (gls_id_action)
    )
    ;

INSERT INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('database.patch',                      0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.run',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.run-all',                    0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.run-sql',                    0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.view',                       0, 1, 0, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('export.index',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('file-import.answers-import',          1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('index.login',                         0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('index.logoff',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('index.resetpassword',                 1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('project-information.maintenance',     1, 1, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('respondent.show',                     0, 1, 0, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.attributes',                   0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.attributes-all',               0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.check',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.check-all',                    0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.synchronize',                  0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.synchronize-all',              0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('survey-maintenance.check',            0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('survey-maintenance.check-all',        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('token.answered',                      1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('token.data-changed',                  1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-all-answers',             1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-all-tracks',              1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-token-answers',           1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-track',                   1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-track-answers',           1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.delete-track',                  0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.edit-track',                    0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.recalc-all-fields',             1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.recalc-fields',                 1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.check-all',         0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.check-track',       0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.export',            1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.import',            1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.recalc-all-fields', 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.recalc-fields',     0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-all',                 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-from',                0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-last',                0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-one',                 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-to',                  0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__mail_servers (
        gms_from       varchar(100) not null,

        gms_server     varchar(100) not null,
        gms_port       smallint not null default 25,
        gms_ssl        tinyint not null default 0,
        gms_user       varchar(100),
        gms_password   varchar(100),

        -- deprecated in 1.8.6  method was never used, now saved with password
        gms_encryption varchar(20),
        -- end deprecated

        gms_changed    TEXT not null default current_timestamp,
        gms_changed_by INTEGER not null,
        gms_created    TEXT not null default '0000-00-00 00:00:00',
        gms_created_by INTEGER not null,

        PRIMARY KEY (gms_from)
    )
    ;


CREATE TABLE gems__openrosaforms (
        gof_id              bigint(20) NOT NULL ,
        gof_form_id         varchar(249) NOT NULL,
        gof_form_version    varchar(249) NOT NULL,
        gof_form_active     int(1) NOT NULL default '1',
        gof_form_title      text NOT NULL,
        gof_form_xml        varchar(64) NOT NULL,
        gof_changed         TEXT NOT NULL default CURRENT_TIMESTAMP,
        gof_changed_by      bigint(20) NOT NULL,
        gof_created         TEXT NOT NULL default '0000-00-00 00:00:00',
        gof_created_by      bigint(20) NOT NULL,
        PRIMARY KEY  (gof_id)
    )
    ;
CREATE TABLE gems__organizations (
        gor_id_organization         INTEGER not null ,

        gor_name                    varchar(50)   not null,
        gor_code                    varchar(20),
        gor_user_class              varchar(30)   not null default 'StaffUser',
        gor_location                varchar(255),
        gor_url                     varchar(127),
        gor_url_base                varchar(1270),
        gor_task                    varchar(50),

        gor_provider_id             varchar(10),

        -- A : separated list of organization numbers that can look at respondents in this organization
        gor_accessible_by           text,

        gor_contact_name            varchar(50),
        gor_contact_email           varchar(127),
        gor_mail_watcher            TINYINT(1) not null default 1,
        gor_welcome                 text,
        gor_signature               text,

        gor_respondent_edit         varchar(255),
        gor_respondent_show         varchar(255),
        gor_respondent_subscribe    varchar(255) default '',
        gor_respondent_unsubscribe  varchar(255) default '',
        gor_token_ask               varchar(255),

        gor_style                   varchar(15)  not null default 'gems',
        gor_resp_change_event       varchar(128) ,
        gor_iso_lang                char(2) not null default 'en',

        gor_has_login               TINYINT(1) not null default 1,
        gor_has_respondents         TINYINT(1) not null default 0,
        gor_add_respondents         TINYINT(1) not null default 1,
        gor_respondent_group        INTEGER,
        gor_create_account_template INTEGER,
        gor_reset_pass_template     INTEGER,
        gor_allowed_ip_ranges       text,
        gor_active                  TINYINT(1) not null default 1,

        gor_changed                 TEXT not null default current_timestamp,
        gor_changed_by              INTEGER not null,
        gor_created                 TEXT not null,
        gor_created_by              INTEGER not null,

        PRIMARY KEY(gor_id_organization)
    )
    ;

INSERT ignore INTO gems__organizations (gor_id_organization, gor_name, gor_changed, gor_changed_by, gor_created, gor_created_by)
    VALUES
    (70, 'New organization', CURRENT_TIMESTAMP, 0, CURRENT_TIMESTAMP, 0);

CREATE TABLE gems__patches (
      gpa_id_patch  INTEGER not null ,

      gpa_level     INTEGER not null default 0,
      gpa_location  varchar(100) not null,
      gpa_name      varchar(30) not null,
      gpa_order     INTEGER not null default 0,

      gpa_sql       text not null,

      gpa_executed  TINYINT(1) not null default 0, 
      gpa_completed TINYINT(1) not null default 0, 

      gpa_result    varchar(255),

      gpa_changed  TEXT not null default current_timestamp,
      gpa_created  TEXT,
      
      PRIMARY KEY (gpa_id_patch),
      UNIQUE (gpa_level, gpa_location, gpa_name, gpa_order)
   )
   ;


CREATE TABLE gems__patch_levels (
      gpl_level   INTEGER not null unique,

      gpl_created TEXT not null default current_timestamp,

      PRIMARY KEY (gpl_level)
   )
   ;

INSERT INTO gems__patch_levels (gpl_level, gpl_created)
   VALUES
   (65, CURRENT_TIMESTAMP);

CREATE TABLE gems__radius_config (
        grcfg_id                bigint(11) NOT NULL ,
        grcfg_id_organization   bigint(11) NOT NULL,
        grcfg_ip                varchar(39),
        grcfg_port              int(5),
        grcfg_secret            varchar(255),

        -- deprecated in 1.8.6  method was never used, now saved with password
        grcfg_encryption        varchar(20),
        -- end deprecated

        PRIMARY KEY (grcfg_id)
    )
;
CREATE TABLE gems__reception_codes (
      grc_id_reception_code varchar(20) not null,
      grc_description       varchar(40) not null,

      grc_success           TINYINT(1) not null default 0,

      grc_for_surveys       tinyint not null default 0,
      grc_redo_survey       tinyint not null default 0,
      grc_for_tracks        TINYINT(1) not null default 0,
      grc_for_respondents   TINYINT(1) not null default 0,
      grc_overwrite_answers TINYINT(1) not null default 0,
      grc_active            TINYINT(1) not null default 1,

      grc_changed    TEXT not null default current_timestamp,
      grc_changed_by INTEGER not null,
      grc_created    TEXT not null,
      grc_created_by INTEGER not null,

      PRIMARY KEY (grc_id_reception_code)
   )
   ;

INSERT INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
    ('OK', '', 1, 1, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('redo', 'Redo survey', 0, 1, 2, 0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),    
    ('refused', 'Survey refused', 0, 1, 0, 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('retract', 'Consent retracted', 0, 0, 0, 1, 1, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 1, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('stop', 'Stopped participating', 0, 2, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('moved', 'Moved to new survey', 0, 1, 0, 0, 0, 1, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__respondent2org (
        gr2o_patient_nr         varchar(15) not null,
        gr2o_id_organization    INTEGER not null,

        gr2o_id_user            INTEGER not null,

        -- gr2o_id_physician       INTEGER,

        -- gr2o_treatment          varchar(200),
        gr2o_email               varchar(100),
        gr2o_mailable           TINYINT(1) not null default 1,
        gr2o_comments           text,

        gr2o_consent            varchar(20) not null default 'Unknown',
        gr2o_reception_code     varchar(20) default 'OK' not null,

        gr2o_opened             TEXT not null default current_timestamp,
        gr2o_opened_by          INTEGER not null,
        gr2o_changed            TEXT not null,
        gr2o_changed_by         INTEGER not null,
        gr2o_created            TEXT not null,
        gr2o_created_by         INTEGER not null,

        PRIMARY KEY (gr2o_patient_nr, gr2o_id_organization),
        UNIQUE (gr2o_id_user, gr2o_id_organization)
    )
    ;


CREATE TABLE gems__respondent2track (
        gr2t_id_respondent_track    INTEGER not null ,

        gr2t_id_user                INTEGER not null,
        gr2t_id_track               INTEGER not null,

        gr2t_track_info             varchar(250) ,
        gr2t_start_date             TEXT,
        gr2t_end_date               TEXT,
        gr2t_end_date_manual        TINYINT(1) not null default 0,

        gr2t_id_organization        INTEGER not null,

        gr2t_mailable               TINYINT(1) not null default 1,
        gr2t_active                 TINYINT(1) not null default 1,
        gr2t_count                  INTEGER not null default 0,
        gr2t_completed              INTEGER not null default 0,

        gr2t_reception_code         varchar(20) default 'OK' not null,
        gr2t_comment                varchar(250),

        gr2t_changed                TEXT not null default current_timestamp,
        gr2t_changed_by             INTEGER not null,
        gr2t_created                TEXT not null,
        gr2t_created_by             INTEGER not null,

        PRIMARY KEY (gr2t_id_respondent_track)
    )
    ;

CREATE TABLE gems__respondent2track2appointment (
        gr2t2a_id_respondent_track  INTEGER not null,
        gr2t2a_id_app_field         INTEGER not null,
        gr2t2a_id_appointment       INTEGER,

        gr2t2a_changed              TEXT not null default current_timestamp,
        gr2t2a_changed_by           INTEGER not null,
        gr2t2a_created              TEXT not null,
        gr2t2a_created_by           INTEGER not null,

        PRIMARY KEY(gr2t2a_id_respondent_track, gr2t2a_id_app_field)
    )
    ;


CREATE TABLE gems__respondent2track2field (
        gr2t2f_id_respondent_track INTEGER not null,
        gr2t2f_id_field INTEGER not null,

        gr2t2f_value text,

        gr2t2f_changed TEXT not null default current_timestamp,
        gr2t2f_changed_by INTEGER not null,
        gr2t2f_created TEXT not null,
        gr2t2f_created_by INTEGER not null,

        PRIMARY KEY(gr2t2f_id_respondent_track,gr2t2f_id_field)
    )
    ;


CREATE TABLE gems__respondents (
        grs_id_user                INTEGER not null,

        grs_ssn                    varchar(128) unique,

        grs_iso_lang               char(2) not null default 'nl',

        -- grs_email                  varchar(100),

        -- grs_initials_name          varchar(30) ,
        grs_first_name             varchar(30) ,
        grs_surname_prefix         varchar(10) ,
        grs_last_name              varchar(50) ,
        -- grs_partner_surname_prefix varchar(10) ,
        -- grs_partner_last_name      varchar(50) ,
        grs_gender                 char(1) not null default 'U',
        grs_birthday               TEXT,

        grs_address_1              varchar(80) ,
        grs_address_2              varchar(80) ,
        grs_zipcode                varchar(10) ,
        grs_city                   varchar(40) ,
        -- grs_region                 varchar(40) ,
        grs_iso_country            char(2) not null default 'NL',
        grs_phone_1                varchar(25) ,
        grs_phone_2                varchar(25) ,
        -- grs_phone_3                varchar(25) ,
        -- grs_phone_4                varchar(25) ,

        grs_changed                TEXT not null default current_timestamp,
        grs_changed_by             INTEGER not null,
        grs_created                TEXT not null,
        grs_created_by             INTEGER not null,

        PRIMARY KEY(grs_id_user)
    )
    ;


CREATE TABLE gems__respondent_relations (
        grr_id                      bigint(20) NOT NULL ,
        grr_id_respondent           bigint(20) NOT NULL,
        grr_type                    varchar(64) ,

        -- When staff this holds the id
        grr_id_staff                bigint(20),

        -- when not staff, we need at least name, gender and email
        grr_email                   varchar(100),
        -- grs_initials_name           varchar(30) ,
        grr_first_name              varchar(30) ,
        -- grs_surname_prefix          varchar(10) ,
        grr_last_name               varchar(50) ,
        -- grs_partner_surname_prefix  varchar(10) ,
        -- grs_partner_last_name       varchar(50) ,
        grr_gender                  char(1) not null default 'U',
        grr_birthdate               TEXT,
        grr_comments                text,

        grr_active                  TINYINT(1) not null default 1,

        grr_changed                 TEXT not null default current_timestamp,
        grr_changed_by              INTEGER not null,
        grr_created                 TEXT not null,
        grr_created_by              INTEGER not null,

        PRIMARY KEY (grr_id)
    )
    
    ;

CREATE TABLE gems__roles (
      grl_id_role INTEGER not null ,
      grl_name varchar(30) not null,
      grl_description varchar(50) not null,

      grl_parents text,
      -- The grl_parents is a comma-separated list of parents for this role

      grl_privileges text,
      -- The grl_privilege is a comma-separated list of privileges for this role

      grl_changed TEXT not null default current_timestamp,
      grl_changed_by INTEGER not null,
      grl_created TEXT not null,
      grl_created_by INTEGER not null,

      PRIMARY KEY(grl_id_role)
   )
   ;

-- default roles/privileges

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (800, 'nologin', 'nologin',,
    'pr.contact.bugs,pr.contact.support,pr.cron.job,pr.nologin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (801, 'guest', 'guest',,
    'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin,pr.respondent',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (802, 'respondent','respondent',,
    'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (803, 'security', 'security', '801',
    'pr.log,pr.log.files,pr.log.files.download,pr.log.maintenance,pr.log.maintenance.edit,
    ,pr.mail.log,
    ,pr.option.edit,pr.option.password,
    ,pr.respondent.show-deleted,pr.respondent.who,
    ,pr.respondent-commlog,pr.respondent-log,
    ,pr.staff,pr.staff.see.all,
    ,pr.staff-log',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (804, 'staff', 'staff', '801',
    'pr.option.edit,pr.option.password,
    ,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.fields,pr.plan.respondent,pr.plan.summary,pr.plan.token,
    ,pr.project,pr.project.questions,
    ,pr.respondent.create,pr.respondent.edit,pr.respondent.select-on-track,pr.respondent.who,
    ,pr.respondent-commlog,pr.respondent-log,
    ,pr.survey,
    ,pr.token,pr.token.answers,pr.token.correct,pr.token.delete,pr.token.edit,pr.token.mail,pr.token.print,
    ,pr.track,pr.track.answers,pr.track.create,pr.track.delete,pr.track.edit',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (805, 'physician', 'physician', '804',
    '',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (806, 'researcher', 'researcher',,
    'pr.contact.bugs,pr.contact.gems,pr.contact.support,
    ,pr.cron.job,
    ,pr.export,pr.export.export,
    ,pr.islogin,
    ,pr.plan.consent,pr.plan.consent.export,
	,pr.upgrade,
    ,pr.option.password,pr.option.edit,pr.organization-switch,
	,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.fields,pr.plan.respondent,pr.plan.summary,pr.plan.token',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (807, 'admin', 'local admin', '801,803,804,805,806',
    'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.export,pr.export.export,pr.export-html,
    ,pr.group,
    ,pr.mail.log,
    ,pr.organization,pr.organization-switch,
    ,pr.plan.compliance.export,pr.plan.overview.export,pr.plan.fields.export,
    ,pr.plan.respondent,pr.plan.respondent.export,pr.plan.summary.export,pr.plan.token.export,
    ,pr.project-information,
    ,pr.reception,pr.reception.create,pr.reception.edit,
    ,pr.respondent.delete,pr.respondent.result,pr.respondent.show-deleted,pr.respondent.undelete,
    ,pr.role,
    ,pr.staff,pr.staff.create,pr.staff.deactivate,pr.staff.edit,pr.staff.reactivate,pr.staff.see.all,
    ,pr.staff-log,
    ,pr.source,
    ,pr.survey-maintenance,pr.survey-maintenance.answer-import,
    ,pr.token.mail.freetext,pr.token.undelete,
    ,pr.track.check,pr.track.insert,pr.track.undelete,
    ,pr.track-maintenance,pr.track-maintenance.create,pr.track-maintenance.edit,pr.track-maintenance.export,
    ,pr.track-maintenance.import,pr.track-maintenance.trackperorg,
    ,pr.conditions,pr.conditions.create,pr.conditions.edit',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (808, 'siteadmin', 'site admin', '801,803,804,805,806,807',
    'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.export,pr.export.export,pr.export-html,
    ,pr.group,pr.group.switch,
    ,pr.mail.log,
    ,pr.maintenance.clean-cache,
    ,pr.organization,pr.organization.check-all,pr.organization.check-org,pr.organization-switch,
    ,pr.plan.compliance.export,pr.plan.overview.export,pr.plan.fields.export,
    ,pr.plan.respondent,pr.plan.respondent.export,pr.plan.summary.export,pr.plan.token.export,
    ,pr.project-information,
    ,pr.reception,pr.reception.create,pr.reception.edit,
    ,pr.respondent.change-org,pr.respondent.delete,pr.respondent.export-html,pr.respondent.result,pr.respondent.show-deleted,pr.respondent.undelete,
    ,pr.role,
    ,pr.staff,pr.staff.create,pr.staff.deactivate,pr.staff.edit,pr.staff.edit.all,pr.staff.reactivate,pr.staff.see.all,
    ,pr.staff-log,
    ,pr.source,pr.source.check-answers,pr.source.check-answers-all,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.synchronize,pr.source.synchronize-all,
    ,pr.survey-maintenance,pr.survey-maintenance.answer-import,pr.survey-maintenance.answer-import,pr.survey-maintenance.check,pr.survey-maintenance.check-all,pr.survey-maintenance.edit.
    ,pr.token.mail.freetext,pr.token.undelete,
    ,pr.track.check,pr.track.insert,pr.track.undelete,
    ,pr.track-maintenance,pr.track-maintenance.check,pr.track-maintenance.check-all,pr.track-maintenance.create,pr.track-maintenance.edit,pr.track-maintenance.export,
    ,pr.track-maintenance.import,pr.track-maintenance.trackperorg,
    ,pr.conditions,pr.conditions.create,pr.conditions.edit',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (809, 'super', 'super', '801,803,804,805,806,807,808',
    'pr.agenda-activity,pr.agenda-activity.cleanup,pr.agenda-activity.create,pr.agenda-activity.delete,pr.agenda-activity.edit,
    ,pr.agenda-filters,pr.agenda-filters.create,pr.agenda-filters.delete,pr.agenda-filters.edit,
    ,pr.agenda-procedure,pr.agenda-procedure.cleanup,pr.agenda-procedure.create,pr.agenda-procedure.delete,pr.agenda-procedure.edit,
    ,pr.agenda-staff,pr.agenda-staff.create,pr.agenda-staff.delete,pr.agenda-staff.edit,
    ,pr.comm.job.create,pr.comm.job.edit,pr.comm.job.delete,
    ,pr.consent.delete,
    ,pr.database,pr.database.create,pr.database.delete,pr.database.execute,pr.database.patches,
    ,pr.episodes.rawdata,
	,pr.file-import,pr.file-import.import,
    ,pr.group.create,pr.group.edit,
    ,pr.locations,pr.locations.cleanup,pr.locations.create,pr.locations.delete,pr.locations.edit,
    ,pr.log.files,pr.log.files.download,
    ,pr.mail.server,pr.mail.server.create,pr.mail.server.delete,pr.mail.server.edit,
    ,pr.maintenance.maintenance-mode,
    ,pr.organization.create,pr.organization.edit,
    ,pr.plan.mail-as-application,pr.reception.delete,
    ,pr.respondent.multiorg,
    ,pr.role.create,pr.role.edit,
    ,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.create,pr.source.edit,pr.source.synchronize,
    ,pr.source.synchronize-all,
    ,pr.staff.edit.all,
    ,pr.survey-maintenance.edit,
    ,pr.templates,
    ,pr.track-maintenance.trackperorg,pr.track-maintenance.delete,
    ,pr.conditions.delete,
    ,pr.upgrade,pr.upgrade.all,pr.upgrade.one,pr.upgrade.from,pr.upgrade.to',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
CREATE TABLE gems__rounds (
        gro_id_round           INTEGER not null ,

        gro_id_track           INTEGER not null,
        gro_id_order           INTEGER not null default 10,

        gro_id_survey          INTEGER not null,

        --- fields for relations
        gro_id_relationfield   bigint(2),

        -- Survey_name is a temp copy from __surveys, needed by me to keep an overview as
        -- long as no track editor exists.
        gro_survey_name        varchar(100) not null,

        gro_round_description  varchar(100),
        gro_icon_file          varchar(100),
        gro_changed_event      varchar(128),
        gro_display_event      varchar(128),

        gro_valid_after_id     INTEGER,
        gro_valid_after_source varchar(12) not null default 'tok',
        gro_valid_after_field  varchar(64) not null
                               default 'gto_valid_from',
        gro_valid_after_unit   char(1) not null default 'M',
        gro_valid_after_length INTEGER not null default 0,

        gro_valid_for_id       INTEGER,
        gro_valid_for_source   varchar(12) not null default 'nul',
        gro_valid_for_field    varchar(64),
        gro_valid_for_unit     char(1) not null default 'M',
        gro_valid_for_length   INTEGER not null default 0,

        gro_condition          INTEGER,

        -- Yes, quick and dirty, will correct later (probably)
        gro_organizations     varchar(250) ,

        gro_active             TINYINT(1) not null default 1,
        gro_code               varchar(64),

        gro_changed            TEXT not null default current_timestamp,
        gro_changed_by         INTEGER not null,
        gro_created            TEXT not null,
        gro_created_by         INTEGER not null,

        PRIMARY KEY (gro_id_round)
    )
    ;

INSERT ignore INTO gems__rounds (gro_id_track, gro_id_order, gro_id_survey, gro_survey_name, gro_round_description,
    gro_valid_after_id, gro_valid_for_id, gro_active, gro_changed, gro_changed_by, gro_created, gro_created_by)
    VALUES
    (0, 10, 0, 'Dummy for inserted surveys', 'Dummy for inserted surveys',
        0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

UPDATE ignore gems__rounds SET gro_id_round = 0 WHERE gro_survey_name = 'Dummy for inserted surveys';

DELETE FROM gems__rounds WHERE gro_id_round != 0 AND gro_survey_name = 'Dummy for inserted surveys';
CREATE TABLE gems__sources (
        gso_id_source       int(10) NOT NULL ,
        gso_source_name     varchar(40) NOT NULL,

        gso_ls_url          varchar(255) NOT NULL,
        gso_ls_class        varchar(60) NOT NULL
                            default 'Gems_Source_LimeSurvey1m9Database',
        gso_ls_adapter      varchar(20),
        gso_ls_dbhost       varchar(127),
        gso_ls_database     varchar(127),
        gso_ls_dbport       mediumint,
        gso_ls_table_prefix varchar(127),
        gso_ls_username     varchar(64),
        gso_ls_password     varchar(255),

        -- deprecated in 1.8.6  method was never used, now saved with password
        gso_encryption      varchar(20),
        -- end deprecated

        gso_ls_default,

        gso_active          tinyint(1) NOT NULL default '1',

        gso_status          varchar(20),
        gso_last_synch      TEXT,

        gso_changed         TEXT NOT NULL default CURRENT_TIMESTAMP,
        gso_changed_by      bigint(20) NOT NULL,
        gso_created         TEXT NOT NULL default '0000-00-00 00:00:00',
        gso_created_by      bigint(20) NOT NULL,

        PRIMARY KEY  (gso_id_source),
        UNIQUE (gso_source_name),
        UNIQUE (gso_ls_url)
    )
    ;
-- Table containing the project staff
--
CREATE TABLE gems__staff (
        gsf_id_user				INTEGER not null,

        gsf_login				varchar(20) not null,
        gsf_id_organization		INTEGER not null,

        gsf_active				TINYINT(1) default 1,

        gsf_id_primary_group	INTEGER,
        gsf_iso_lang			char(2) not null default 'en',
        gsf_logout_on_survey	TINYINT(1) not null default 0,
		gsf_mail_watcher		TINYINT(1) not null default 0,

        gsf_email				varchar(100) ,

        gsf_first_name			varchar(30) ,
        gsf_surname_prefix		varchar(10) ,
        gsf_last_name			varchar(30) ,
        gsf_gender				char(1) not null default 'U',
        -- gsf_birthday            TEXT,
        gsf_job_title           varchar(64) ,

        -- gsf_address_1           varchar(80) ,
        -- gsf_address_2           varchar(80) ,
        -- gsf_zipcode             varchar(10) ,
        -- gsf_city                varchar(40) ,
        -- gsf_region              varchar(40) ,
        -- gsf_iso_country         char(2) --,
        gsf_phone_1				varchar(25) ,
        -- gsf_phone_2             varchar(25) ,
        -- gsf_phone_3             varchar(25) ,

        gsf_changed				TEXT not null default current_timestamp,
        gsf_changed_by			INTEGER not null,
        gsf_created				TEXT not null,
        gsf_created_by			INTEGER not null,

        PRIMARY KEY (gsf_id_user),
        UNIQUE (gsf_login, gsf_id_organization)
    )
    ;


CREATE TABLE gems__surveys (
        gsu_id_survey               INTEGER not null ,
        gsu_survey_name             varchar(100) not null,
        gsu_survey_description      varchar(100) ,

        gsu_surveyor_id             int(11),
        gsu_surveyor_active         TINYINT(1) not null default 1,

        gsu_survey_pdf              varchar(128) ,
        gsu_beforeanswering_event   varchar(128) ,
        gsu_completed_event         varchar(128) ,
        gsu_display_event           varchar(128) ,

        gsu_id_source               INTEGER not null,
        gsu_active                  TINYINT(1) not null default 0,
        gsu_status                  varchar(127) ,

        gsu_id_primary_group        INTEGER,

        gsu_insertable              TINYINT(1) not null default 0,
        gsu_valid_for_unit          char(1) not null default 'M',
        gsu_valid_for_length        INTEGER not null default 6,
        gsu_insert_organizations    varchar(250) ,

        gsu_result_field            varchar(20) ,

        gsu_agenda_result           varchar(20) ,
        gsu_duration                varchar(50) ,

        gsu_code                    varchar(64),
        gsu_export_code             varchar(64),
        gsu_hash                    CHAR(32),

        gsu_changed                 TEXT not null default current_timestamp,
        gsu_changed_by              INTEGER not null,
        gsu_created                 TEXT not null,
        gsu_created_by              INTEGER not null,

        PRIMARY KEY(gsu_id_survey)
    )
    ;


CREATE TABLE gems__survey_questions (
        gsq_id_survey       INTEGER not null,
        gsq_name            varchar(100) not null,

        gsq_name_parent     varchar(100) ,
        gsq_order           INTEGER not null default 10,
        gsq_type            smallint not null default 1,
        gsq_class           varchar(50) ,
        gsq_group           varchar(100) ,

        gsq_label           text ,
        gsq_description     text ,

        gsq_changed         TEXT not null default current_timestamp,
        gsq_changed_by      INTEGER not null,
        gsq_created         TEXT not null,
        gsq_created_by      INTEGER not null,

        PRIMARY KEY (gsq_id_survey, gsq_name)
    )
    ;

CREATE TABLE gems__survey_question_options (
        gsqo_id_survey      INTEGER not null,
        gsqo_name           varchar(100) not null,
        -- Order is key as you never now what is in the key used by the providing system
        gsqo_order          INTEGER not null default 0,

        gsqo_key            varchar(100) ,
        gsqo_label          varchar(100) ,

        gsqo_changed        TEXT not null default current_timestamp,
        gsqo_changed_by     INTEGER not null,
        gsqo_created        TEXT not null,
        gsqo_created_by     INTEGER not null,

        PRIMARY KEY (gsqo_id_survey, gsqo_name, gsqo_order)
    )
    ;

CREATE TABLE gems__tokens (
        gto_id_token            varchar(9) not null,

        gto_id_respondent_track INTEGER not null,
        gto_id_round            INTEGER not null,

        -- non-changing fields calculated from previous two:
        gto_id_respondent       INTEGER not null,
        gto_id_organization     INTEGER not null,
        gto_id_track            INTEGER not null,

        -- values initially filled from gems__rounds, but that may get different values later on
        gto_id_survey           INTEGER not null,

        -- values initially filled from gems__rounds, but that might get different values later on, but but not now
        gto_round_order         INTEGER not null default 10,
        gto_icon_file           varchar(100),
        gto_round_description   varchar(100),

        --- fields for relations
        gto_id_relationfield    bigint(2),
        gto_id_relation         bigint(2),

        -- real data
        gto_valid_from          TEXT,
        gto_valid_from_manual   TINYINT(1) not null default 0,
        gto_valid_until         TEXT,
        gto_valid_until_manual  TINYINT(1) not null default 0,
        gto_mail_sent_date      TEXT,
        gto_mail_sent_num       int(11) not null default 0,
        -- gto_next_mail_date      TEXT,  -- deprecated

        gto_start_time          TEXT,
        gto_in_source           TINYINT(1) not null default 0,
        gto_by                  bigint(20),

        gto_completion_time     TEXT,
        gto_duration_in_sec     bigint(20),
        -- gto_followup_date       TEXT, -- deprecated
        gto_result              varchar(255) ,

        gto_comment             text,
        gto_reception_code      varchar(20) default 'OK' not null,

        gto_return_url          varchar(250),

        gto_changed             TEXT not null default current_timestamp,
        gto_changed_by          INTEGER not null,
        gto_created             TEXT not null,
        gto_created_by          INTEGER not null,

        PRIMARY KEY (gto_id_token)
    )
    ;


CREATE TABLE gems__token_attempts (
        gta_id_attempt      INTEGER not null ,
        gta_id_token        varchar(9) not null,
        gta_ip_address      varchar(64) not null,
        gta_datetime        TEXT not null default current_timestamp,
        gta_activated       TINYINT(1) default 0,


        PRIMARY KEY (gta_id_attempt)
    )
    ;


-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE gems__token_replacements (
        gtrp_id_token_new           varchar(9) not null,
        gtrp_id_token_old           varchar(9) not null,

        gtrp_created                TEXT not null default CURRENT_TIMESTAMP,
        gtrp_created_by             INTEGER not null,

        PRIMARY KEY (gtrp_id_token_new)
    )
    ;


CREATE TABLE gems__tracks (
        gtr_id_track                INTEGER not null ,
        gtr_track_name              varchar(40) not null unique,

        gtr_track_info              varchar(250) ,
        gtr_code                    varchar(64),

        gtr_date_start              TEXT not null,
        gtr_date_until              TEXT,

        gtr_active                  TINYINT(1) not null default 0,
        gtr_survey_rounds           INTEGER not null default 0,

        gtr_track_class             varchar(64) not null,
        gtr_beforefieldupdate_event varchar(128) ,
        gtr_calculation_event       varchar(128) ,
        gtr_completed_event         varchar(128) ,
        gtr_fieldupdate_event       varchar(128) ,

        -- Yes, quick and dirty
        gtr_organizations           varchar(250) ,

        gtr_changed                 TEXT not null default current_timestamp,
        gtr_changed_by              INTEGER not null,
        gtr_created                 TEXT not null,
        gtr_created_by              INTEGER not null,

        PRIMARY KEY (gtr_id_track)
    )
    ;


CREATE TABLE gems__track_appointments (
        gtap_id_app_field       INTEGER not null ,
        gtap_id_track           INTEGER not null,

        gtap_id_order           INTEGER not null default 10,

        gtap_field_name         varchar(200) not null,
        gtap_field_code         varchar(20),
        gtap_field_description  varchar(200),

        gtap_to_track_info      TINYINT(1) not null default true,
        gtap_track_info_label   TINYINT(1) not null default false,
        gtap_required           TINYINT(1) not null default false,
        gtap_readonly           TINYINT(1) not null default false,

        gtap_filter_id          INTEGER,
        -- deprecated
        gtap_after_next         TINYINT(1) not null default 1,
        -- deprecated
        gtap_min_diff_length    INTEGER not null default 1,
        gtap_min_diff_unit      char(1) not null default 'D',
        gtap_max_diff_exists    TINYINT(1) not null default 0,
        gtap_max_diff_length    INTEGER not null default 0,
        gtap_max_diff_unit      char(1) not null default 'D',
        gtap_uniqueness         tinyint not null default 0,

        gtap_create_track       INTEGER not null default 0,
        gtap_create_wait_days   INTEGER signed not null default 182,

        gtap_changed            TEXT not null default current_timestamp,
        gtap_changed_by         INTEGER not null,
        gtap_created            TEXT not null,
        gtap_created_by         INTEGER not null,

        PRIMARY KEY (gtap_id_app_field)
    )
    ;


CREATE TABLE gems__track_fields (
        gtf_id_field            INTEGER not null ,
        gtf_id_track            INTEGER not null,

        gtf_id_order            INTEGER not null default 10,

        gtf_field_name          varchar(200) not null,
        gtf_field_code          varchar(20),
        gtf_field_description   varchar(200),

        gtf_field_values        text,
        gtf_field_default       varchar(50),
        gtf_calculate_using     varchar(50) ,

        gtf_field_type          varchar(20) not null,

        gtf_to_track_info       TINYINT(1) not null default true,
        gtf_track_info_label    TINYINT(1) not null default false,
        gtf_required            TINYINT(1) not null default false,
        gtf_readonly            TINYINT(1) not null default false,

        gtf_changed             TEXT not null default current_timestamp,
        gtf_changed_by          INTEGER not null,
        gtf_created             TEXT not null,
        gtf_created_by          INTEGER not null,

        PRIMARY KEY (gtf_id_field)
    )
    ;


-- Support table for generating unique staff/respondent id's
--
CREATE TABLE gems__user_ids (
        gui_id_user          INTEGER not null,

        gui_created          TEXT not null,

        PRIMARY KEY (gui_id_user)
    )
    ;

-- Table containing the users that are allowed to login
--
CREATE TABLE gems__user_logins (
        gul_id_user          INTEGER not null ,

        gul_login            varchar(30) not null,
        gul_id_organization  INTEGER not null,

        gul_user_class       varchar(30) not null default 'NoLogin',
        gul_can_login        TINYINT(1) not null default 0,

        gul_two_factor_key   varchar(100),
        gul_enable_2factor   TINYINT(1) not null default 1,

        gul_changed          TEXT not null default current_timestamp,
        gul_changed_by       INTEGER not null,
        gul_created          TEXT not null,
        gul_created_by       INTEGER not null,

        PRIMARY KEY (gul_id_user),
        UNIQUE (gul_login, gul_id_organization)
    )
    ;


-- Table for keeping track of failed login attempts
--
CREATE TABLE gems__user_login_attempts (
        gula_login            varchar(30) not null,
        gula_id_organization  INTEGER not null,

    	gula_failed_logins    int(11) not null default 0,
        gula_last_failed      TEXT,
        gula_block_until      TEXT,

        PRIMARY KEY (gula_login, gula_id_organization)
    )
    ;

-- Table containing the users that are allowed to login
--
CREATE TABLE gems__user_passwords (
        gup_id_user          INTEGER not null,

        gup_password         varchar(255),
        gup_reset_key        char(64),
        gup_reset_requested  TEXT,
        gup_reset_required   TINYINT(1) not null default 0,
        gup_last_pwd_change  TEXT not null default 0,  -- Can only have on current_timestamp so default to 0

        gup_changed          TEXT not null default current_timestamp,
        gup_changed_by       INTEGER not null,
        gup_created          TEXT not null,
        gup_created_by       INTEGER not null,

        PRIMARY KEY (gup_id_user),
        UNIQUE (gup_reset_key)
    )
    ;

CREATE TABLE gemsdata__responses (
        gdr_id_response		bigint(20)  NOT NULL ,
        gdr_id_token		varchar(9)  not null,
        gdr_answer_id		varchar(40) not null,
		gdr_answer_row		bigint(20)  NOT NULL default 1,

        gdr_response		text ,

        gdr_changed			TEXT not null default current_timestamp,
        gdr_changed_by		INTEGER not null,
		gdr_created			TEXT not null,
        gdr_created_by		INTEGER not null,

        PRIMARY KEY (gdr_id_response),
        UNIQUE (gdr_id_token, gdr_answer_id, gdr_answer_row)
    )
    ;

