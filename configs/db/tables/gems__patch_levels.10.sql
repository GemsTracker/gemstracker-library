
CREATE TABLE if not exists gems__patch_levels (
      gpl_level   int unsigned not null unique,

      gpl_created timestamp not null default current_timestamp,

      PRIMARY KEY (gpl_level)
   )
   ENGINE=InnoDB
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__patch_levels (gpl_level, gpl_created)
   VALUES
   (57, CURRENT_TIMESTAMP);
