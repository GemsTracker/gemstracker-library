
CREATE TABLE if not exists gems__agenda_activities (
        gaa_id_activity     bigint unsigned not null auto_increment,
        gaa_name            varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gaa_id_organization bigint unsigned null references gems__organizations (gor_id_organization),

        gaa_name_for_resp   varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gaa_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gaa_code            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gaa_active          TINYINT(1) not null default 1,
        gaa_filter          TINYINT(1) not null default 0,

        gaa_changed         timestamp not null default current_timestamp on update current_timestamp,
        gaa_changed_by      bigint unsigned not null,
        gaa_created         timestamp not null default '0000-00-00 00:00:00',
        gaa_created_by      bigint unsigned not null,

        PRIMARY KEY (gaa_id_activity),
        INDEX (gaa_name)
    )
    ENGINE=InnoDB
    auto_increment = 500
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__agenda_procedures (
        gapr_id_procedure    bigint unsigned not null auto_increment,
        gapr_name            varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gapr_id_organization bigint unsigned null references gems__organizations (gor_id_organization),

        gapr_name_for_resp   varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gapr_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gapr_code            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gapr_active          TINYINT(1) not null default 1,
        gapr_filter          TINYINT(1) not null default 0,

        gapr_changed         timestamp not null default current_timestamp on update current_timestamp,
        gapr_changed_by      bigint unsigned not null,
        gapr_created         timestamp not null default '0000-00-00 00:00:00',
        gapr_created_by      bigint unsigned not null,

        PRIMARY KEY (gapr_id_procedure),
        INDEX (gapr_name)
    )
    ENGINE=InnoDB
    auto_increment = 4000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__agenda_staff (
        gas_id_staff        bigint unsigned not null auto_increment,
        gas_name            varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gas_function        varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gas_id_organization bigint unsigned not null references gems__organizations (gor_id_organization),
        gas_id_user         bigint unsigned null references gems__staff (gsf_id_user),

        gas_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gas_active          TINYINT(1) not null default 1,
        gas_filter          TINYINT(1) not null default 0,

        gas_changed         timestamp not null default current_timestamp on update current_timestamp,
        gas_changed_by      bigint unsigned not null,
        gas_created         timestamp not null default '0000-00-00 00:00:00',
        gas_created_by      bigint unsigned not null,

        PRIMARY KEY (gas_id_staff),
        INDEX (gas_name)
    )
    ENGINE=InnoDB
    auto_increment = 3000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__appointments (
        gap_id_appointment      bigint unsigned not null auto_increment,
        gap_id_user             bigint unsigned not null references gems__respondents (grs_id_user),
        gap_id_organization     bigint unsigned not null references gems__organizations (gor_id_organization),

        gap_source              varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'manual',
        gap_id_in_source        varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gap_manual_edit         TINYINT(1) not null default 0,

        gap_code                varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'A',
        -- one off A => Ambulatory, E => Emergency, F => Field, H => Home, I => Inpatient, S => Short stay, V => Virtual
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        -- Not implemented
        -- moodCode http://wiki.ihe.net/index.php?title=1.3.6.1.4.1.19376.1.5.3.1.4.14
        -- one of  PRMS Scheduled, ARQ requested but no date, EVN has occurred

        gap_status              varchar(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'AC',
        -- one off AB => Aborted, AC => active, CA => Cancelled, CO => completed
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        gap_admission_time      datetime not null,
        gap_discharge_time      datetime null,

        gap_id_attended_by      bigint unsigned null references gems__agenda_staff (gas_id_staff),
        gap_id_referred_by      bigint unsigned null references gems__agenda_staff (gas_id_staff),
        gap_id_activity         bigint unsigned null references gems__agenda_activities (gaa_id_activity),
        gap_id_procedure        bigint unsigned null references gems__agenda_procedures (gapr_id_procedure),
        gap_id_location         bigint unsigned null references gems__locations (glo_id_location),

        gap_subject             varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gap_comment             TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gap_changed             timestamp not null default current_timestamp on update current_timestamp,
        gap_changed_by          bigint unsigned not null,
        gap_created             timestamp not null,
        gap_created_by          bigint unsigned not null,

        PRIMARY KEY (gap_id_appointment),
        UNIQUE INDEX (gap_id_in_source, gap_id_organization, gap_source),
        INDEX (gap_id_user, gap_id_organization),
        INDEX (gap_admission_time),
        INDEX (gap_code),
        INDEX (gap_status),
        INDEX (gap_id_attended_by),
        INDEX (gap_id_referred_by),
        INDEX (gap_id_activity),
        INDEX (gap_id_procedure),
        INDEX (gap_id_location)
    )
    ENGINE=InnoDB
    auto_increment = 2000000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__appointment_filters (
        gaf_id                  bigint unsigned auto_increment not null,
        gaf_class               varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gaf_manual_name         varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gaf_calc_name           varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gaf_id_order            int not null default 10,

        -- Generic text fields so the classes can fill them as they please
        gaf_filter_text1        varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gaf_filter_text2        varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gaf_filter_text3        varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gaf_filter_text4        varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gaf_active              TINYINT(1) not null default 1,

        gaf_changed             timestamp not null default current_timestamp on update current_timestamp,
        gaf_changed_by          bigint unsigned not null,
        gaf_created             timestamp not null default '0000-00-00 00:00:00',
        gaf_created_by          bigint unsigned not null,

        PRIMARY KEY (gaf_id)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
CREATE TABLE IF NOT EXISTS `gems__chart_config` (
  `gcc_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gcc_tid` bigint(20) NULL,
  `gcc_rid` bigint(20) NULL,
  `gcc_sid` bigint(20) NULL,
  `gcc_code` varchar(16) COLLATE utf8_unicode_ci NULL,
  `gcc_config` text COLLATE utf8_unicode_ci NULL,
  `gcc_description` varchar(64) COLLATE utf8_unicode_ci NULL,

  `gcc_changed`          timestamp not null default current_timestamp on update current_timestamp,
  `gcc_changed_by`       bigint unsigned not null,
  `gcc_created`          timestamp not null,
  `gcc_created_by`       bigint unsigned not null,

  PRIMARY KEY (`gcc_id`),
  INDEX (gcc_tid),
  INDEX (gcc_rid), 
  INDEX (gcc_sid),
  INDEX (gcc_code)

) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=101;
CREATE TABLE if not exists gems__comm_jobs (
        gcj_id_job bigint unsigned not null auto_increment,
        gcj_id_order      int not null default 10,

        gcj_id_message bigint unsigned not null
                references gems__comm_templates (gct_id_template),

        gcj_id_user_as bigint unsigned not null
                references gems__staff (gsf_id_user),

        gcj_active TINYINT(1) not null default 1,

        -- O Use organization from address
        -- S Use site from address
        -- U Use gcj_id_user_as from address
        -- F Fixed gcj_from_fixed
        gcj_from_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gcj_from_fixed varchar(254) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- M => multiple per respondent, one for each token
        -- O => One per respondent, mark all tokens as send
        -- A => Send only one token, do not mark
        gcj_process_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        -- N => notmailed
        -- R => reminder
        gcj_filter_mode          VARCHAR(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gcj_filter_days_between  INT UNSIGNED NOT NULL DEFAULT 7,
        gcj_filter_max_reminders INT UNSIGNED NOT NULL DEFAULT 3,

        -- Optional filters
        gcj_target tinyint(1) NOT NULL DEFAULT '0',
        gcj_id_organization bigint unsigned null references gems__organizations (gor_id_organization),
        gcj_id_track        int unsigned null references gems__tracks (gtr_id_track),
        gcj_round_description varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gcj_id_survey       int unsigned null references gems__surveys (gsu_id_survey),

        gcj_changed timestamp not null default current_timestamp on update current_timestamp,
        gcj_changed_by bigint unsigned not null,
        gcj_created timestamp not null default '0000-00-00 00:00:00',
        gcj_created_by bigint unsigned not null,

        PRIMARY KEY (gcj_id_job)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__comm_templates (
      gct_id_template bigint unsigned not null AUTO_INCREMENT,

      gct_name        varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gct_target      varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gct_code        varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

      gct_changed     timestamp not null default current_timestamp on update current_timestamp,
      gct_changed_by  bigint unsigned not null,
      gct_created     timestamp not null default '0000-00-00 00:00:00',
      gct_created_by  bigint unsigned not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE KEY (gct_name)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 20
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (15, 'Questions for your treatement at {organization}', 'token', null,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (16, 'Reminder: your treatement at {organization}', 'token', null,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (17, 'Global Password reset', 'staffPassword', 'passwordReset', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (18, 'Global Account created', 'staffPassword', 'accountCreate', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (19, 'Linked account created', 'staff', 'linkedAccountCreated', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

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
    (19, 'en', 'New account created', 'A new account has been created for theÂ [b]{organization}[/b]Â websiteÂ [b]{project}[/b].
To log in with your organization account {login_name}Â please click on this link:\r\n{login_url}'),
    (19, 'nl', 'Nieuw account aangemaakt', 'Er is voor u een nieuw account aangemaakt voor deÂ [b]{organization}[/b] websiteÂ [b]{project}[/b].
Om in te loggen met uw organisatie accountÂ {login_name} klikt u op onderstaande link:\r\n{login_url}');
CREATE TABLE if not exists gems__consents (
      gco_description varchar(20) not null,
      gco_order smallint not null default 10,
      gco_code varchar(20) not null default 'do not use',

      gco_changed timestamp not null default current_timestamp on update current_timestamp,
      gco_changed_by bigint unsigned not null,
      gco_created timestamp not null,
      gco_created_by bigint unsigned not null,

      PRIMARY KEY (gco_description)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


INSERT INTO gems__consents 
    (gco_description, gco_order, gco_code, gco_changed, gco_changed_by, gco_created, gco_created_by) 
    VALUES
    ('Yes', 10, 'consent given', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('No', 20, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('Unknown', 30, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE if not exists gems__groups (
        ggp_id_group              bigint unsigned not null auto_increment,
        ggp_name                  varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        ggp_description           varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        ggp_role                  varchar(150) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'respondent',
        -- The ggp_role value(s) determines someones roles as set in the bootstrap

        ggp_may_set_groups        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        ggp_default_group         bigint unsigned null,

        ggp_group_active          TINYINT(1) not null default 1,
        ggp_staff_members         TINYINT(1) not null default 0,
        ggp_respondent_members    TINYINT(1) not null default 1,
        ggp_allowed_ip_ranges     text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        ggp_respondent_browse     varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        ggp_respondent_edit       varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        ggp_respondent_show       varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        ggp_mask_settings         text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        ggp_changed               timestamp not null default current_timestamp on update current_timestamp,
        ggp_changed_by            bigint unsigned not null,
        ggp_created               timestamp not null,
        ggp_created_by            bigint unsigned not null,

        PRIMARY KEY(ggp_id_group)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 800
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- Default groups
INSERT ignore INTO gems__groups
    (ggp_id_group, ggp_name, ggp_description, ggp_role, ggp_may_set_groups, ggp_group_active, ggp_staff_members, ggp_respondent_members, ggp_changed_by, ggp_created, ggp_created_by)
    VALUES
    (900, 'Super Administrators', 'Super administrators with access to the whole site', 809, '900,901,902,903', 1, 1, 0, 0, current_timestamp, 0),
    (901, 'Site Admins', 'Site Administrators', 808, '901,902,903', 1, 1, 0, 0, current_timestamp, 0),
    (902, 'Local Admins', 'Local Administrators', 807, '903', 1, 1, 0, 0, current_timestamp, 0),
    (903, 'Staff', 'Health care staff', 804, null, 1, 1, 0, 0, current_timestamp, 0),
    (904, 'Respondents', 'Respondents', 802, null, 1, 0, 1, 0, current_timestamp, 0);

CREATE TABLE if not exists gems__locations (
        glo_id_location     bigint unsigned not null auto_increment,
        glo_name            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- Yes, quick and dirty, will correct later (probably)
        glo_organizations     varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_code            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_url             varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_url_route       varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_address_1       varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_address_2       varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_zipcode         varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_city            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_region          varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_iso_country     char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NL',
        glo_phone_1         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_phone_2         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_phone_3         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_phone_4         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_active          TINYINT(1) not null default 1,
        glo_filter          TINYINT(1) not null default 0,

        glo_changed         timestamp not null default current_timestamp on update current_timestamp,
        glo_changed_by      bigint unsigned not null,
        glo_created         timestamp not null default '0000-00-00 00:00:00',
        glo_created_by      bigint unsigned not null,

        PRIMARY KEY (glo_id_location),
        INDEX (glo_name),
        INDEX (glo_match_to)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 600
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__log_activity (
        gla_id              bigint unsigned not null auto_increment,

        gla_action          int unsigned    not null references gems__log_setup     (gls_id_action),
        gla_respondent_id   bigint unsigned null     references gems__respondents   (grs_id_user),

        gla_by              bigint unsigned null     references gems__staff         (gsf_id_user),
        gla_organization    bigint unsigned not null references gems__organizations (gor_id_organization),
        gla_role            varchar(20) character set 'utf8' collate 'utf8_general_ci' not null,

        gla_changed         TINYINT(1) not null default 0,
        gla_message         text character set 'utf8' collate 'utf8_general_ci' null default null,
        gla_data            text character set 'utf8' collate 'utf8_general_ci' null default null,
        gla_method          varchar(10) character set 'utf8' collate 'utf8_general_ci' not null,
        gla_remote_ip       varchar(20) character set 'utf8' collate 'utf8_general_ci' not null,

        gla_created         timestamp not null default current_timestamp,

        PRIMARY KEY (gla_id),
        INDEX (gla_action),
        INDEX (gla_respondent_id),
        INDEX (gla_by),
        INDEX (gla_organization),
        INDEX (gla_role)
   )
   ENGINE=InnoDB
   auto_increment = 100000
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__log_respondent_communications (
        grco_id_action    bigint unsigned not null auto_increment,

        grco_id_to        bigint unsigned not null references gems__respondents (grs_id_user),
        grco_id_by        bigint unsigned null default 0 references gems__staff (gsf_id_user),
        grco_organization bigint unsigned not null references gems__organizations (gor_id_organization),

        grco_id_token     varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null references gems__tokens (gto_id_token),

        grco_method       varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        grco_topic        varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        grco_address      varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        grco_sender       varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        grco_comments     varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        grco_id_message   bigint unsigned null references gems__comm_templates (gct_id_template),

        grco_changed      timestamp not null default current_timestamp,
        grco_changed_by   bigint unsigned not null,
        grco_created      timestamp not null,
        grco_created_by   bigint unsigned not null,

        PRIMARY KEY (grco_id_action)
    )
    ENGINE=InnoDB
    auto_increment = 200000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__log_setup (
        gls_id_action       int unsigned not null auto_increment,
        gls_name            varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null unique,

        gls_when_no_user    TINYINT(1) not null default 0,
        gls_on_action       TINYINT(1) not null default 0,
        gls_on_post         TINYINT(1) not null default 0,
        gls_on_change       TINYINT(1) not null default 1,

        gls_changed         timestamp not null default current_timestamp on update current_timestamp,
        gls_changed_by      bigint unsigned not null,
        gls_created         timestamp not null,
        gls_created_by      bigint unsigned not null,

        PRIMARY KEY (gls_id_action),
        INDEX (gls_name)
    )
    ENGINE=InnoDB
    auto_increment = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

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

CREATE TABLE if not exists gems__mail_servers (
        gms_from       varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gms_server     varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gms_port       smallint unsigned not null default 25,
        gms_ssl        tinyint not null default 0,
        gms_user       varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gms_password   varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gms_encryption varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gms_changed    timestamp not null default current_timestamp on update current_timestamp,
        gms_changed_by bigint unsigned not null,
        gms_created    timestamp not null default '0000-00-00 00:00:00',
        gms_created_by bigint unsigned not null,

        PRIMARY KEY (gms_from)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 20
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE IF NOT EXISTS gems__openrosaforms (
        gof_id              bigint(20) NOT NULL auto_increment,
        gof_form_id         varchar(249) collate utf8_unicode_ci NOT NULL,
        gof_form_version    varchar(249) collate utf8_unicode_ci NOT NULL,
        gof_form_active     int(1) NOT NULL default '1',
        gof_form_title      text collate utf8_unicode_ci NOT NULL,
        gof_form_xml        varchar(64) collate utf8_unicode_ci NOT NULL,
        gof_changed         timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
        gof_changed_by      bigint(20) NOT NULL,
        gof_created         timestamp NOT NULL default '0000-00-00 00:00:00',
        gof_created_by      bigint(20) NOT NULL,
        PRIMARY KEY  (gof_id)
    )
    ENGINE=MyISAM
    AUTO_INCREMENT = 10
    DEFAULT CHARSET=utf8
    COLLATE=utf8_unicode_ci;
CREATE TABLE if not exists gems__organizations (
        gor_id_organization         bigint unsigned not null auto_increment,

        gor_name                    varchar(50)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gor_code                    varchar(20)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_user_class              varchar(30)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'StaffUser',
        gor_location                varchar(255)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_url                     varchar(127)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_url_base                varchar(1270) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_task                    varchar(50)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_provider_id             varchar(10)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- A : separated list of organization numbers that can look at respondents in this organization
        gor_accessible_by           text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_contact_name            varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_contact_email           varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_welcome                 text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  null,
        gor_signature               text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_respondent_edit         varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gor_respondent_show         varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gor_token_ask               varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gor_style                   varchar(15)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems',
        gor_resp_change_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_iso_lang                char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'en',

        gor_has_login               TINYINT(1) not null default 1,
        gor_has_respondents         TINYINT(1) not null default 0,
        gor_add_respondents         TINYINT(1) not null default 1,
        gor_respondent_group        bigint unsigned null references gems__groups (ggp_id_group),
        gor_create_account_template bigint unsigned null,
        gor_reset_pass_template     bigint unsigned null,
        gor_allowed_ip_ranges       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_active                  TINYINT(1) not null default 1,

        gor_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gor_changed_by              bigint unsigned not null,
        gor_created                 timestamp not null,
        gor_created_by              bigint unsigned not null,

        PRIMARY KEY(gor_id_organization),
        KEY (gor_code)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT ignore INTO gems__organizations (gor_id_organization, gor_name, gor_changed, gor_changed_by, gor_created, gor_created_by)
    VALUES
    (70, 'New organization', CURRENT_TIMESTAMP, 0, CURRENT_TIMESTAMP, 0);

CREATE TABLE if not exists gems__patches (
      gpa_id_patch  int unsigned not null auto_increment,

      gpa_level     int unsigned not null default 0,
      gpa_location  varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gpa_name      varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gpa_order     int unsigned not null default 0,

      gpa_sql       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      gpa_executed  TINYINT(1) not null default 0, 
      gpa_completed TINYINT(1) not null default 0, 

      gpa_result    varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

      gpa_changed  timestamp not null default current_timestamp,
      gpa_created  timestamp null,
      
      PRIMARY KEY (gpa_id_patch),
      UNIQUE KEY (gpa_level, gpa_location, gpa_name, gpa_order)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 1
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__patch_levels (
      gpl_level   int unsigned not null unique,

      gpl_created timestamp not null default current_timestamp,

      PRIMARY KEY (gpl_level)
   )
   ENGINE=InnoDB
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__patch_levels (gpl_level, gpl_created)
   VALUES
   (62, CURRENT_TIMESTAMP);

CREATE TABLE if not exists gems__radius_config (
        grcfg_id                bigint(11) NOT NULL auto_increment,
        grcfg_id_organization   bigint(11) NOT NULL references gems__organizations (gor_id_organization),
        grcfg_ip                varchar(39) CHARACTER SET 'utf8' collate utf8_unicode_ci default NULL,
        grcfg_port              int(5) default NULL,
        grcfg_secret            varchar(255) CHARACTER SET 'utf8' collate utf8_unicode_ci default NULL,
        grcfg_encryption        varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        PRIMARY KEY (grcfg_id)
    )
ENGINE=MyISAM
DEFAULT CHARSET=utf8
COLLATE=utf8_unicode_ci;
CREATE TABLE if not exists gems__reception_codes (
      grc_id_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      grc_description       varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      grc_success           TINYINT(1) not null default 0,

      grc_for_surveys       tinyint not null default 0,
      grc_redo_survey       tinyint not null default 0,
      grc_for_tracks        TINYINT(1) not null default 0,
      grc_for_respondents   TINYINT(1) not null default 0,
      grc_overwrite_answers TINYINT(1) not null default 0,
      grc_active            TINYINT(1) not null default 1,

      grc_changed    timestamp not null default current_timestamp on update current_timestamp,
      grc_changed_by bigint unsigned not null,
      grc_created    timestamp not null,
      grc_created_by bigint unsigned not null,

      PRIMARY KEY (grc_id_reception_code),
      INDEX (grc_success)
   )
   ENGINE=InnoDB
   auto_increment = 1
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
    ('OK', '', 1, 1, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('redo', 'Redo survey', 0, 1, 2, 0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('refused', 'Survey refused', 0, 1, 0, 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('retract', 'Consent retracted', 0, 0, 0, 1, 1, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 1, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('stop', 'Stopped participating', 0, 2, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE if not exists gems__respondent2org (
        gr2o_patient_nr         varchar(15) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gr2o_id_organization    bigint unsigned not null references gems__organizations (gor_id_organization),

        gr2o_id_user            bigint unsigned not null references gems__respondents (grs_id_user),

        -- gr2o_id_physician       bigint unsigned null references gems_staff (gsf_id_user),

        -- gr2o_treatment          varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gr2o_mailable           TINYINT(1) not null default 1,
        gr2o_comments           text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gr2o_consent            varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'Unknown'
                                references gems__consents (gco_description),
        gr2o_reception_code     varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
                                references gems__reception_codes (grc_id_reception_code),

        gr2o_opened             timestamp not null default current_timestamp on update current_timestamp,
        gr2o_opened_by          bigint unsigned not null,
        gr2o_changed            timestamp not null,
        gr2o_changed_by         bigint unsigned not null,
        gr2o_created            timestamp not null,
        gr2o_created_by         bigint unsigned not null,

        PRIMARY KEY (gr2o_patient_nr, gr2o_id_organization),
        UNIQUE KEY (gr2o_id_user, gr2o_id_organization),
        INDEX (gr2o_id_organization),
        INDEX (gr2o_opened),
        INDEX (gr2o_reception_code),
        INDEX (gr2o_opened_by),
        INDEX (gr2o_changed_by),
        INDEX (gr2o_consent)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__respondent2track (
        gr2t_id_respondent_track    bigint unsigned not null auto_increment,

        gr2t_id_user                bigint unsigned not null references gems__respondents (grs_id_user),
        gr2t_id_track               int unsigned not null references gems__tracks (gtr_id_track),

        gr2t_track_info             varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gr2t_start_date             datetime null,
        gr2t_end_date               datetime null,
        gr2t_end_date_manual        TINYINT(1) not null default 0,

        gr2t_id_organization        bigint unsigned not null references gems__organizations (gor_id_organization),

        gr2t_mailable               TINYINT(1) not null default 1,
        gr2t_active                 TINYINT(1) not null default 1,
        gr2t_count                  int unsigned not null default 0,
        gr2t_completed              int unsigned not null default 0,

        gr2t_reception_code         varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
                                    references gems__reception_codes (grc_id_reception_code),
        gr2t_comment                varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gr2t_changed                timestamp not null default current_timestamp on update current_timestamp,
        gr2t_changed_by             bigint unsigned not null,
        gr2t_created                timestamp not null,
        gr2t_created_by             bigint unsigned not null,

        PRIMARY KEY (gr2t_id_respondent_track),
        INDEX (gr2t_id_track),
        INDEX (gr2t_id_user),
        INDEX (gr2t_start_date),
        INDEX (gr2t_id_organization),
        INDEX (gr2t_created_by)
    )
    ENGINE=InnoDB
    auto_increment = 100000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__respondent2track2appointment (
        gr2t2a_id_respondent_track  bigint unsigned not null
                                    references gems__respondent2track (gr2t_id_respondent_track),
        gr2t2a_id_app_field         bigint unsigned not null references gems__track_appointments (gtap_id_app_field),
        gr2t2a_id_appointment       bigint unsigned null references gems__appointments (gap_id_appointment),

        gr2t2a_changed              timestamp not null default current_timestamp on update current_timestamp,
        gr2t2a_changed_by           bigint unsigned not null,
        gr2t2a_created              timestamp not null,
        gr2t2a_created_by           bigint unsigned not null,

        PRIMARY KEY(gr2t2a_id_respondent_track, gr2t2a_id_app_field),
        INDEX (gr2t2a_id_appointment)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__respondent2track2field (
        gr2t2f_id_respondent_track bigint unsigned not null references gems__respondent2track (gr2t_id_respondent_track),
        gr2t2f_id_field bigint unsigned not null references gems__track_fields (gtf_id_field),

        gr2t2f_value text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gr2t2f_changed timestamp not null default current_timestamp on update current_timestamp,
        gr2t2f_changed_by bigint unsigned not null,
        gr2t2f_created timestamp not null,
        gr2t2f_created_by bigint unsigned not null,

        PRIMARY KEY(gr2t2f_id_respondent_track,gr2t2f_id_field)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__respondents (
        grs_id_user                bigint unsigned not null auto_increment references gems__user_ids (gui_id_user),

        grs_ssn                    varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null unique key,

        grs_iso_lang               char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'nl',

        grs_email                  varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- grs_initials_name          varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_first_name             varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_surname_prefix         varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_last_name              varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_partner_surname_prefix varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_partner_last_name      varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_gender                 char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'U',
        grs_birthday               date,

        grs_address_1              varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_address_2              varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_zipcode                varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_city                   varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_region                 varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_iso_country            char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NL',
        grs_phone_1                varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grs_phone_2                varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_phone_3                varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_phone_4                varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        grs_changed                timestamp not null default current_timestamp on update current_timestamp,
        grs_changed_by             bigint unsigned not null,
        grs_created                timestamp not null,
        grs_created_by             bigint unsigned not null,

        PRIMARY KEY(grs_id_user),
        INDEX (grs_email)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 30001
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE IF NOT EXISTS gems__respondent_relations (
        grr_id                      bigint(20) NOT NULL AUTO_INCREMENT,
        grr_id_respondent           bigint(20) NOT NULL,
        grr_type                    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- When staff this holds the id
        grr_id_staff                bigint(20) NULL DEFAULT NULL,

        -- when not staff, we need at least name, gender and email
        grr_email                   varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        -- grs_initials_name           varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grr_first_name              varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_surname_prefix          varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grr_last_name               varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_partner_surname_prefix  varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_partner_last_name       varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grr_gender                  char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'U',
        grr_birthdate               date NULL DEFAULT NULL,
        grr_comments                text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        grr_active                  TINYINT(1) not null default 1,

        grr_changed                 timestamp not null default current_timestamp on update current_timestamp,
        grr_changed_by              bigint unsigned not null,
        grr_created                 timestamp not null,
        grr_created_by              bigint unsigned not null,

        PRIMARY KEY (grr_id),
        INDEX grr_id_respondent (grr_id_respondent,grr_id_staff)
    )
    ENGINE=InnoDB
    DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci
    AUTO_INCREMENT = 10001;

CREATE TABLE if not exists gems__roles (
      grl_id_role bigint unsigned not null auto_increment,
      grl_name varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      grl_description varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      grl_parents text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
      -- The grl_parents is a comma-separated list of parents for this role

      grl_privileges text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
      -- The grl_privilege is a comma-separated list of privileges for this role

      grl_changed timestamp not null default current_timestamp on update current_timestamp,
      grl_changed_by bigint unsigned not null,
      grl_created timestamp not null,
      grl_created_by bigint unsigned not null,

      PRIMARY KEY(grl_id_role)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- default roles/privileges

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (800, 'nologin', 'nologin', null,
    'pr.contact.bugs,pr.contact.support,pr.cron.job,pr.nologin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (801, 'guest', 'guest', null,
    'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin,pr.respondent',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (802, 'respondent','respondent', null,
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
    ,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.respondent,pr.plan.summary,pr.plan.token,
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
    (806, 'researcher', 'researcher', null,
    'pr.contact.bugs,pr.contact.gems,pr.contact.support,
    ,pr.cron.job,
    ,pr.export,pr.export.export,
    ,pr.islogin,
    ,pr.plan.consent,pr.plan.consent.export,
	,pr.upgrade,
    ,pr.option.password,pr.option.edit,pr.organization-switch,
	,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.respondent,pr.plan.summary,pr.plan.token',
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
    ,pr.plan.compliance.export,pr.plan.overview.export,
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
    ,pr.track-maintenance.import,pr.track-maintenance.trackperorg',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (809, 'siteadmin', 'site admin', '801,803,804,805,806,807',
    'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.export,pr.export.export,pr.export-html,
    ,pr.group,
    ,pr.mail.log,
    ,pr.organization,pr.organization-switch,
    ,pr.plan.compliance.export,pr.plan.overview.export,
    ,pr.plan.respondent,pr.plan.respondent.export,pr.plan.summary.export,pr.plan.token.export,
    ,pr.project-information,
    ,pr.reception,pr.reception.create,pr.reception.edit,
    ,pr.respondent.delete,pr.respondent.result,pr.respondent.show-deleted,pr.respondent.undelete,
    ,pr.role,
    ,pr.staff,pr.staff.create,pr.staff.deactivate,pr.staff.edit,pr.staff.edit.all,pr.staff.reactivate,pr.staff.see.all,
    ,pr.staff-log,
    ,pr.source,
    ,pr.survey-maintenance,pr.survey-maintenance.answer-import,
    ,pr.token.mail.freetext,pr.token.undelete,
    ,pr.track.check,pr.track.insert,pr.track.undelete,
    ,pr.track-maintenance,pr.track-maintenance.create,pr.track-maintenance.edit,pr.track-maintenance.export,
    ,pr.track-maintenance.import,pr.track-maintenance.trackperorg',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (808, 'super', 'super', '801,803,804,805,806,807,809',
    'pr.agenda-activity,pr.agenda-activity.cleanup,pr.agenda-activity.create,pr.agenda-activity.delete,pr.agenda-activity.edit,
    ,pr.agenda-filters,pr.agenda-filters.create,pr.agenda-filters.delete,pr.agenda-filters.edit,
    ,pr.agenda-procedure,pr.agenda-procedure.cleanup,pr.agenda-procedure.create,pr.agenda-procedure.delete,pr.agenda-procedure.edit,
    ,pr.agenda-staff,pr.agenda-staff.create,pr.agenda-staff.delete,pr.agenda-staff.edit,
    ,pr.comm.job.create,pr.comm.job.edit,pr.comm.job.delete,
    ,pr.consent.delete,
    ,pr.database,pr.database.create,pr.database.delete,pr.database.execute,pr.database.patches,
	,pr.file-import,
    ,pr.group.create,pr.group.edit,
    ,pr.locations,pr.locations.cleanup,pr.locations.create,pr.locations.delete,pr.locations.edit,
    ,pr.log.files,pr.log.files.download,
    ,pr.mail.server,pr.mail.server.create,pr.mail.server.delete,pr.mail.server.edit,
    ,pr.maintenance.clean-cache,pr.maintenance.maintenance-mode,
    ,pr.organization.create,pr.organization.edit,
    ,pr.plan.mail-as-application,pr.reception.delete,
    ,pr.respondent.multiorg,
    ,pr.role.create,pr.role.edit,
    ,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.create,pr.source.edit,pr.source.synchronize,
    ,pr.source.synchronize-all,
    ,pr.staff.edit.all,
    ,pr.survey-maintenance.edit,
    ,pr.templates,
    ,pr.track-maintenance.delete,
    ,pr.upgrade,pr.upgrade.all,pr.upgrade.one,pr.upgrade.from,pr.upgrade.to',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
CREATE TABLE if not exists gems__rounds (
        gro_id_round           bigint unsigned not null auto_increment,

        gro_id_track           bigint unsigned not null references gems__tracks (gtr_id_track),
        gro_id_order           int not null default 10,

        gro_id_survey          bigint unsigned not null references gems__surveys (gsu_id_survey),

        --- fields for relations
        gro_id_relationfield   bigint(2) null default null,

        -- Survey_name is a temp copy from __surveys, needed by me to keep an overview as
        -- long as no track editor exists.
        gro_survey_name        varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gro_round_description  varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gro_icon_file          varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gro_changed_event      varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gro_display_event      varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gro_valid_after_id     bigint unsigned null references gems__rounds (gro_id_round),
        gro_valid_after_source varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'tok',
        gro_valid_after_field  varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null
                               default 'gto_valid_from',
        gro_valid_after_unit   char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M',
        gro_valid_after_length int not null default 0,

        gro_valid_for_id       bigint unsigned null references gems__rounds (gro_id_round),
        gro_valid_for_source   varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'nul',
        gro_valid_for_field    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gro_valid_for_unit     char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M',
        gro_valid_for_length   int not null default 0,

        -- Yes, quick and dirty, will correct later (probably)
        gro_organizations     varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gro_active             TINYINT(1) not null default 1,
        gro_code               varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gro_changed            timestamp not null default current_timestamp on update current_timestamp,
        gro_changed_by         bigint unsigned not null,
        gro_created            timestamp not null,
        gro_created_by         bigint unsigned not null,

        PRIMARY KEY (gro_id_round),
        INDEX (gro_id_track, gro_id_order),
        INDEX (gro_id_order),
        INDEX (gro_id_survey)
    )
    ENGINE=InnoDB
    auto_increment = 40000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT ignore INTO gems__rounds (gro_id_track, gro_id_order, gro_id_survey, gro_survey_name, gro_round_description,
    gro_valid_after_id, gro_valid_for_id, gro_active, gro_changed, gro_changed_by, gro_created, gro_created_by)
    VALUES
    (0, 10, 0, 'Dummy for inserted surveys', 'Dummy for inserted surveys',
        0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

UPDATE ignore gems__rounds SET gro_id_round = 0 WHERE gro_survey_name = 'Dummy for inserted surveys';

DELETE FROM gems__rounds WHERE gro_id_round != 0 AND gro_survey_name = 'Dummy for inserted surveys';
CREATE TABLE IF NOT EXISTS gems__sources (
        gso_id_source       int(10) unsigned NOT NULL auto_increment,
        gso_source_name     varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL,

        gso_ls_url          varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL,
        gso_ls_class        varchar(60) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL
                            default 'Gems_Source_LimeSurvey1m9Database',
        gso_ls_adapter      varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_ls_dbhost       varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_ls_database     varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_ls_table_prefix varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_ls_username     varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_ls_password     varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_encryption      varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_ls_charset      varchar(8) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,

        gso_active          tinyint(1) NOT NULL default '1',

        gso_status          varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default NULL,
        gso_last_synch      timestamp NULL default NULL,

        gso_changed         timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
        gso_changed_by      bigint(20) unsigned NOT NULL,
        gso_created         timestamp NOT NULL default '0000-00-00 00:00:00',
        gso_created_by      bigint(20) unsigned NOT NULL,

        PRIMARY KEY  (gso_id_source),
        UNIQUE KEY gso_source_name (gso_source_name),
        UNIQUE KEY gso_ls_url (gso_ls_url)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 20
    DEFAULT CHARSET=utf8;
-- Table containing the project staff
--
CREATE TABLE if not exists gems__staff (
        gsf_id_user				bigint unsigned not null references gems__user_ids (gui_id_user),

        gsf_login				varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gsf_id_organization		bigint not null references gems__organizations (gor_id_organization),

        gsf_active				TINYINT(1) null default 1,

        -- depreciated
        gsf_password			varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
    	gsf_failed_logins		int(11) unsigned null default 0,
        gsf_last_failed			timestamp null,
        -- end depreciated

        gsf_id_primary_group	bigint unsigned references gems__groups (ggp_id_group),
        gsf_iso_lang			char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'en'
								references gems__languages (gml_iso_lang),
        gsf_logout_on_survey	TINYINT(1) not null default 0,
		gsf_mail_watcher		TINYINT(1) not null default 0,

        gsf_email				varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsf_first_name			varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsf_surname_prefix		varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsf_last_name			varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsf_gender				char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
								not null default 'U',
        -- gsf_birthday            date,
        gsf_job_title           varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- gsf_address_1           varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_address_2           varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_zipcode             varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_city                varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_region              varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_iso_country         char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
        --                         references phpwcms__phpwcms_country (country_iso),
        gsf_phone_1				varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_phone_2             varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_phone_3             varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- depreciated
        gsf_reset_key			varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gsf_reset_req			timestamp null,
        -- end depreciated

        gsf_changed				timestamp not null default current_timestamp on update current_timestamp,
        gsf_changed_by			bigint unsigned not null,
        gsf_created				timestamp not null,
        gsf_created_by			bigint unsigned not null,

        PRIMARY KEY (gsf_id_user),
        UNIQUE KEY (gsf_login, gsf_id_organization),
        KEY (gsf_email)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 2001
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__staff2groups (
        gs2g_id_user bigint unsigned not null references gems__staff (gsf_id_user),
        gs2g_id_group bigint unsigned not null references gems__groups (ggp_id_group),

        gs2g_active TINYINT(1) not null default 1,

        gs2g_changed timestamp not null default current_timestamp on update current_timestamp,
        gs2g_changed_by bigint unsigned not null,
        gs2g_created timestamp not null,
        gs2g_created_by bigint unsigned not null,

        PRIMARY KEY (gs2g_id_user, gs2g_id_group)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';



CREATE TABLE if not exists gems__surveys (
        gsu_id_survey               int unsigned not null auto_increment,
        gsu_survey_name             varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gsu_survey_description      varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_surveyor_id             int(11),
        gsu_surveyor_active         TINYINT(1) not null default 1,

        gsu_survey_pdf              varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_beforeanswering_event   varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_completed_event         varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_display_event           varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_id_source               int unsigned not null references gems__sources (gso_id_source),
        gsu_active                  TINYINT(1) not null default 0,
        gsu_status                  varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_id_primary_group        bigint unsigned null references gems__groups (ggp_id_group),

        gsu_insertable              TINYINT(1) not null default 0,
        gsu_valid_for_unit          char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M',
        gsu_valid_for_length        int not null default 6,
        gsu_insert_organizations    varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_result_field            varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_agenda_result           varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_duration                varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_code                    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gsu_export_code             varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gsu_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gsu_changed_by              bigint unsigned not null,
        gsu_created                 timestamp not null,
        gsu_created_by              bigint unsigned not null,

        PRIMARY KEY(gsu_id_survey),
        INDEX (gsu_active),
        INDEX (gsu_surveyor_active),
        INDEX (gsu_code),
        INDEX (gsu_id_primary_group)
    )
    ENGINE=InnoDB
    auto_increment = 500
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__survey_questions (
        gsq_id_survey       int unsigned not null references gems__surveys (gsu_id_survey),
        gsq_name            varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_bin' not null,

        gsq_name_parent     varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_bin',
        gsq_order           int unsigned not null default 10,
        gsq_type            smallint unsigned not null default 1,
        gsq_class           varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsq_group           varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsq_label           text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsq_description     text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsq_changed         timestamp not null default current_timestamp on update current_timestamp,
        gsq_changed_by      bigint unsigned not null,
        gsq_created         timestamp not null,
        gsq_created_by      bigint unsigned not null,

        PRIMARY KEY (gsq_id_survey, gsq_name)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__survey_question_options (
        gsqo_id_survey      int unsigned not null references gems__surveys (gsu_id_survey),
        gsqo_name           varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        -- Order is key as you never now what is in the key used by the providing system
        gsqo_order          int unsigned not null default 0,

        gsqo_key            varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsqo_label          varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsqo_changed        timestamp not null default current_timestamp on update current_timestamp,
        gsqo_changed_by     bigint unsigned not null,
        gsqo_created        timestamp not null,
        gsqo_created_by     bigint unsigned not null,

        PRIMARY KEY (gsqo_id_survey, gsqo_name, gsqo_order)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__tokens (
        gto_id_token            varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gto_id_respondent_track bigint unsigned not null references gems__respondent2track (gr2t_id_respondent_track),
        gto_id_round            bigint unsigned not null references gems__rounds (gro_id_round),

        -- non-changing fields calculated from previous two:
        gto_id_respondent       bigint unsigned not null references gems__respondents (grs_id_user),
        gto_id_organization     bigint unsigned not null references gems__organizations (gor_id_organization),
        gto_id_track            bigint unsigned not null references gems__tracks (gtr_id_track),

        -- values initially filled from gems__rounds, but that may get different values later on
        gto_id_survey           bigint unsigned not null references gems__surveys (gsu_id_survey),

        -- values initially filled from gems__rounds, but that might get different values later on, but but not now
        gto_round_order         int not null default 10,
        gto_icon_file           varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gto_round_description   varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        --- fields for relations
        gto_id_relationfield    bigint(2) null default null,
        gto_id_relation         bigint(2) null default null,

        -- real data
        gto_valid_from          datetime,
        gto_valid_from_manual   TINYINT(1) not null default 0,
        gto_valid_until         datetime,
        gto_valid_until_manual  TINYINT(1) not null default 0,
        gto_mail_sent_date      date,
        gto_mail_sent_num       int(11) unsigned not null default 0,
        -- gto_next_mail_date      date,  -- deprecated

        gto_start_time          datetime,
        gto_in_source           TINYINT(1) not null default 0,
        gto_by                  bigint(20) unsigned NULL,

        gto_completion_time     datetime,
        gto_duration_in_sec     bigint(20) unsigned NULL,
        -- gto_followup_date       date, -- deprecated
        gto_result              varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gto_comment             text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gto_reception_code      varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
                                references gems__reception_codes (grc_id_reception_code),

        gto_return_url          varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gto_changed             timestamp not null default current_timestamp on update current_timestamp,
        gto_changed_by          bigint unsigned not null,
        gto_created             timestamp not null,
        gto_created_by          bigint unsigned not null,

        PRIMARY KEY (gto_id_token),
        INDEX (gto_id_organization),
        INDEX (gto_id_respondent),
        INDEX (gto_id_survey),
        INDEX (gto_id_track),
        INDEX (gto_id_round),
        INDEX (gto_in_source),
        INDEX (gto_reception_code),
        INDEX (gto_id_respondent_track, gto_round_order),
        INDEX (gto_valid_from, gto_valid_until),
        INDEX (gto_completion_time),
        INDEX (gto_by),
        INDEX (gto_round_order),
        INDEX (gto_created)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__token_attempts (
        gta_id_attempt bigint unsigned not null auto_increment,
        gta_id_token varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gta_ip_address varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gta_datetime timestamp not null default current_timestamp,

        PRIMARY KEY (gta_id_attempt)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 10000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE if not exists gems__token_replacements (
        gtrp_id_token_new           varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtrp_id_token_old           varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gtrp_created                timestamp not null default CURRENT_TIMESTAMP,
        gtrp_created_by             bigint unsigned not null,

        PRIMARY KEY (gtrp_id_token_new),
        INDEX (gtrp_id_token_old)
    )
    ENGINE=InnoDB
    auto_increment = 30000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__tracks (
        gtr_id_track                int unsigned not null auto_increment,
        gtr_track_name              varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null unique key,

        gtr_track_info              varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gtr_code                    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gtr_date_start              date not null,
        gtr_date_until              date null,

        gtr_active                  TINYINT(1) not null default 0,
        gtr_survey_rounds           int unsigned not null default 0,

        gtr_track_class             varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtr_beforefieldupdate_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gtr_calculation_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gtr_completed_event         varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gtr_fieldupdate_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- Yes, quick and dirty
        gtr_organizations           varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gtr_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gtr_changed_by              bigint unsigned not null,
        gtr_created                 timestamp not null,
        gtr_created_by              bigint unsigned not null,

        PRIMARY KEY (gtr_id_track),
        INDEX (gtr_track_name),
        INDEX (gtr_active),
        INDEX (gtr_track_class)
    )
    ENGINE=InnoDB
    auto_increment = 7000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__track_appointments (
        gtap_id_app_field       bigint unsigned not null auto_increment,
        gtap_id_track           int unsigned not null references gems__tracks (gtr_id_track),

        gtap_id_order           int not null default 10,

        gtap_field_name         varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtap_field_code         varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtap_field_description  varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gtap_to_track_info      TINYINT(1) not null default true,
        gtap_track_info_label   TINYINT(1) not null default false,
        gtap_required           TINYINT(1) not null default false,
        gtap_readonly           TINYINT(1) not null default false,

        gtap_filter_id          bigint unsigned null references gems__appointment_filters (gaf_id),
        -- deprecated
        gtap_after_next         TINYINT(1) not null default 1,
        -- deprecated
        gtap_min_diff_length    int not null default 1,
        gtap_min_diff_unit      char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'D',
        gtap_max_diff_exists    TINYINT(1) not null default 0,
        gtap_max_diff_length    int not null default 0,
        gtap_max_diff_unit      char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'D',
        gtap_uniqueness         tinyint unsigned not null default 0,

        gtap_create_track       TINYINT(1) not null default 0,
        gtap_create_wait_days   bigint signed not null default 182,

        gtap_changed            timestamp not null default current_timestamp on update current_timestamp,
        gtap_changed_by         bigint unsigned not null,
        gtap_created            timestamp not null,
        gtap_created_by         bigint unsigned not null,

        PRIMARY KEY (gtap_id_app_field),
        INDEX (gtap_id_track),
        INDEX (gtap_id_order)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 80000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


CREATE TABLE if not exists gems__track_fields (
        gtf_id_field            bigint unsigned not null auto_increment,
        gtf_id_track            int unsigned not null references gems__tracks (gtr_id_track),

        gtf_id_order            int not null default 10,

        gtf_field_name          varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtf_field_code          varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtf_field_description   varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gtf_field_values        text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtf_field_default       varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtf_calculate_using     varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gtf_field_type          varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gtf_to_track_info       TINYINT(1) not null default true,
        gtf_track_info_label    TINYINT(1) not null default false,
        gtf_required            TINYINT(1) not null default false,
        gtf_readonly            TINYINT(1) not null default false,

        gtf_changed             timestamp not null default current_timestamp on update current_timestamp,
        gtf_changed_by          bigint unsigned not null,
        gtf_created             timestamp not null,
        gtf_created_by          bigint unsigned not null,

        PRIMARY KEY (gtf_id_field),
        INDEX (gtf_id_track),
        INDEX (gtf_id_order)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 60000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


-- Support table for generating unique staff/respondent id's
--
CREATE TABLE if not exists gems__user_ids (
        gui_id_user          bigint unsigned not null,

        gui_created          timestamp not null,

        PRIMARY KEY (gui_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__user_logins (
        gul_id_user          bigint unsigned not null auto_increment,

        gul_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null references gems__staff (gsf_login),
        gul_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gul_user_class       varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NoLogin',
        gul_can_login        TINYINT(1) not null default 0,

        gul_changed          timestamp not null default current_timestamp on update current_timestamp,
        gul_changed_by       bigint unsigned not null,
        gul_created          timestamp not null,
        gul_created_by       bigint unsigned not null,

        PRIMARY KEY (gul_id_user),
        UNIQUE (gul_login, gul_id_organization)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 10001
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

/*
-- Code to restore login codes after failed update. You just never know when we might need it again.

UPDATE gems__user_logins
    SET gul_user_class =
    CASE
        WHEN EXISTS(SELECT gsf_id_user FROM gems__staff WHERE gsf_login = gul_login AND gsf_id_organization = gul_id_organization) THEN
            CASE
                WHEN EXISTS(SELECT gup_id_user FROM gems__user_passwords WHERE gup_id_user = gul_id_user) THEN 'StaffUser'
                ELSE 'OldStaffUser'
            END
        WHEN EXISTS(SELECT gr2o_id_user FROM gems__respondent2org WHERE gr2o_patient_nr = gul_login AND gr2o_id_organization = gul_id_organization) THEN 'RespondentUser'
        ELSE 'NoLogin'
    END
    WHERE gul_user_class = 'StaffUser';

*/
-- Table for keeping track of failed login attempts
--
CREATE TABLE if not exists gems__user_login_attempts (
        gula_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gula_id_organization  bigint not null references gems__organizations (gor_id_organization),

    	gula_failed_logins    int(11) unsigned not null default 0,
        gula_last_failed      timestamp null,
        gula_block_until      timestamp null,

        PRIMARY KEY (gula_login, gula_id_organization)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__user_passwords (
        gup_id_user          bigint unsigned not null references gems__user_logins (gul_id_user),

        gup_password         varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gup_reset_key        varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gup_reset_requested  timestamp null,
        gup_reset_required   TINYINT(1) not null default 0,
        gup_last_pwd_change      timestamp not null default 0,  -- Can only have on current_timestamp so default to 0

        gup_changed          timestamp not null default current_timestamp on update current_timestamp,
        gup_changed_by       bigint unsigned not null,
        gup_created          timestamp not null,
        gup_created_by       bigint unsigned not null,

        PRIMARY KEY (gup_id_user),
        UNIQUE KEY (gup_reset_key)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
