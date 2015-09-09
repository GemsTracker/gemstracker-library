
CREATE TABLE if not exists gems__log_respondent_communications (
        grco_id_action    bigint unsigned not null auto_increment,

        grco_id_to        bigint unsigned not null references gems__respondents (grs_id_user),
        grco_id_by        bigint unsigned null default 0 references gems__staff (gsf_id_user),
        grco_organization bigint unsigned not null references gems__organizations (gor_id_organization),

        grco_id_token     varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null references gems__tokens (gto_id_token),

        grco_method       varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        grco_topic        varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        grco_address      varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        grco_sender       varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        grco_comments     varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        grco_id_message   bigint unsigned null references gems__comm_templates (gct_id_template),

        grco_changed      timestamp not null default current_timestamp,
        grco_changed_by   bigint unsigned not null,
        grco_created      timestamp not null,
        grco_created_by   bigint unsigned not null,

        PRIMARY KEY (grco_id_action)
    )
    ENGINE=InnoDB
    auto_increment = 200000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

