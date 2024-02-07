
CREATE TABLE if not exists gemsdata__responses (
        gdr_id_response     bigint(20)  unsigned NOT NULL auto_increment,
        gdr_id_token        varchar(9)  CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        gdr_answer_id       varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        gdr_answer_row      bigint(20)  unsigned NOT NULL default 1,

        gdr_response        text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gdr_changed         timestamp not null default current_timestamp on update current_timestamp,
        gdr_changed_by      bigint unsigned not null,
        gdr_created         timestamp not null default current_timestamp,
        gdr_created_by      bigint unsigned not null,

        PRIMARY KEY (gdr_id_response),
        UNIQUE KEY (gdr_id_token, gdr_answer_id, gdr_answer_row)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 100000000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

