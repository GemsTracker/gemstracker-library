
CREATE TABLE if not exists gems__agenda_diagnoses (
        gad_diagnosis_code  varchar(50) not null,
        gad_description     varchar(250) null default null,

        gad_coding_method   varchar(10) not null default 'DBC',
        gad_code            varchar(40) null default null,

        gad_source          varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'manual',
        gad_id_in_source    varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gad_active          boolean not null default 1,
        gad_filter          boolean not null default 0,

        gad_changed         timestamp not null default current_timestamp on update current_timestamp,
        gad_changed_by      bigint unsigned not null,
        gad_created         timestamp not null default '0000-00-00 00:00:00',
        gad_created_by      bigint unsigned not null,

        PRIMARY KEY (gad_diagnosis_code),
        INDEX (gad_description)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

