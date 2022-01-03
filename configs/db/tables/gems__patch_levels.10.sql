
CREATE TABLE if not exists gems__patch_levels (
      gpl_level   int unsigned not null unique,

      gpl_created timestamp not null default current_timestamp,

      PRIMARY KEY (gpl_level)
   )
   ENGINE=InnoDB
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- First level should be equal to Versions::getBuild()
-- this ensures new patches at this level will be run 
INSERT INTO gems__patch_levels (gpl_level, gpl_created)
   VALUES
   (68, CURRENT_TIMESTAMP);
