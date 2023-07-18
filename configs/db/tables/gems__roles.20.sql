
CREATE TABLE if not exists gems__roles (
      grl_id_role bigint unsigned not null auto_increment,
      grl_name varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,
      grl_description varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,

      grl_parents text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
      -- The grl_parents is a comma-separated list of parents for this role

      grl_privileges text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
      -- The grl_privilege is a comma-separated list of privileges for this role

      grl_changed timestamp not null default current_timestamp on update current_timestamp,
      grl_changed_by bigint unsigned not null,
      grl_created timestamp not null default current_timestamp,
      grl_created_by bigint unsigned not null,

      PRIMARY KEY(grl_id_role)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
