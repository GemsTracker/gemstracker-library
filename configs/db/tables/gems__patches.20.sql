
CREATE TABLE if not exists gems__patches (
      gpa_id_patch  int unsigned not null auto_increment,

      gpa_level     int unsigned not null default 0,
      gpa_location  varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
      gpa_name      varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
      gpa_order     int unsigned not null default 0,

      gpa_sql       text CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,

      gpa_executed  boolean not null default 0,
      gpa_completed boolean not null default 0,

      gpa_result    varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,

      gpa_changed  timestamp not null default current_timestamp,
      gpa_created  timestamp null,

      PRIMARY KEY (gpa_id_patch),
      UNIQUE KEY (gpa_level, gpa_location, gpa_name, gpa_order)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 1
   CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

