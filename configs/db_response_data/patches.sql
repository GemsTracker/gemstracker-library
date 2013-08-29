-- GEMS VERSION: 53
-- PATCH: Change index to be unique
ALTER TABLE  `zsd`.`gemsdata__responses` DROP INDEX  `gdr_id_token` ,
ADD UNIQUE  `gdr_id_token` (  `gdr_id_token` ,  `gdr_answer_id` );