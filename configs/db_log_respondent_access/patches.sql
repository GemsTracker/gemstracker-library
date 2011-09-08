
/*******************
 * GEMS PATCH FILE *
 *******************/


-- GEMS VERSION: 27
-- PATCH: a - Moving plog to gems

ALTER TABLE plog__actions           RENAME TO gems__log_actions;
ALTER TABLE plog__staff2respondents RENAME TO gems__log_staff2respondents;
ALTER TABLE plog__useractions       RENAME TO gems__log_useractions;

-- PATCH: b - Rename plog fields

ALTER TABLE gems__log_actions
   CHANGE COLUMN pac_id_action glac_id_action int unsigned not null auto_increment,
   CHANGE COLUMN pac_name      glac_name      varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null unique,
   CHANGE COLUMN pac_change    glac_change    boolean not null default 1,
   CHANGE COLUMN pac_created   glac_created   timestamp not null default current_timestamp;

ALTER TABLE gems__log_staff2respondents
   CHANGE COLUMN ps2r_to           gls2r_to           bigint unsigned not null,
   CHANGE COLUMN ps2r_by           gls2r_by           bigint unsigned not null,
   CHANGE COLUMN ps2r_organization gls2r_organization bigint unsigned not null,
   CHANGE COLUMN ps2r_changed      gls2r_changed      timestamp not null not null default current_timestamp on update current_timestamp,
   CHANGE COLUMN ps2r_created      gls2r_created      timestamp not null;

ALTER TABLE gems__log_useractions 
   CHANGE COLUMN pua_id_action    glua_id_action    bigint unsigned not null auto_increment,
   CHANGE COLUMN pua_to           glua_to           bigint unsigned not null,
   CHANGE COLUMN pua_by           glua_by           bigint unsigned not null,
   CHANGE COLUMN pua_organization glua_organization bigint unsigned not null,
   CHANGE COLUMN pua_action       glua_action       int unsigned    not null,
   CHANGE COLUMN pua_role         glua_role         varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
   CHANGE COLUMN pua_created      glua_created      timestamp not null default current_timestamp;

-- GEMS VERSION: 35
-- PATCH: Log an optional message with an entry
ALTER TABLE  gems__log_useractions 
    ADD glua_message TEXT NULL DEFAULT NULL AFTER glua_action,
    CHANGE COLUMN glua_to glua_to BIGINT( 20 ) UNSIGNED NULL;

ALTER TABLE  gems__log_actions 
    ADD glac_log INT( 1 ) NOT NULL DEFAULT  1 AFTER glac_change,
    CHANGE  glac_name  glac_name VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- PATCH: Drop table we no longer need
DROP TABLE  gems__log_staff2respondents

-- GEMS VERSION: 36
-- PATCH: Add IP address column
ALTER TABLE `gems__log_useractions` ADD `glua_remote_ip` VARCHAR(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL AFTER `glua_role`;
