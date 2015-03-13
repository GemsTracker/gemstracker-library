
CREATE TABLE if not exists gems__agenda_procedures (
        gapr_id_procedure    bigint unsigned not null auto_increment,
        gapr_name            varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gapr_id_organization bigint unsigned null references gems__organizations (gor_id_organization),

        gapr_name_for_resp   varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gapr_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gapr_code            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gapr_active          boolean not null default 1,
        gapr_filter          boolean not null default 0,

        gapr_changed         timestamp not null default current_timestamp on update current_timestamp,
        gapr_changed_by      bigint unsigned not null,
        gapr_created         timestamp not null default '0000-00-00 00:00:00',
        gapr_created_by      bigint unsigned not null,

        PRIMARY KEY (gapr_id_procedure),
        INDEX (gapr_name)
    )
    ENGINE=InnoDB
    auto_increment = 4000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

