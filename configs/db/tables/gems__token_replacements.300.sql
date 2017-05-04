
-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE if not exists gems__token_replacements (
        gtrp_id_token_new           varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtrp_id_token_old           varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gtrp_created                timestamp not null default CURRENT_TIMESTAMP,
        gtrp_created_by             bigint unsigned not null,

        PRIMARY KEY (gtrp_id_token_new),
        INDEX (gtrp_id_token_old)
    )
    ENGINE=InnoDB
    auto_increment = 30000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

