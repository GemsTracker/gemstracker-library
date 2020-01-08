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
-- PATCH: Add gtr_organizations to tracks
ALTER TABLE `gems__tracks` ADD `gtr_organizations` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gtr_track_type` ;
UPDATE gems__tracks
    SET  `gtr_organizations` = (SELECT CONCAT('|', CONVERT(GROUP_CONCAT(gor_id_organization SEPARATOR '|'), CHAR), '|') as orgs FROM gems__organizations WHERE gor_active=1)
    WHERE gtr_active = 1;

-- PATCH: Gewijzigd track model
ALTER TABLE `gems__tracks` ADD `gtr_track_model` VARCHAR(64) NOT NULL DEFAULT 'TrackModel' AFTER `gtr_track_type`;
ALTER TABLE `gems__rounds` ADD `gro_used_date_order` INT(4) NULL AFTER `gro_used_date`,
    ADD `gro_used_date_field` VARCHAR(16) NULL AFTER `gro_used_date_order`;

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

-- PATCH: Fix projects that lived on trunk and have problems with multilanguage templates
ALTER TABLE `gems__comm_template_translations`
    CHANGE `gctt_subject` `gctt_subject` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
    CHANGE `gctt_body` `gctt_body` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- GEMS VERSION: 56
-- PATCH: Add organizations to Rounds
ALTER TABLE gems__rounds ADD gro_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gro_valid_for_length;

-- PATCH: Encrypt password fields
ALTER TABLE gems__mail_servers ADD
      gms_encryption varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gms_password;

ALTER TABLE gems__sources ADD
    gso_encryption varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gso_ls_password;

ALTER TABLE gems__radius_config CHANGE grcfg_secret
    grcfg_secret varchar(255) CHARACTER SET 'utf8' COLLATE utf8_unicode_ci default NULL;

ALTER TABLE gems__radius_config ADD
    grcfg_encryption varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER grcfg_secret;

-- PATCH: Add templates privilege to superadmin role (super)
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.templates')
    WHERE grl_privileges NOT LIKE '%pr.templates%' AND grl_name = 'super';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.plan.fields')
    WHERE grl_privileges NOT LIKE '%pr.plan.fields%' AND grl_privileges LIKE '%pr.plan.compliance%' ;

-- PATCH: Add description inclusion option
ALTER TABLE gems__track_fields ADD
    gtf_to_track_info       boolean not null default true AFTER gtf_field_type;
ALTER TABLE gems__track_appointments ADD
    gtap_to_track_info      boolean not null default true AFTER gtap_field_description;

UPDATE gems__track_appointments SET gtap_to_track_info = 0;

-- PATCH: New agenda automation system
UPDATE gems__roles
    SET grl_privileges =
        CONCAT(grl_privileges,',pr.agenda-filters,pr.agenda-filters.create,pr.agenda-filters.delete,pr.agenda-filters.edit')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.agenda-filters%';

ALTER TABLE gems__track_appointments ADD
    gtap_filter_id          bigint unsigned null references gems__appointment_filters (gaf_id) AFTER gtap_readonly;
ALTER TABLE gems__track_appointments ADD
    gtap_after_next         boolean not null default 1 AFTER gtap_filter_id;
ALTER TABLE gems__track_appointments ADD
    gtap_create_track       boolean not null default 0 AFTER gtap_filter_id;
ALTER TABLE gems__track_appointments ADD
    gtap_create_wait_days   bigint signed not null default 182 AFTER gtap_create_track;

-- PATCH: Field update event
ALTER TABLE gems__tracks ADD
    gtr_fieldupdate_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gtr_completed_event;

-- GEMS VERSION: 57
-- PATCH: Uniqueness in appointment fields
ALTER TABLE gems__track_appointments ADD
    gtap_uniqueness tinyint unsigned not null default 0 AFTER gtap_after_next;

-- PATCH: Organization locations field larger
ALTER TABLE `gems__organizations` CHANGE
    `gor_location` `gor_location` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- PATCH: Insertable surveys are defined at survey level
ALTER IGNORE TABLE gems__surveys DROP COLUMN gsu_survey_table;
ALTER IGNORE TABLE gems__surveys DROP COLUMN gsu_token_table;
ALTER IGNORE TABLE gems__surveys DROP COLUMN gsu_staff;
ALTER IGNORE TABLE gems__surveys DROP COLUMN gsu_id_user_field;
ALTER IGNORE TABLE gems__surveys DROP COLUMN gsu_completion_field;
ALTER IGNORE TABLE gems__surveys DROP COLUMN gsu_followup_field;

ALTER TABLE gems__surveys
    ADD gsu_insertable boolean not null default 0
    AFTER gsu_id_primary_group;
ALTER TABLE gems__surveys
    ADD gsu_valid_for_unit char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M'
    AFTER gsu_insertable;
ALTER TABLE gems__surveys
    ADD gsu_valid_for_length int not null default 6
    AFTER gsu_valid_for_unit;
ALTER TABLE gems__surveys
    ADD gsu_insert_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gsu_valid_for_length;

UPDATE gems__surveys
    SET gsu_insertable = 1,
        gsu_insert_organizations = (SELECT MAX(gtr_organizations)
            FROM gems__rounds INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
            WHERE gtr_track_type = 'S')
    WHERE gsu_id_survey IN
        (SELECT gro_id_survey
            FROM gems__rounds INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
            WHERE gtr_track_type = 'S');

-- Move SingleSurveyEngine tracks to AnyStepEngine
UPDATE gems__rounds
    SET gro_valid_after_id     = null,
        gro_valid_after_source = 'rtr',
        gro_valid_after_field  = 'gr2t_start_date',
        gro_valid_after_unit   = 'M',
        gro_valid_after_length = 0,
        gro_valid_for_id     = null,
        gro_valid_for_source = 'rtr',
        gro_valid_for_field  = 'gr2t_start_date',
        gro_valid_for_unit   = 'M',
        gro_valid_for_length = 6
    WHERE gro_id_track IN (SELECT gtr_id_track FROM gems__tracks WHERE gtr_track_type = 'S');

UPDATE gems__tracks
    SET gtr_track_class = 'AnyStepEngine'
    WHERE gtr_track_class = 'SingleSurveyEngine' AND
        gtr_id_track IN (SELECT gr2t_id_track FROM gems__respondent2track);

UPDATE gems__tracks
    SET gtr_active = 0,
        gtr_date_until = CURRENT_DATE
    WHERE gtr_track_class = 'SingleSurveyEngine' AND
        gtr_id_track NOT IN (SELECT gr2t_id_track FROM gems__respondent2track);

ALTER IGNORE TABLE gems__tracks DROP gtr_track_model;

INSERT ignore INTO gems__rounds (gro_id_track, gro_id_order, gro_id_survey, gro_survey_name, gro_round_description,
    gro_valid_after_id, gro_valid_for_id, gro_active, gro_changed, gro_changed_by, gro_created, gro_created_by)
    VALUES
    (0, 10, 0, 'Dummy for inserted surveys', 'Dummy for inserted surveys',
        0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

UPDATE ignore gems__rounds SET gro_id_round = 0 WHERE gro_survey_name = 'Dummy for inserted surveys';

DELETE FROM gems__rounds WHERE gro_id_round != 0 AND gro_survey_name = 'Dummy for inserted surveys';

-- PATCH: New rights for insert action
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track.insert')
    WHERE grl_privileges LIKE '%pr.survey.create,%' AND grl_privileges NOT LIKE '%,pr.track.insert%';

-- PATCH: Agenda items can be set to not importable
ALTER TABLE gems__agenda_activities ADD
        gaa_filter boolean not null default 0 AFTER gaa_active;

ALTER TABLE gems__agenda_procedures ADD
        gapr_filter boolean not null default 0 AFTER gapr_active;

ALTER TABLE gems__agenda_staff ADD
        gas_filter boolean not null default 0 AFTER gas_active;

ALTER TABLE gems__locations ADD
        glo_filter boolean not null default 0 AFTER glo_active;

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.agenda-activity.cleanup')
    WHERE grl_privileges LIKE '%pr.agenda-activity.edit,%' AND grl_privileges NOT LIKE '%,pr.agenda-activity.cleanup%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.agenda-procedure.cleanup')
    WHERE grl_privileges LIKE '%pr.agenda-procedure.edit,%' AND grl_privileges NOT LIKE '%,pr.agenda-procedure.cleanup%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.locations.cleanup')
    WHERE grl_privileges LIKE '%pr.locations.edit,%' AND grl_privileges NOT LIKE '%,pr.locations.cleanup%';

-- PATCH: Respondent log
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent-log')
    WHERE grl_privileges LIKE '%pr.log%' AND grl_privileges NOT LIKE '%,pr.respondent-log%';

-- PATCH: New rights for undeleting
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent.undelete')
    WHERE (grl_name = 'admin' OR grl_privileges LIKE '%pr.respondent.show-deleted%')
        AND grl_privileges NOT LIKE '%,pr.respondent.undelete%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track.undelete')
    WHERE grl_name = 'admin' AND grl_privileges NOT LIKE '%,pr.track.undelete%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.token.undelete')
    WHERE grl_name = 'admin' AND grl_privileges NOT LIKE '%,pr.token.undelete%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.staff-log')
    WHERE grl_name = 'admin' AND grl_privileges NOT LIKE '%,pr.staff-log%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent.select-on-track')
    WHERE grl_name = 'admin' AND grl_privileges NOT LIKE '%,pr.respondent.select-on-track%';

-- PATCH: Advanced appointment integration
ALTER TABLE gems__track_fields
    ADD gtf_track_info_label boolean not null default false AFTER gtf_to_track_info;

UPDATE gems__track_fields SET gtf_track_info_label = true WHERE gtf_field_type IN ('date', 'datetime');

ALTER TABLE gems__track_appointments
    ADD gtap_track_info_label boolean not null default false AFTER gtap_to_track_info;

UPDATE gems__track_appointments SET gtap_track_info_label = true;

ALTER TABLE gems__track_appointments
    ADD gtap_min_diff_length int not null default 1 AFTER gtap_after_next;

ALTER TABLE gems__track_appointments
    ADD gtap_min_diff_unit char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'D'
    AFTER gtap_min_diff_length;

ALTER TABLE gems__track_appointments
    ADD gtap_max_diff_length int not null default 0 AFTER gtap_min_diff_unit;

ALTER TABLE gems__track_appointments
    ADD gtap_max_diff_unit char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'D'
    AFTER gtap_max_diff_length;

ALTER TABLE gems__track_appointments
    ADD gtap_max_diff_exists boolean not null default 0 AFTER gtap_min_diff_unit;

UPDATE gems__track_appointments SET gtap_min_diff_length = -1 WHERE gtap_after_next = 0;
UPDATE gems__track_appointments SET gtap_min_diff_unit = 'S', gtap_max_diff_unit = 'S';

-- PATCH: Wrong fieldname
ALTER TABLE gems__openrosaforms CHANGE gof_createf_by gof_created_by BIGINT( 20 ) NOT NULL;

UPDATE gems__patches
    SET gpa_completed = 1
    WHERE gpa_sql = "ALTER TABLE gems__openrosaforms CHANGE gof_createf_by gof_created_by BIGINT( 20 ) NOT NULL";

-- PATCH: Respondent relations can take surveys
ALTER TABLE gems__rounds ADD
        gro_id_relationfield BIGINT( 20 ) NULL DEFAULT NULL
        AFTER gro_id_survey;

ALTER TABLE gems__tokens ADD
        gto_id_relationfield BIGINT( 20 ) NULL DEFAULT NULL
        AFTER gto_round_description;

ALTER TABLE gems__tokens ADD
        gto_id_relation  bigint(20) NULL DEFAULT NULL
        AFTER gto_round_description;

-- GEMS VERSION: 58
-- PATCH: New rights for respondent comm log
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent-commlog')
    WHERE grl_privileges LIKE '%pr.respondent%'
        AND grl_name = 'staff'
        AND grl_privileges NOT LIKE '%,pr.respondent-commlog%';

-- PATCH: New right for deactivation and reactivation
UPDATE gems__roles
    SET grl_privileges = REPLACE(grl_privileges, ',pr.staff.delete', ',pr.staff.deactivate,pr.staff.reactivate')
    WHERE grl_privileges LIKE '%,pr.staff.delete%';

-- PATCH: Speedup of queries
ALTER IGNORE TABLE gems__surveys ADD INDEX (gsu_id_primary_group);
ALTER IGNORE TABLE gems__surveys DROP INDEX gsu_id_primary_group_2; -- Undo double creation

ALTER IGNORE TABLE gems__tokens ADD INDEX (gto_round_order);
ALTER IGNORE TABLE gems__tokens DROP INDEX gto_round_order_2; -- Undo double creation

ALTER IGNORE TABLE gems__tokens ADD INDEX (gto_created);
ALTER IGNORE TABLE gems__tokens DROP INDEX gto_created_2; -- Undo double creation

ALTER IGNORE TABLE gems__appointments ADD INDEX (gap_id_attended_by);
ALTER IGNORE TABLE gems__appointments DROP INDEX gap_id_attended_by_2;  -- Undo double creation

ALTER IGNORE TABLE gems__appointments ADD INDEX (gap_id_referred_by);
ALTER IGNORE TABLE gems__appointments DROP INDEX gap_id_referred_by_2;  -- Undo double creation

ALTER IGNORE TABLE gems__appointments ADD INDEX (gap_id_activity);
ALTER IGNORE TABLE gems__appointments DROP INDEX gap_id_activity_2;  -- Undo double creation

ALTER IGNORE TABLE gems__appointments ADD INDEX (gap_id_procedure);
ALTER IGNORE TABLE gems__appointments DROP INDEX gap_id_procedure_2;  -- Undo double creation

ALTER IGNORE TABLE gems__appointments ADD INDEX (gap_id_location);
ALTER IGNORE TABLE gems__appointments DROP INDEX gap_id_location_2;  -- Undo double creation

ALTER IGNORE TABLE gems__respondent2org ADD INDEX (gr2o_consent);
ALTER IGNORE TABLE gems__respondent2org DROP INDEX gr2o_consent_2;  -- Undo double creation

ALTER IGNORE TABLE gems__rounds ADD INDEX (gro_id_order);
ALTER IGNORE TABLE gems__rounds DROP INDEX gro_id_order_2;  -- Undo double creation

ALTER IGNORE TABLE gems__rounds ADD INDEX (gro_id_survey);
ALTER IGNORE TABLE gems__rounds DROP INDEX gro_id_survey_2;  -- Undo double creation

ALTER IGNORE TABLE gems__track_fields ADD INDEX (gtf_id_track);
ALTER IGNORE TABLE gems__track_fields DROP INDEX gtf_id_track_2;  -- Undo double creation

ALTER IGNORE TABLE gems__track_fields ADD INDEX (gtf_id_order);
ALTER IGNORE TABLE gems__track_fields DROP INDEX gtf_id_order_2;  -- Undo double creation

-- PATCH: Log file access
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.log.files,pr.log.files.download')
    WHERE grl_name = 'super'
        AND grl_privileges NOT LIKE '%,pr.log.files%';

-- PATCH: New rights and fields for track im- & export
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track-maintenance.export')
    WHERE grl_name = 'super'
        AND grl_privileges NOT LIKE '%,pr.track-maintenance.export%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track-maintenance.import')
    WHERE grl_name = 'super'
        AND grl_privileges NOT LIKE '%,pr.track-maintenance.import%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track-maintenance.merge')
    WHERE grl_name = 'super'
        AND grl_privileges NOT LIKE '%,pr.track-maintenance.merge%';

ALTER TABLE gems__surveys
    ADD gsu_export_code varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gsu_code;

-- PATCH: New rights and fields repsondent level track checks
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track.check')
    WHERE grl_privileges NOT LIKE '%,pr.track.check%' AND grl_privileges LIKE '%,pr.track-maintenance.check%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.track.answers')
    WHERE grl_privileges NOT LIKE '%,pr.track.answers%' AND grl_privileges LIKE '%,pr.survey-maintenance.check%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.token.answers')
    WHERE grl_privileges NOT LIKE '%,pr.token.answers%' AND grl_privileges LIKE '%,pr.survey-maintenance.check%';

-- PATCH: Log answered tokens
INSERT ignore INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('file-import.answers-import',          1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('project-information.maintenance',     1, 1, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('token.answered',                      1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('token.data-changed',                  1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.check-all-answers',             1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.check-all-tracks',              1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.check-token-answers',           1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.check-track',                   1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.check-track-answers',           1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.recalc-all-fields',             1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track.recalc-fields',                 1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track-maintenance.export',            1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
		('track-maintenance.import',            1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

-- PATCH: Mail monitor staff patch
ALTER TABLE gems__staff
	ADD gsf_mail_watcher boolean not null default 0 AFTER gsf_logout_on_survey;

-- PATCH: Updates of roles to 2016
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent.select-on-track')
    WHERE grl_name = 'staff' AND grl_privileges NOT LIKE '%,pr.respondent.select-on-track%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.export,pr.export-html,')
    WHERE grl_name IN ('admin', 'researcher') AND grl_privileges NOT LIKE '%,pr.export%';
UPDATE gems__roles SET grl_privileges = 'pr.log,pr.log.files,pr.log.files.download,pr.log.maintenance,
    ,pr.log.maintenance.edit,pr.mail.log,
    ,pr.option.edit,pr.option.password,
    ,pr.respondent.show-deleted,pr.respondent.who,pr.respondent-commlog,pr.respondent-log,
    ,pr.staff,pr.staff.see.all,pr.staff-log'
    WHERE grl_name = 'security' AND (grl_privileges = '' OR grl_privileges IS NULL);
UPDATE gems__roles SET grl_parents = null,
        grl_privileges = 'pr.contact.bugs,pr.contact.gems,pr.contact.support,
    ,pr.cron.job,
    ,pr.export,
    ,pr.islogin,
    ,pr.plan.consent,pr.plan.consent.excel,
	,pr.project-information.changelog,pr.contact,pr.export,pr.islogin,
    ,pr.option.password,pr.option.edit,pr.organization-switch,
	,pr.plan,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.respondent,pr.plan.summary,pr.plan.token'
    WHERE grl_name = 'researcher' and grl_parents = '801' and grl_changed = grl_created AND grl_changed_by = 1;

-- GEMS VERSION: 59
-- PATCH: Add gto_icon_file so individual tokens can have icons too
ALTER TABLE gems__tokens
    ADD gto_icon_file varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gto_round_order;

-- PATCH: Allow manual ordering of communication jobs
ALTER TABLE `gems__comm_jobs`
    ADD
        `gcj_id_order` INT NOT NULL DEFAULT '10'
    AFTER `gcj_id_job`;

UPDATE `gems__comm_jobs` AS t4
    JOIN (
        SELECT @rownr:=@rownr+10 AS gcj_id_order, t1.gcj_id_job
            FROM (
                SELECT gcj_id_job
                FROM gems__comm_jobs
                WHERE gcj_active = 1
                ORDER BY CASE WHEN gcj_id_survey IS NULL THEN 1 ELSE 0 END,
                    CASE WHEN gcj_round_description IS NULL THEN 1 ELSE 0 END,
                    CASE WHEN gcj_id_track IS NULL THEN 1 ELSE 0 END,
                    CASE WHEN gcj_id_organization IS NULL THEN 1 ELSE 0 END
                ) AS t1, (SELECT @rownr:=0) AS t2
    ) AS t3 ON t4.gcj_id_job = t3.gcj_id_job
SET t4.gcj_id_order = t3.gcj_id_order;

-- GEMS VERSION: 60
-- PATCH: Respondent change events at organisation level
ALTER TABLE gems__organizations ADD
    gor_resp_change_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gor_style;

-- PATCH: Cleanup of old fields
ALTER TABLE gems__tracks DROP INDEX gtr_track_type;

ALTER TABLE gems__tracks DROP gtr_track_type;

-- PATCH: Add before field update event
ALTER TABLE gems__tracks ADD
    gtr_beforefieldupdate_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gtr_track_class;

-- PATCH: Add correct token right
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.token.correct')
    WHERE grl_privileges NOT LIKE '%,pr.token.correct%' AND grl_privileges LIKE '%,pr.token.delete%';

-- PATCH: Add change respondent change organization right
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent.change-org')
    WHERE grl_privileges NOT LIKE '%,pr.respondent.change-org%' AND grl_name = 'admin';

INSERT ignore INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('respondent.change-organization', 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

-- PATCH: Allow longer results in token table

ALTER TABLE gems__tokens CHANGE gto_result gto_result varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- PATCH: Set logging of heavy actions to true
UPDATE gems__log_setup
    SET gls_on_change = 1
    WHERE gls_name LIKE '%recalc%' OR
        gls_name LIKE '%eactivate%' OR
        gls_name LIKE '%check%' OR
        gls_name LIKE '%synchronize%' OR
        gls_name LIKE '%patch%' OR
        gls_name LIKE '%run%';

-- PATCH: Set roles per group
ALTER TABLE gems__groups ADD
    ggp_may_set_groups varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null AFTER ggp_role;

ALTER TABLE gems__groups ADD
    ggp_default_group bigint unsigned null AFTER ggp_may_set_groups;

UPDATE gems__groups INNER JOIN gems__roles ON ggp_role = grl_name
    SET ggp_role = grl_id_role;

UPDATE gems__groups INNER JOIN
    (SELECT GROUP_CONCAT(gp.ggp_id_group SEPARATOR ',') as groups, rp.grl_id_role as role_id
        FROM gems__roles AS rp INNER JOIN
            gems__roles AS rc ON rp.grl_id_role = rc.grl_id_role OR rp.grl_parents LIKE CONCAT('%', rc.grl_id_role, '%')
                INNER JOIN
            gems__groups AS gp ON gp.ggp_role = rc.grl_id_role
        GROUP BY rp.grl_id_role
        ) AS rg
        ON ggp_role = rg.role_id
    SET ggp_may_set_groups = rg.groups
    WHERE ggp_may_set_groups IS NULL AND
        ggp_role IN (SELECT grl_id_role FROM gems__roles WHERE grl_privileges LIKE '%pr.staff.edit%');

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.group.switch')
    WHERE grl_privileges LIKE '%,pr.group%'
        AND grl_privileges NOT LIKE '%,pr.group.switch%';

-- PATCH: Add mask and respondent settings to groups
ALTER TABLE gems__groups ADD
    ggp_mask_settings text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER ggp_allowed_ip_ranges;

ALTER TABLE gems__groups ADD
    ggp_respondent_browse varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER ggp_allowed_ip_ranges;

ALTER TABLE gems__groups ADD
    ggp_respondent_edit varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER ggp_respondent_browse;

ALTER TABLE gems__groups ADD
    ggp_respondent_show varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER ggp_respondent_edit;

-- PATCH: Add respondent settings to organizations

ALTER TABLE gems__organizations ADD
    gor_respondent_edit varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER gor_signature;

ALTER TABLE gems__organizations ADD
    gor_respondent_show varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER gor_respondent_edit;

ALTER TABLE gems__organizations ADD
    gor_token_ask varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER gor_respondent_show;

UPDATE gems__organizations
    SET gor_respondent_edit = ''
    WHERE gor_respondent_edit IS NULL;

UPDATE gems__organizations
    SET gor_respondent_show = ''
    WHERE gor_respondent_show IS NULL;

UPDATE gems__organizations
    SET gor_token_ask = 'Gems\\Screens\\Token\\Ask\\ProjectDefaultAsk'
    WHERE gor_token_ask IS NULL;

-- PATCH: Activate job title as default staff element
ALTER TABLE gems__staff ADD gsf_job_title varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' after gsf_gender;

-- PATCH: Add default to fields
ALTER TABLE gems__track_fields ADD
    gtf_field_default varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null
    AFTER gtf_field_values;

-- PATCH: Add field for filtering mail jobs on target
ALTER TABLE gems__comm_jobs ADD gcj_target TINYINT(1) NOT NULL DEFAULT '0' AFTER `gcj_filter_max_reminders`;

-- PATCH: Add communication template for Radius accounts
INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (null, 'Linked account created', 'staff', 'linkedAccountCreated', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
INSERT INTO gems__comm_template_translations (gctt_id_template, gctt_lang, gctt_subject, gctt_body)
    VALUES
    ((select gct_id_template from gems__comm_templates where gct_code='linkedAccountCreated'), 'en', 'New account created', 'A new account has been created for you for the [b]{organization}[/b] website [b]{project}[/b].
To log in with your organization account {login_name} please click on this link:\r\n{login_url}'),
    ((select gct_id_template from gems__comm_templates where gct_code='linkedAccountCreated'), 'nl', 'Nieuw account aangemaakt', 'Er is voor u een nieuw account aangemaakt voor de [b]{organization}[/b] website [b]{project}[/b].
Om in te loggen met uw organisatie account {login_name} klikt u op onderstaande link:\r\n{login_url}');

-- GEMS VERSION: 61
-- PATCH: Save survey questions/answers hash
ALTER TABLE gems__surveys ADD gsu_hash CHAR(32) NULL DEFAULT NULL AFTER `gsu_export_code`;

-- PATCH: Make the password field larger and make the password reset key field fixed size
ALTER TABLE `gems__user_passwords`
    CHANGE `gup_password` `gup_password` varchar(255) COLLATE 'utf8_general_ci' NULL AFTER `gup_id_user`,
    CHANGE `gup_reset_key` `gup_reset_key` char(64) COLLATE 'utf8_general_ci' NULL AFTER `gup_password`;

-- PATCH: Move date source answer submitdate to token gto_completion_time
UPDATE `gems__rounds` SET gro_valid_after_source = 'tok', gro_valid_after_field = 'gto_completion_time' where gro_valid_after_source = 'ans' AND gro_valid_after_field = 'submitdate';
UPDATE `gems__rounds` SET gro_valid_for_source = 'tok', gro_valid_for_field = 'gto_completion_time' where gro_valid_for_source = 'ans' AND gro_valid_for_field = 'submitdate';

-- GEMS VERSION: 62
-- PATCH: Allow field overviews
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.plan.fields')
    WHERE grl_privileges LIKE '%pr.plan.summary%' AND grl_privileges NOT LIKE '%,pr.plan.fields%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.plan.fields.export')
    WHERE grl_privileges LIKE '%pr.plan.summary.export%' AND grl_privileges NOT LIKE '%,pr.plan.fields.export%';

-- PATCH: Update empty group interface settings
UPDATE gems__groups SET ggp_respondent_browse = 'Gems\\Screens\\Respondent\\Browse\\ProjectDefaultBrowse'
    WHERE ggp_respondent_browse IS NULL;

UPDATE gems__groups SET ggp_respondent_edit = 'Gems\\Screens\\Respondent\\Edit\\ProjectDefaultEdit'
    WHERE ggp_respondent_edit IS NULL;

UPDATE gems__groups SET ggp_respondent_show = 'Gems\\Screens\\Respondent\\Show\\GemsProjectDefaultShow'
    WHERE ggp_respondent_show IS NULL;

--PATCH: Change create track from boolean to int
ALTER TABLE  `gems__track_appointments`
    CHANGE  `gtap_create_track`  `gtap_create_track` INT( 1 ) NOT NULL DEFAULT  '0';

-- PATCH: Add care episodes to appointments
ALTER TABLE  gems__appointments
    ADD gap_id_episode bigint unsigned null AFTER gap_id_organization;

-- PATCH: Introducing conditions for rounds
ALTER TABLE gems__rounds
    ADD gro_condition bigint unsigned null AFTER gro_valid_for_length;

-- PATCH: Introducing two factor authentication
ALTER TABLE gems__user_logins
    ADD gul_two_factor_key varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER gul_can_login;

ALTER TABLE gems__user_logins
    ADD gul_enable_2factor boolean not null default 1
    AFTER gul_two_factor_key;

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.option.2factor')
    WHERE grl_privileges NOT LIKE '%,pr.option.2factor%' AND grl_name = 'super';

ALTER TABLE gems__groups
    ADD ggp_no_2factor_ip_ranges text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null
    AFTER ggp_allowed_ip_ranges;

ALTER TABLE gems__groups
    ADD ggp_2factor_set tinyint not null default 50
    AFTER ggp_no_2factor_ip_ranges;

ALTER TABLE gems__groups
    ADD ggp_2factor_not_set tinyint not null default 0
    AFTER ggp_2factor_set;

UPDATE gems__groups SET ggp_no_2factor_ip_ranges = '127.0.0.1|::1' WHERE ggp_no_2factor_ip_ranges IS NULL;

UPDATE gems__groups
    SET ggp_no_2factor_ip_ranges = ggp_allowed_ip_ranges,
        ggp_2factor_set = 50,
        ggp_2factor_not_set = 99,
        ggp_allowed_ip_ranges = NULL
    WHERE ggp_allowed_ip_ranges IS NOT NULL;

-- PATCH: Clean up old staff password columns
ALTER TABLE gems__staff
    DROP COLUMN gsf_password,
    DROP COLUMN gsf_failed_logins,
    DROP COLUMN gsf_last_failed;

ALTER TABLE gems__staff
    DROP COLUMN gsf_reset_key,
    DROP COLUMN gsf_reset_req;

UPDATE gems__user_logins SET gul_user_class = 'StaffUser' WHERE gul_user_class = 'OldStaffUser';

-- PATCH: Add respondent organisation email and fill with existing data from respondents
ALTER TABLE `gems__respondent2org`
	ADD `gr2o_email` varchar(100) COLLATE 'utf8_general_ci' NULL AFTER `gr2o_id_user`;

UPDATE gems__respondent2org
	INNER JOIN gems__respondents ON grs_id_user = gr2o_id_user
	SET gr2o_email = grs_email;

-- PATCH: Add memory to gems__token_attempts
ALTER TABLE gems__token_attempts
    ADD gta_activated boolean null default 0 AFTER gta_datetime;

-- GEMS VERSION: 63
-- PATCH: Extend structure of Episodes
ALTER TABLE gems__episodes_of_care
        ADD gec_diagnosis_data text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
        AFTER gec_diagnosis;

ALTER TABLE gems__episodes_of_care
        ADD gec_extra_data text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
        AFTER gec_diagnosis_data;

-- PATCH: Add diagnosis to appoointments
ALTER TABLE gems__appointments
        ADD gap_diagnosis_code varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null
        AFTER gap_id_location;

-- PATCH: Add source to Agenda staff
ALTER TABLE gems__agenda_staff
    ADD gas_source varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'manual'
    AFTER gas_match_to;

ALTER TABLE gems__agenda_staff
    ADD gas_id_in_source varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
    AFTER gas_source;

-- PATCH: Updates rights to see raw data for Episodes of Care
UPDATE gems__roles
    SET grl_privileges = CONCAT(grl_privileges, ',pr.episodes.rawdata')
    WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.episodes.rawdata%';

-- GEMS VERSION: 64
-- PATCH: 186 - add organization mailwatcher
ALTER TABLE gems__organizations
    ADD gor_mail_watcher boolean not null default 1 after gor_contact_email;

-- PATCH: add continue later templates
INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (null, 'Continue later', 'token', 'continue', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
INSERT INTO gems__comm_template_translations (gctt_id_template, gctt_lang, gctt_subject, gctt_body)
    VALUES
    ((select gct_id_template from gems__comm_templates where gct_code='continue'), 'en', 'Continue later', 'Dear {greeting},\n\nClick on [url={token_url}]this link[/url] to continue filling out surveys or go to [url]{site_ask_url}[/url] and enter this token: [b]{token}[/b]\n\n{organization_signature}'),
    ((select gct_id_template from gems__comm_templates where gct_code='continue'), 'nl', 'Later doorgaan', 'Beste {greeting},\n\nKlik op [url={token_url}]deze link[/url] om verder te gaan met invullen van vragenlijsten of ga naar [url]{site_ask_url}[/url] en voer dit kenmerk in: [b]{token}[/b]\n\n{organization_signature}');

-- PATCH: New fundamental reception code 'moved'
INSERT ignore INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
        ('moved', 'Moved to new survey', 0, 1, 0, 0, 0, 1, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

-- PATCH: Add organization check
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.organization.check-org') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.organization.check-org%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.organization.check-all') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.organization.check-all%';

-- PATCH: Add conditions to interface
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.conditions,')
    WHERE grl_privileges LIKE '%,pr.track-maintenance,%' AND grl_privileges NOT LIKE '%,pr.conditions,%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.conditions.edit,pr.conditions.create,')
    WHERE grl_privileges LIKE '%,pr.track-maintenance.edit%' AND grl_privileges NOT LIKE '%,pr.conditions.edit,pr.conditions.create%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.conditions.delete,')
    WHERE grl_privileges LIKE '%,pr.track-maintenance.delete%' AND grl_privileges NOT LIKE '%,pr.conditions.delete%';

-- PATCH: Add organization participation screen
ALTER TABLE gems__organizations ADD
    gor_respondent_subscribe varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default ''
    AFTER gor_respondent_show;
ALTER TABLE gems__organizations ADD
    gor_respondent_unsubscribe varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default ''
    AFTER gor_respondent_subscribe;

-- GEMS VERSION: 65
-- PATCH: Add port to sources
ALTER TABLE gems__sources ADD
        gso_ls_dbport mediumint default NULL
        AFTER gso_ls_database;

ALTER TABLE gems__locations CHANGE
        glo_name glo_name varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- PATCH: Add to address settings to mail jobs
ALTER TABLE gems__comm_jobs ADD
        gcj_to_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'A'
        AFTER gcj_from_fixed;

ALTER TABLE gems__comm_jobs ADD
        gcj_fallback_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'O'
        AFTER gcj_to_method;

ALTER TABLE gems__comm_jobs ADD
        gcj_fallback_fixed varchar(254) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null
        AFTER gcj_fallback_method;

-- PATCH: Enable logging of all mail execution
INSERT IGNORE INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('comm-job.cron-lock',                  1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT IGNORE INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('comm-job.execute',                    1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT IGNORE INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('comm-job.execute-all',                1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT IGNORE INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('cron.index',                          1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

UPDATE gems__log_setup
    SET gls_when_no_user = 1, gls_on_change = 1
    WHERE gls_name IN ('comm-job.cron-lock', 'comm-job.execute', 'comm-job.execute-all', 'cron.index');

-- PATCH: Allow embedded user login through user
ALTER TABLE gems__staff ADD
    gsf_is_embedded boolean not null default 0
    AFTER gsf_phone_1;

-- PATCH: Allow relations to be set to not mailable
ALTER TABLE  `gems__respondent_relations` ADD  `grr_mailable` boolean not null default 1 AFTER  `grr_email`;

-- PATCH: Save survey warnings and languages
ALTER TABLE `gems__surveys`
    ADD `gsu_survey_languages` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
    AFTER `gsu_survey_description`;
ALTER TABLE `gems__surveys`
    ADD `gsu_survey_warnings` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
    AFTER `gsu_status`;

-- PATCH: System users
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.systemuser')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.systemuser%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.systemuser.create')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.systemuser.create%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.systemuser.deactivate')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.systemuser.deactivate%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.systemuser.reactivate')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.systemuser.reactivate%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.systemuser.edit')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.systemuser.edit%';

UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.staff.switch-user')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.staff.switch-user%';


-- GEMS VERSION: 66
-- PATCH: add change consent right to super
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.respondent.change-consent')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.respondent.change-consent%';

-- PATCH: Add cron job to respondent mail log
ALTER TABLE `gems__log_respondent_communications`
    ADD `grco_id_job` bigint(20) unsigned NULL AFTER `grco_id_message`;

-- PATCH: Add check appointment right
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.appointments.check')
    WHERE grl_name IN ('super')
        AND grl_privileges NOT LIKE '%,pr.appointments.check%';
