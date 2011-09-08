
CREATE TABLE if not exists gems__log_useractions (
      glua_id_action    bigint unsigned not null auto_increment,

      glua_to           bigint unsigned null references gems__respondents   (grs_id_user),
      glua_by           bigint unsigned not null references gems__staff         (gsf_id_user),
      glua_organization bigint unsigned not null references gems__organizations (gor_id_organization),
      glua_action       int unsigned    not null references gems__log_actions   (glac_id_action),
      glua_message      text null default null,
      glua_role         varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      glua_remote_ip    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      glua_created      timestamp not null default current_timestamp,

      PRIMARY KEY (glua_id_action)
   )
   ENGINE=InnoDB
   auto_increment = 100000
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
   
