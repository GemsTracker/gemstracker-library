
CREATE TABLE if not exists gems__respondent2appointment (
    gr2a_id_appointment    bigint unsigned not null auto_increment,
    gr2a_id_user           bigint unsigned not null references gems__respondents (grs_id_user),
    gr2a_id_organization   bigint unsigned not null references gems__organizations (gor_id_organization),

    gr2a_appointment       datetime not null,
    gr2a_whole_day         boolean not null default 0,
    gr2a_until             datetime null,
    gr2a_active            boolean not null default 1,

    gr2a_subject           varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_location          varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_comment           TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

    gr2a_changed           timestamp not null default current_timestamp on update current_timestamp,
    gr2a_changed_by        bigint unsigned not null,
    gr2a_created           timestamp not null,
    gr2a_created_by        bigint unsigned not null,

    PRIMARY KEY (gr2a_id_appointment),
    INDEX (gr2a_id_user, gr2a_id_organization),
    INDEX (gr2a_appointment)
)
ENGINE=InnoDB
auto_increment = 2000000
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
