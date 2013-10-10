
CREATE TABLE if not exists gems__agenda_procedures (
        gap_id_procedure    bigint unsigned not null auto_increment,
        gap_name            varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gap_id_organization bigint unsigned null references gems__organizations (gor_id_organization),

        gap_name_for_resp   varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gap_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gap_code            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gap_active          boolean not null default 1,

        gap_changed         timestamp not null default current_timestamp on update current_timestamp,
        gap_changed_by      bigint unsigned not null,
        gap_created         timestamp not null default '0000-00-00 00:00:00',
        gap_created_by      bigint unsigned not null,

        PRIMARY KEY (gap_id_procedure),
        INDEX (gap_name)
    )
    ENGINE=InnoDB
    auto_increment = 4000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

