
CREATE TABLE if not exists gems__groups (
      ggp_id_group bigint unsigned not null auto_increment,
      ggp_name varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      ggp_description varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      ggp_role varchar(150) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'respondent',
      -- The ggp_role value(s) determines someones roles as set in the bootstrap

      ggp_group_active boolean not null default 1,
      ggp_staff_members boolean not null default 0,
      ggp_respondent_members boolean not null default 1,
      ggp_allowed_ip_ranges text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

      ggp_changed timestamp not null default current_timestamp on update current_timestamp,
      ggp_changed_by bigint unsigned not null,
      ggp_created timestamp not null,
      ggp_created_by bigint unsigned not null,

      PRIMARY KEY(ggp_id_group)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- Default group
INSERT ignore INTO gems__groups
   (ggp_id_group, ggp_name, ggp_description, ggp_role, ggp_group_active, ggp_staff_members, ggp_respondent_members, ggp_changed_by, ggp_created, ggp_created_by)
   VALUES
   (800, 'Super Administrators', 'Super administrators with access to the whole site', 'super', 1, 1, 0, 0, current_timestamp, 0),
   (801, 'Local Admins', 'Local Administrators', 'admin', 1, 1, 0, 0, current_timestamp, 0),
   (802, 'Staff', 'Health care staff', 'staff', 1, 1, 0, 0, current_timestamp, 0),
   (803, 'Respondents', 'Respondents', 'respondent', 1, 0, 1, 0, current_timestamp, 0);
