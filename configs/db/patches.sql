-- GEMS VERSION: 1
-- PATCH: Test skip earlier patch levels

SELECT NULL;

-- GEMS VERSION: 27
-- PATCH: Use OK reception code

ALTER TABLE `gems__reception_codes`
    ADD
        `grc_success` BOOLEAN NOT NULL DEFAULT '0'
    AFTER `grc_description`;

INSERT IGNORE INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
        grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_active,
        grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
        ('OK', '', 1, 1, 0, 1, 1, 1, 0, CURRENT_TIMESTAMP, 0, CURRENT_TIMESTAMP);

UPDATE gems__tokens SET gto_reception_code = 'OK' WHERE gto_reception_code IS NULL;

ALTER TABLE gems__tokens
    CHANGE COLUMN gto_reception_code
        gto_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'OK';

UPDATE gems__respondent2track SET gr2t_reception_code = 'OK' WHERE gr2t_reception_code IS NULL;

ALTER TABLE gems__respondent2track
    CHANGE COLUMN gr2t_reception_code
        gr2t_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null;

ALTER TABLE gems__respondent2org
    ADD
        gr2o_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
            references gems__reception_codes (grc_id_reception_code)
    AFTER gr2o_consent;

-- PATCH: Longer patch results

ALTER TABLE `gems__patches` CHANGE `gpa_result` `gpa_result` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- PATCH: datestamp to submitdate
ALTER TABLE  `gems__surveys` CHANGE  `gsu_completion_field`  `gsu_completion_field` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'submitdate',
CHANGE  `gsu_followup_field`  `gsu_followup_field` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'submitdate';

UPDATE `gems__surveys` SET `gsu_completion_field` = 'submitdate' WHERE  `gsu_completion_field` = 'datestamp';
UPDATE `gems__surveys` SET `gsu_followup_field` = 'submitdate' WHERE  `gsu_followup_field` = 'datestamp';

--PATCH: Result storage
ALTER TABLE gems__surveys
    ADD gsu_result_field varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gsu_followup_field;

ALTER TABLE gems__tokens
    ADD gto_result varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gto_followup_date;

-- GEMS VERSION: 28
-- PATCH: Track reception / code options

ALTER TABLE `gems__reception_codes`
    ADD `grc_overwrite_answers` boolean not null default '0'
    AFTER `grc_for_respondents`;

-- PATCH: Performance, indexes
ALTER TABLE `gems__tokens` ADD INDEX(`gto_id_survey`);
ALTER TABLE `gems__tokens` ADD INDEX(`gto_id_track`);
ALTER TABLE `gems__tokens` ADD INDEX(`gto_id_round`);
ALTER TABLE `gems__tokens` ADD INDEX(`gto_in_surveyor`);
ALTER TABLE `gems__reception_codes` ADD INDEX (`grc_success`);
ALTER TABLE `gems__tokens` ADD INDEX (`gto_id_respondent_track`);
ALTER TABLE `gems__tracks` ADD INDEX (`gtr_active`);
ALTER TABLE `gems__surveys` ADD INDEX (`gsu_active`);

-- PATCH: Store who took a survey
ALTER TABLE `gems__tokens` ADD `gto_by` bigint(20) unsigned NULL AFTER  `gto_in_surveyor`;

-- GEMS VERSION: 30
-- PATCH: Round description only when needed
ALTER TABLE `gems__rounds` CHANGE `gro_round_description` `gro_round_description` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL

-- GEMS VERSION: 34
-- PATCH: Clear the surveys list
UPDATE gems__surveys SET gsu_active = 0 WHERE gsu_id_primary_group IS NULL AND gsu_active = 1;

-- GEMS VERSION: 35
-- PATCH: Add gsf_reset_key and 'gsf_reset_req columns
ALTER TABLE `gems__staff` ADD `gsf_reset_key` varchar(64) NULL AFTER `gsf_phone_1`;
ALTER TABLE `gems__staff` ADD `gsf_reset_req`timestamp NULL AFTER `gsf_reset_key`;

-- PATCH: Add gtr_organizations to tracks
ALTER TABLE `gems__tracks` ADD `gtr_organizations` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gtr_track_type` ;
UPDATE gems__tracks
    SET  `gtr_organizations` = (SELECT CONCAT('|', CONVERT(GROUP_CONCAT(gor_id_organization SEPARATOR '|'), CHAR), '|') as orgs FROM gems__organizations WHERE gor_active=1)
    WHERE gtr_active = 1;

-- PATCH: Gewijzigd track model
ALTER TABLE `gems__tracks` ADD `gtr_track_model` VARCHAR(64) NOT NULL DEFAULT 'TrackModel' AFTER `gtr_track_type`;
ALTER TABLE `gems__rounds` ADD `gro_used_date_order` INT(4) NULL AFTER `gro_used_date`,
    ADD `gro_used_date_field` VARCHAR(16) NULL AFTER `gro_used_date_order`;

-- GEMS VERSION: 36
-- PATCH: Store number of failed login attempts
ALTER TABLE `gems__staff` ADD `gsf_failed_logins` int(11) unsigned not null default 0 AFTER `gsf_active`;
ALTER TABLE `gems__staff` ADD `gsf_last_failed` timestamp null AFTER `gsf_failed_logins`;

-- GEMS VERSION: 37
-- PATCH: Allow duplicate location name across patch levels
ALTER TABLE `gems__patches` DROP INDEX `gpa_location` ,
    ADD UNIQUE `gpa_location` ( `gpa_level` , `gpa_location` , `gpa_name` , `gpa_order` ) ;

-- PATCH: Survey event model
ALTER TABLE gems__surveys ADD gsu_completed_event varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gsu_survey_pdf;

-- PATCH: New token table
ALTER TABLE `gems__tokens` CHANGE `gto_in_surveyor` `gto_in_source` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `gems__tokens` ADD `gto_comment` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gto_result`;
ALTER TABLE `gems__tokens` ADD `gto_start_time` DATETIME NULL DEFAULT NULL AFTER `gto_next_mail_date`;
ALTER TABLE `gems__tokens` CHANGE `gto_completion_date` `gto_completion_time` DATETIME NULL DEFAULT NULL;
ALTER TABLE `gems__tokens` ADD `gto_duration_in_sec` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `gto_completion_time`;
ALTER TABLE `gems__tokens` ADD `gto_round_order` INT NOT NULL DEFAULT '10' AFTER `gto_id_survey`;
ALTER TABLE `gems__tokens` ADD `gto_round_description` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gto_round_order`;
ALTER TABLE `gems__tokens` CHANGE `gto_valid_from` `gto_valid_from` DATETIME NULL DEFAULT NULL,
                           CHANGE `gto_valid_until` `gto_valid_until` DATETIME NULL DEFAULT NULL;

UPDATE gems__tokens, gems__rounds
    SET gto_round_order = gro_id_order,
        gto_round_description = gro_round_description
    WHERE gto_id_round = gro_id_round;

ALTER TABLE `gems__tokens` DROP INDEX `gto_id_respondent_track`;
ALTER TABLE `gems__tokens` ADD INDEX `gto_id_respondent_track` ( `gto_id_respondent_track` , `gto_round_order` );

-- PATCH: Rounds events
ALTER TABLE `gems__rounds` ADD `gro_changed_event` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gro_round_description`;

-- PATCH: Track end time
ALTER TABLE `gems__respondent2track` CHANGE `gr2t_start_date` `gr2t_start_date` DATETIME NULL DEFAULT NULL;
ALTER TABLE `gems__respondent2track` ADD `gr2t_end_date` DATETIME NULL DEFAULT NULL AFTER `gr2t_start_date`;

-- PATCH: New reception code
INSERT INTO `gems__reception_codes`
    (`grc_id_reception_code`, `grc_description`, `grc_success`, `grc_for_surveys`, `grc_redo_survey`, `grc_for_tracks`, `grc_for_respondents`, `grc_overwrite_answers`, `grc_active`, `grc_changed`, `grc_changed_by`, `grc_created`, `grc_created_by`)
    VALUES
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 0, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

-- PATCH: Always a track_field for a track
INSERT INTO gems__track_fields
        (gtf_id_track, gtf_id_order, gtf_field_name,
            gtf_field_values, gtf_field_type, gtf_required,
            gtf_changed, gtf_changed_by, gtf_created, gtf_created_by)
    SELECT gtr_id_track as gtf_id_track, 10 as gtf_id_order, 'Description' as gtf_field_name,
            null as gtf_field_values, 'text' as gtf_field_type, 1 as gtf_required,
            CURRENT_TIMESTAMP as gtf_changed, 1 as gtf_changed_by, CURRENT_TIMESTAMP as gtf_created, 1 as gtf_created_by
        FROM gems__tracks WHERE gtr_id_track NOT IN (SELECT gtf_id_track FROM gems__track_fields);

-- GEMS VERSION: 38
-- PATCH: Update source classes
UPDATE gems__sources
    SET gso_ls_class = SUBSTRING(gso_ls_class, 13)
    WHERE SUBSTRING(gso_ls_class, 1, 12) = 'Gems_Source_';

-- PATCH: Start using track engine classes
ALTER TABLE `gems__tracks` ADD `gtr_track_class` varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default ''
    AFTER `gtr_track_model`;

UPDATE `gems__tracks`
   SET `gtr_track_class` =
       CASE
           WHEN gtr_track_type = 'S' THEN 'SingleSurveyEngine'
           WHEN gtr_track_model = 'NewTrackModel' THEN 'AnyStepEngine'
           ELSE 'NextStepEngine'
       END;

ALTER TABLE `gems__tracks` CHANGE `gtr_track_class` `gtr_track_class` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- PATCH: Survey event before answering model
ALTER TABLE gems__surveys ADD gsu_beforeanswering_event varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gsu_survey_pdf;

-- PATCH: Change bsn length to store hash instead of value
ALTER TABLE `gems__respondents` CHANGE `grs_bsn` `grs_bsn` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- GEMS VERSION: 39
-- PATCH: Organization signatures

ALTER TABLE `gems__organizations` ADD `gor_welcome` TEXT NULL DEFAULT NULL AFTER `gor_contact_email`;
ALTER TABLE `gems__organizations` ADD `gor_signature` TEXT NULL DEFAULT NULL AFTER `gor_welcome` ;

-- PATCH: Mail templates per organization
ALTER TABLE `gems__mail_templates` ADD `gmt_organizations` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gmt_body`;
UPDATE gems__mail_templates SET gmt_organizations = (SELECT CONCAT('|', GROUP_CONCAT(gor_id_organization SEPARATOR '|'), '|') FROM gems__organizations);

-- GEMS VERSION: 40
-- PATCH: Organization codes
ALTER TABLE `gems__organizations` ADD gor_code            varchar(20)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gor_name;

-- PATCH: Extra mail logging
RENAME TABLE gems__respondent_communications TO gems__log_respondent_communications;

ALTER TABLE gems__log_respondent_communications ADD grco_sender     varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER grco_address;
ALTER TABLE gems__log_respondent_communications ADD grco_id_message bigint unsigned null references gems__mail_templates (gmt_id_message) AFTER grco_comments;

-- GEMS VERSION: 41
-- PATCH: Corrected misspelling of gtr_organisations
ALTER TABLE gems__tracks CHANGE gtr_organisations gtr_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- PATCH: Assign maintenance mode toggle to super role
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.maintenance') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.maintenance%';

-- GEMS VERSION: 42
-- PATCH: Add mail actions to admin role
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.mail.log') WHERE grl_name = 'admin' AND grl_privileges NOT LIKE '%pr.mail.log%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.mail.server,pr.mail.server.create,pr.mail.server.delete,pr.mail.server.edit') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.mail.server%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.mail.job,pr.mail.job.create,pr.mail.job.delete,pr.mail.job.edit') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.mail.job%';

-- PATCH: Set default for new rounds at days
ALTER TABLE `gems__round_periods` CHANGE `grp_valid_after_unit` `grp_valid_after_unit` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'D',
    CHANGE `grp_valid_for_unit` `grp_valid_for_unit` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'D';

-- PATCH: New user login structure
CREATE TABLE if not exists gems__user_ids (
        gui_id_user          bigint unsigned not null,

        gui_created          timestamp not null,

        PRIMARY KEY (gui_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__user_logins (
        gul_id_user          bigint unsigned not null auto_increment,

        gul_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gul_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gul_user_class       varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NoLogin',
        gul_can_login        boolean not null default 1,

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

CREATE TABLE if not exists gems__user_login_attempts (
        gula_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gula_id_organization  bigint not null references gems__organizations (gor_id_organization),

    	gula_failed_logins    int(11) unsigned not null default 0,
        gula_last_failed      timestamp null,

        PRIMARY KEY (gula_login, gula_id_organization)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__user_passwords (
        gup_id_user          bigint unsigned not null references gems__user_logins (gul_id_user),

        gup_password         varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gup_reset_key        varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gup_reset_requested  timestamp null,
        gup_reset_required   boolean not null default 0,

        gup_changed          timestamp not null default current_timestamp on update current_timestamp,
        gup_changed_by       bigint unsigned not null,
        gup_created          timestamp not null,
        gup_created_by       bigint unsigned not null,

        PRIMARY KEY (gup_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__user_logins (gul_login, gul_id_organization, gul_user_class,
                gul_can_login,
                gul_changed, gul_changed_by, gul_created, gul_created_by)
    SELECT gsf_login, gsf_id_organization, 'OldStaffUser',
                gsf_active,
                gsf_changed, gsf_changed_by, gsf_created, gsf_created_by
        FROM gems__staff WHERE gsf_login IS NOT NULL AND
            gsf_id_organization IS NOT NULL AND
            gsf_id_organization != 0 AND
            (gsf_login, gsf_id_organization) NOT IN (SELECT gul_login, gul_id_organization FROM gems__user_logins);

ALTER TABLE `gems__staff` CHANGE `gsf_id_user` `gsf_id_user` BIGINT( 20 ) UNSIGNED NOT NULL;

ALTER TABLE `gems__staff` CHANGE `gsf_password` `gsf_password` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `gems__staff` ADD UNIQUE `gesf_login` (`gsf_login`, `gsf_id_organization`);

ALTER TABLE gems__organizations ADD gor_style varchar(15) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems' AFTER gor_signature;

INSERT INTO gems__user_ids (gui_id_user, gui_created)
    SELECT gsf_id_user, gsf_created FROM gems__staff WHERE gsf_id_user NOT IN (SELECT gui_id_user FROM gems__user_ids);

INSERT INTO gems__user_ids (gui_id_user, gui_created)
    SELECT grs_id_user, grs_created FROM gems__respondents WHERE grs_id_user NOT IN (SELECT gui_id_user FROM gems__user_ids);

-- PATCH: Extra information for track fields
ALTER TABLE gems__track_fields ADD gtf_field_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gtf_field_name,
    ADD gtf_field_description varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gtf_field_code,
    ADD gtf_readonly boolean not null default false AFTER gtf_required;

-- PATCH: Change Burger Service Nummer to Social Security Number
ALTER TABLE `gems__respondents` CHANGE `grs_bsn` `grs_ssn` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- PATCH: Extending organizations

ALTER TABLE `gems__organizations` ADD UNIQUE INDEX (`gor_code`);

ALTER TABLE `gems__organizations` ADD gor_accessible_by text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gor_task;

ALTER TABLE `gems__organizations`
    ADD gor_has_respondents boolean not null default 1 AFTER gor_iso_lang,
    ADD gor_add_respondents boolean not null default 1 AFTER gor_has_respondents;

UPDATE `gems__organizations` SET gor_has_respondents = COALESCE((SELECT 1 FROM gems__respondent2org WHERE gr2o_id_organization = gor_id_organization GROUP BY gr2o_id_organization), 0);
UPDATE `gems__organizations` SET gor_add_respondents = CASE WHEN gor_has_respondents = 1 AND gor_active = 1 THEN 1 ELSE 0 END;

ALTER TABLE `gems__organizations` ADD gor_respondent_group bigint unsigned null AFTER gor_add_respondents;

ALTER TABLE `gems__organizations` ADD gor_has_login boolean not null default 1 AFTER gor_iso_lang;

UPDATE `gems__organizations` SET gor_has_login = COALESCE((SELECT 1 FROM gems__staff WHERE gsf_id_organization = gor_id_organization GROUP BY gsf_id_organization), 0);

ALTER TABLE `gems__organizations` CHANGE `gor_has_respondents` `gor_has_respondents` TINYINT( 1 ) NOT NULL DEFAULT '0';

-- PATCH: Log failed logins
INSERT INTO  `gems__log_actions` (`glac_id_action`, `glac_name`, `glac_change`, `glac_log`, `glac_created`)
    VALUES (NULL ,  'loginFail',  '0',  '1', CURRENT_TIMESTAMP);

-- PATCH: IP ranges for groups
ALTER TABLE `gems__groups` ADD `ggp_allowed_ip_ranges` TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER `ggp_respondent_members`;

-- PATCH: Roles fields sometimes empty
ALTER TABLE gems__roles CHANGE grl_parents grl_parents  text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;
ALTER TABLE gems__roles CHANGE grl_privileges grl_privileges text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;

-- PATCH: Base URL / installation URL to facilitate org switching
ALTER TABLE gems__organizations ADD `gor_url_base` VARCHAR(1270) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER `gor_url`;

-- PATCH: New fundamental reception code 'STOP'
INSERT INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
        ('stop', 'Stop surveys', 0, 2, 0, 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

--PATCH: Remove unique constraint for staff email
ALTER TABLE  `gems__staff` DROP INDEX  `gsf_email` ,
ADD INDEX  `gsf_email` (  `gsf_email` );

-- GEMS VERSION: 43
-- PATCH: Add comment field to respondent tracks
ALTER TABLE `gems__respondent2track` ADD gr2t_comment varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null AFTER `gr2t_reception_code`;

-- PATCH: Default userdefinition per organization
ALTER TABLE gems__organizations ADD `gor_user_class` VARCHAR( 30 ) NOT NULL DEFAULT 'StaffUser' AFTER  `gor_code`;
ALTER TABLE `gems__radius_config` CHANGE  `grcfg_ip`  `grcfg_ip` VARCHAR( 39 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL

-- GEMS VERSION: 44
-- PATCH: Add icon field to rounds
ALTER TABLE `gems__rounds` ADD gro_icon_file VARCHAR(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER `gro_round_description`;

-- PATCH: Add index for receptioncode to token table
ALTER TABLE  `gems__tokens` ADD INDEX (  `gto_reception_code` )

-- PATCH: Add track completion event
ALTER TABLE `gems__tracks` ADD gtr_completed_event varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gtr_track_class;

-- GEMS VERSION: 45
-- PATCH: Assign attribute sync to super role
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.source.check-attributes') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.source.check-attributes%';

-- GEMS VERSION: 46
-- PATCH: Add charset attribute for source database
ALTER TABLE  `gems__sources` ADD  `gso_ls_charset` VARCHAR( 8 ) default NULL AFTER  `gso_ls_password`;

-- PATCH: Add block until to
ALTER TABLE gems__user_login_attempts ADD gula_block_until timestamp null AFTER gula_last_failed;

-- PATCH: logins are sometimes added autmatically as part of outer join
ALTER TABLE gems__user_logins CHANGE gul_can_login gul_can_login boolean not null default 0;

-- PATCH: make reset keys unique so we now whose key it is
ALTER TABLE `gems__user_passwords` ADD UNIQUE KEY (gup_reset_key);
ALTER TABLE `gems__staff` ADD UNIQUE KEY (gsf_reset_key);

-- GEMS VERSION: 47
-- PATCH: Add return url to tokens
ALTER TABLE gems__tokens ADD gto_return_url varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null AFTER gto_reception_code;
ALTER TABLE `gems__organizations` ADD `gor_allowed_ip_ranges` TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER `gor_respondent_group`;

--PATCH: organization code no longer needs to be unique
ALTER TABLE  `gems__organizations` DROP INDEX  `gor_code` , ADD INDEX  `gor_code` (  `gor_code` );

-- PATCH: Add number of reminders sent to tokens
ALTER TABLE `gems__tokens` ADD `gto_mail_sent_num` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `gto_mail_sent_date`;

-- PATCH: Add column to store maximum number of reminders (default is 3) to mail jobs
ALTER TABLE `gems__mail_jobs` ADD `gmj_filter_max_reminders` INT(11) UNSIGNED NOT NULL DEFAULT 3 AFTER `gmj_filter_days_between`;

-- GEMS VERSION: 48
-- PATCH: Add duration to surveys
ALTER TABLE gems__surveys ADD gsu_duration varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gsu_result_field;

-- PATCH: Allow multi org view for supers
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent.multiorg') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.respondent.multiorg%';

-- PATCH: Add code field to surveys
ALTER TABLE `gems__surveys` ADD gsu_code varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL AFTER gsu_duration;

-- PATCH: Assign deletion of track parts to super role
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.track-maintenance.delete') WHERE grl_privileges LIKE '%pr.track-maintenance.edit%' AND grl_privileges NOT LIKE '%pr.track-maintenance.delete%';

-- PATCH: Add track completion event
ALTER TABLE `gems__tracks` ADD gtr_completed_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;

-- GEMS VERSION: 49
-- PATCH: Speed up show respondent
ALTER TABLE `gems__respondent2org` ADD INDEX ( `gr2o_reception_code` );
ALTER TABLE `gems__tokens` ADD INDEX ( `gto_round_order` );
ALTER TABLE `gems__tokens` ADD INDEX ( `gto_valid_from`,  `gto_valid_until` );
ALTER TABLE `gems__tokens` ADD INDEX ( `gto_completion_time` );
ALTER TABLE `gems__tracks` ADD INDEX ( `gtr_track_name` );

-- PATCH: Add answer display snippets to gems, lengthen class name space
ALTER TABLE `gems__surveys` CHANGE gsu_survey_pdf            gsu_survey_pdf            varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;
ALTER TABLE `gems__surveys` CHANGE gsu_beforeanswering_event gsu_beforeanswering_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;
ALTER TABLE `gems__surveys` CHANGE gsu_completed_event       gsu_completed_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;
ALTER TABLE `gems__surveys` ADD gsu_display_event         varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL AFTER gsu_completed_event;

ALTER TABLE `gems__rounds` CHANGE gro_changed_event gro_changed_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;
ALTER TABLE `gems__rounds` ADD gro_display_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL AFTER gro_changed_event;

ALTER TABLE `gems__tracks` CHANGE gtr_completed_event gtr_completed_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null;

-- GEMS VERSION: 50
-- PATCH: Speedup respondent screen
ALTER TABLE gems__respondent2org
      ADD INDEX (gr2o_id_organization),
      ADD INDEX (gr2o_opened_by),
      ADD INDEX (gr2o_changed_by);

ALTER TABLE gems__respondent2track
      ADD INDEX (gr2t_id_track),
      ADD INDEX (gr2t_id_user),
      ADD INDEX (gr2t_id_organization),
      ADD INDEX (gr2t_start_date);

ALTER TABLE `gems__tokens` ADD INDEX (gto_id_organization);
ALTER TABLE `gems__tokens` ADD INDEX (gto_id_respondent);

ALTER TABLE gems__surveys ADD INDEX (gsu_surveyor_active);

ALTER TABLE gems__tracks ADD INDEX (gtr_track_type), ADD INDEX (gtr_track_class);

-- GEMS VERSION: 51
-- PATCH: Compliance rights
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.plan.compliance')
    WHERE grl_privileges LIKE '%pr.plan.%' AND grl_privileges NOT LIKE '%pr.plan.compliance%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.plan.summary')
    WHERE grl_privileges LIKE '%pr.plan.%' AND grl_privileges NOT LIKE '%pr.plan.summary%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.plan.compliance.excel')
    WHERE grl_privileges LIKE '%pr.plan.%' AND grl_privileges NOT LIKE '%pr.plan.compliance.excel%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.plan.summary.excel')
    WHERE grl_privileges LIKE '%pr.plan.%' AND grl_privileges NOT LIKE '%pr.plan.summary.excel%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.contact.gems')
    WHERE grl_privileges LIKE '%pr.plan.%' AND grl_privileges NOT LIKE '%pr.contact%';

-- PATCH: Longer SSN hashes
ALTER TABLE gems__respondents CHANGE grs_ssn
      grs_ssn varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- GEMS VERSION: 52
-- PATCH: Agenda items
ALTER TABLE gems__surveys ADD gsu_agenda_result varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gsu_result_field;

-- PATCH: Track code
ALTER TABLE gems__tracks ADD gtr_code varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL AFTER gtr_track_info;

-- PATCH: Add last changed date for passwords, default to last changed date on update (= date patch executed)
ALTER TABLE gems__user_passwords ADD gup_last_pwd_change TIMESTAMP NOT NULL DEFAULT 0 AFTER gup_reset_required;
UPDATE gems__user_passwords SET gup_last_pwd_change = gup_changed;

-- PATCH: Organizational provider id
ALTER TABLE gems__organizations ADD
    gor_provider_id varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gor_task;

-- PATCH: Import rights
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.file-import,pr.file-import.auto') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.file-import%';

-- PATCH: Track calculation event
ALTER TABLE gems__tracks
    ADD gtr_calculation_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gtr_track_class;

-- GEMS VERSION: 53
-- PATCH: Check all attribute rights
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.source.check-attributes-all')
    WHERE grl_privileges LIKE '%pr.source.check-attributes%' AND grl_privileges NOT LIKE '%pr.source.check-attributes-all%';

-- PATCH: Rights for agenda administration
UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.locations,pr.locations.create,pr.locations.delete,pr.locations.edit')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.locations%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.agenda-activity,pr.agenda-activity.create,pr.agenda-activity.delete,pr.agenda-activity.edit')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.agenda-activity%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.agenda-procedure,pr.agenda-procedure.create,pr.agenda-procedure.delete,pr.agenda-procedure.edit')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.agenda-procedure%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.agenda-staff,pr.agenda-staff.create,pr.agenda-staff.delete,pr.agenda-staff.edit')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.agenda-staff%';

-- PATCH: Performance, indexes
ALTER TABLE `gems__tokens` ADD INDEX(`gto_by`);

ALTER TABLE `gems__respondent2track` ADD INDEX(`gr2t_created_by`);

-- PATCH: Organization create account template SELECT
ALTER TABLE gems__organizations
    ADD gor_create_account_template bigint unsigned null AFTER gor_allowed_ip_ranges;

-- PATCH: Organization Reset Password template SELECT
ALTER TABLE gems__organizations
    ADD gor_reset_pass_template bigint unsigned null AFTER gor_create_account_template;

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.template')
    WHERE (grl_privileges LIKE '%,pr.mail' OR grl_privileges LIKE '%,pr.mail,%') AND
        grl_privileges NOT LIKE '%pr.comm.template%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.template.create')
    WHERE (grl_privileges LIKE '%,pr.mail.create' OR grl_privileges LIKE '%,pr.mail.create,%') AND
        grl_privileges NOT LIKE '%pr.comm.template.create%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.template.delete')
    WHERE (grl_privileges LIKE '%,pr.mail.delete' OR grl_privileges LIKE '%,pr.mail.delete,%') AND
        grl_privileges NOT LIKE '%pr.comm.template.delete%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.template.edit')
    WHERE (grl_privileges LIKE '%,pr.mail.edit' OR grl_privileges LIKE '%,pr.mail.edit,%') AND
        grl_privileges NOT LIKE '%pr.comm.template.edit%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.template.excel')
    WHERE (grl_privileges LIKE '%,pr.mail.excel' OR grl_privileges LIKE '%,pr.mail.excel,%') AND
        grl_privileges NOT LIKE '%pr.comm.template.excel%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.job')
    WHERE (grl_privileges LIKE '%,pr.mail.job' OR grl_privileges LIKE '%,pr.mail.job,%') AND
        grl_privileges NOT LIKE '%pr.comm.job%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.job.create')
    WHERE (grl_privileges LIKE '%,pr.mail.job.create' OR grl_privileges LIKE '%,pr.mail.job.create,%') AND
        grl_privileges NOT LIKE '%pr.comm.job.create%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.job.delete')
    WHERE (grl_privileges LIKE '%,pr.mail.job.delete' OR grl_privileges LIKE '%,pr.mail.job.delete,%') AND
        grl_privileges NOT LIKE '%pr.comm.job.delete%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.job.edit')
    WHERE (grl_privileges LIKE '%,pr.mail.job.edit' OR grl_privileges LIKE '%,pr.mail.job.edit,%') AND
        grl_privileges NOT LIKE '%pr.comm.job.edit%';

UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.comm.job.excel')
    WHERE (grl_privileges LIKE '%,pr.mail.job.excel' OR grl_privileges LIKE '%,pr.mail.job.excel,%') AND
        grl_privileges NOT LIKE '%pr.comm.job.excel%';

-- PATCH: Store round information in single table

ALTER TABLE gems__rounds
    DROP gro_valid_after,
    DROP gro_valid_for,
    DROP gro_used_date,
    DROP gro_used_date_order,
    DROP gro_used_date_field;

ALTER TABLE gems__rounds ADD
        gro_valid_after_id     bigint unsigned null references gems__rounds (gro_id_round)
        AFTER gro_display_event;

ALTER TABLE gems__rounds ADD
        gro_valid_after_source varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'tok'
        AFTER gro_valid_after_id;

ALTER TABLE gems__rounds ADD
        gro_valid_after_field  varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null
                               default 'gto_valid_from'
        AFTER gro_valid_after_source;

ALTER TABLE gems__rounds ADD
        gro_valid_after_unit   char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M'
        AFTER gro_valid_after_field;

ALTER TABLE gems__rounds ADD
        gro_valid_after_length int not null default 0
        AFTER gro_valid_after_unit;

ALTER TABLE gems__rounds ADD
        gro_valid_for_id       bigint unsigned null references gems__rounds (gro_id_round)
        AFTER gro_valid_after_length;

ALTER TABLE gems__rounds ADD
        gro_valid_for_source   varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'nul'
        AFTER gro_valid_for_id;

ALTER TABLE gems__rounds ADD
        gro_valid_for_field    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
        AFTER gro_valid_for_source;

ALTER TABLE gems__rounds ADD
        gro_valid_for_unit     char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M'
        AFTER gro_valid_for_field;

ALTER TABLE gems__rounds ADD
        gro_valid_for_length   int not null default 0
        AFTER gro_valid_for_unit;

UPDATE gems__rounds, gems__round_periods SET
            gro_valid_after_id     = grp_valid_after_id,
            gro_valid_after_source = grp_valid_after_source,
            gro_valid_after_field  = grp_valid_after_field,
            gro_valid_after_unit   = grp_valid_after_unit,
            gro_valid_after_length = grp_valid_after_length,
            gro_valid_for_id       = grp_valid_for_id,
            gro_valid_for_source   = grp_valid_for_source,
            gro_valid_for_field    = grp_valid_for_field,
            gro_valid_for_unit     = grp_valid_for_unit,
            gro_valid_for_length   = grp_valid_for_length
        WHERE gro_id_round = grp_id_round;

-- PATCH: Insert old mail templates into comm table

INSERT ignore INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    (SELECT gmt_id_message, gmt_subject, 'token', null, gmt_changed, gmt_changed_by, gmt_created, gmt_created_by FROM gems__mail_templates);

INSERT ignore INTO gems__comm_template_translations (gctt_id_template, gctt_lang, gctt_subject, gctt_body)
    (SELECT gmt_id_message, 'en', gmt_subject, gmt_body FROM gems__mail_templates);

INSERT ignore INTO gems__comm_jobs (gcj_id_job,
    gcj_id_message,
    gcj_id_user_as,
    gcj_active,
    gcj_from_method,
    gcj_from_fixed,
    gcj_process_method,
    gcj_filter_mode,
    gcj_filter_days_between,
    gcj_filter_max_reminders,
    gcj_id_organization,
    gcj_id_track,
    gcj_id_survey,
    gcj_changed,
    gcj_changed_by,
    gcj_created,
    gcj_created_by)
    (SELECT gmj_id_job,
        gmj_id_message,
        gmj_id_user_as,
        gmj_active,
        gmj_from_method,
        gmj_from_fixed,
        gmj_process_method,
        gmj_filter_mode,
        gmj_filter_days_between,
        gmj_filter_max_reminders,
        gmj_id_organization,
        gmj_id_track,
        gmj_id_survey,
        gmj_changed,
        gmj_changed_by,
        gmj_created,
        gmj_created_by
        FROM gems__mail_jobs);

-- GEMS VERSION: 54
-- PATCH: add round description as an option in comm jobs
ALTER TABLE gems__comm_jobs ADD
    gcj_round_description varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null
    AFTER gcj_id_track;

-- PATCH: Update rounds to new appointment / field combination
UPDATE gems__rounds SET gro_valid_after_field = CONCAT('f__', gro_valid_after_field)
    WHERE gro_valid_after_source = 'rtr' AND
        SUBSTRING(gro_valid_after_field, 1, 5) != 'gr2t_' AND
        SUBSTRING(gro_valid_after_field, 1, 3) != 'f__';

UPDATE gems__rounds SET gro_valid_for_field = CONCAT('f__', gro_valid_for_field)
    WHERE gro_valid_for_source = 'rtr' AND
        SUBSTRING(gro_valid_for_field, 1, 5) != 'gr2t_' AND
        SUBSTRING(gro_valid_for_field, 1, 3) != 'f__';

-- PATCH: New priviliges
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.cron.job') WHERE grl_privileges NOT LIKE '%pr.cron.job%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.maintenance.clean-cache')
    WHERE grl_privileges LIKE '%pr.maintenance%' AND grl_privileges NOT LIKE '%pr.maintenance.clean-cache%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.maintenance.maintenance-mode')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.maintenance.maintenance-mode%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.plan.consent')
    WHERE grl_privileges LIKE '%pr.plan.respondent%' AND grl_privileges NOT LIKE '%pr.plan.consent%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.plan.consent.excel')
    WHERE grl_privileges LIKE '%pr.plan.respondent.excel%' AND grl_privileges NOT LIKE '%pr.plan.consent.excel%';

-- PATCH: Keeping track of the manual date
ALTER TABLE  gems__tokens CHANGE gto_comment
    gto_comment TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null;
ALTER TABLE gems__tokens ADD gto_valid_from_manual  boolean not null default 0 AFTER gto_valid_from;
ALTER TABLE gems__tokens ADD gto_valid_until_manual boolean not null default 0 AFTER gto_valid_until;
ALTER TABLE gems__respondent2track ADD gr2t_end_date_manual boolean not null default 0 AFTER gr2t_end_date;

-- PATCH: fields to set if a respondent can be mailed in respondent and track
ALTER TABLE  `gems__respondent2org` ADD  `gr2o_mailable` boolean not null default 1 AFTER  `gr2o_comments`;

ALTER TABLE  `gems__respondent2track` ADD  `gr2t_mailable` boolean not null default 1 AFTER  `gr2t_id_organization`;

-- PATCH: integrating appointments
ALTER TABLE gems__appointments ADD gap_source varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null
    default 'manual' AFTER gap_id_organization;
ALTER TABLE gems__appointments ADD gap_manual_edit boolean not null default 0
    AFTER gap_id_in_source;
UPDATE gems__appointments  SET gap_source = 'import' WHERE gap_id_in_source IS NOT NULL;
UPDATE gems__appointments  SET gap_manual_edit =
        CASE WHEN gap_id_in_source IS NULL OR gap_changed_by != gap_created_by THEN 1 ELSE 0 END;
ALTER TABLE gems__appointments CHANGE gap_id_in_source gap_id_in_source varchar(20) CHARACTER SET 'utf8'
        COLLATE 'utf8_general_ci' null default null;

ALTER TABLE gems__appointments DROP INDEX gap_id_in_source;
ALTER TABLE gems__appointments ADD UNIQUE INDEX (gap_id_in_source, gap_id_organization, gap_source);

ALTER TABLE `gems__surveys`
  DROP `gsu_staff`,
  DROP `gsu_id_user_field`,
  DROP `gsu_completion_field`,
  DROP `gsu_followup_field`;

-- GEMS VERSION: 55
-- PATCH: add new privilege
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.track-maintenance.trackperorg')
    WHERE grl_privileges LIKE '%pr.track-maintenance%' AND grl_privileges NOT LIKE '%pr.track-maintenance.trackperorg%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.survey-maintenance.answer-import')
    WHERE grl_privileges LIKE '%pr.track-maintenance%' AND
        grl_privileges NOT LIKE '%pr.survey-maintenance.answer-import%';

-- PATCH: add round code
ALTER TABLE gems__rounds ADD gro_code varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL AFTER gro_active;

-- PATCH: updates to survey_questions table
ALTER TABLE  `gems__survey_questions`
    CHANGE  `gsq_label`  `gsq_label` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    CHANGE  `gsq_description`  `gsq_description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
    CHANGE  `gsq_name`         `gsq_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
    CHANGE  `gsq_name_parent`  `gsq_name_parent` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;

-- PATCH: The number of reminders should not include the original mail
UPDATE gems__comm_jobs SET gcj_filter_max_reminders = gcj_filter_max_reminders - 1
    WHERE gcj_filter_mode = 'R' and gcj_filter_max_reminders > 1;

-- PATCH: Automaticall fill track fields
ALTER TABLE gems__track_fields ADD gtf_calculate_using varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gtf_field_values;

-- PATCH: Fix incorrect rights for no-login accounts due to error in level 48
update gems__roles set grl_privileges =  concat(left(grl_privileges,locate(',pr.track-maintenance.delete', grl_privileges)-1), right(grl_privileges, char_length(grl_privileges) - locate(',pr.track-maintenance.delete', grl_privileges)-27)) WHERE grl_privileges LIKE '%,pr.track-maintenance.delete%' AND grl_privileges NOT LIKE '%,pr.track-maintenance,%';
update gems__roles set grl_privileges =  concat(left(grl_privileges,locate(',pr.track-maintenance.trackperorg', grl_privileges)-1), right(grl_privileges, char_length(grl_privileges) - locate(',pr.track-maintenance.trackperorg', grl_privileges)-32)) WHERE grl_privileges LIKE '%,pr.track-maintenance.trackperorg%' AND grl_privileges NOT LIKE '%,pr.track-maintenance,%';
update gems__roles set grl_privileges =  concat(left(grl_privileges,locate(',pr.survey-maintenance.answer-import', grl_privileges)-1), right(grl_privileges, char_length(grl_privileges) - locate(',pr.survey-maintenance.answer-import', grl_privileges)-37)) WHERE grl_privileges LIKE '%,pr.survey-maintenance.answer-import%' AND grl_privileges NOT LIKE '%,pr.track-maintenance,%';