
CREATE TABLE if not exists gems__agenda_diagnoses (
        gad_diagnosis_code  VARCHAR(50) not null,
        gad_description     VARCHAR(250) null default null,

        gad_coding_method   VARCHAR(10) not null default 'DBC',
        gad_code            VARCHAR(40) null default null,

        gad_active          tinyint(1) NOT NULL DEFAULT '1',

        gad_changed         timestamp not null default current_timestamp on update current_timestamp,
        gad_changed_by      bigint unsigned not null,
        gad_created         timestamp not null default '0000-00-00 00:00:00',
        gad_created_by      bigint unsigned not null,

        PRIMARY KEY (gad_diagnosis_code),
        INDEX (gad_description)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

