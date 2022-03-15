
-- GEMS VERSION: 53
-- PATCH: Change index to be unique
ALTER TABLE  gemsdata__responses DROP INDEX gdr_id_token,
    ADD UNIQUE gdr_id_token (gdr_id_token, gdr_answer_id);

-- GEMS VERSION: 54
-- PATCH: Longer field name for answer id
ALTER TABLE  gemsdata__responses CHANGE gdr_answer_id
    gdr_answer_id varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null;

-- GEMS VERSION: 58
-- PATCH: Row numbers for answers
ALTER IGNORE TABLE gemsdata__responses DROP INDEX gdr_id_token;

ALTER TABLE  gemsdata__responses ADD
    gdr_answer_row bigint(20) unsigned NOT NULL default 1 AFTER gdr_answer_id;

ALTER IGNORE TABLE gemsdata__responses ADD UNIQUE INDEX (gdr_id_token, gdr_answer_id, gdr_answer_row);

-- GEMS VERSION: 67
-- PATCH: Add index to gems data
ALTER TABLE gemsdata__responses ADD INDEX gdr_changed (gdr_changed);

