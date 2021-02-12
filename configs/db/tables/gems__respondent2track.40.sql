
CREATE TABLE if not exists gems__respondent2track (
        gr2t_id_respondent_track    bigint unsigned not null auto_increment,

        gr2t_id_user                bigint unsigned not null references gems__respondents (grs_id_user),
        gr2t_id_track               int unsigned not null references gems__tracks (gtr_id_track),

        gr2t_track_info             varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gr2t_start_date             datetime null,
        gr2t_end_date               datetime null,
        gr2t_end_date_manual        boolean not null default 0,

        gr2t_id_organization        bigint unsigned not null references gems__organizations (gor_id_organization),

        gr2t_mailable               tinyint not null default 100 references gems__mail_codes (gmc_id),
        gr2t_active                 boolean not null default 1,
        gr2t_count                  int unsigned not null default 0,
        gr2t_completed              int unsigned not null default 0,

        gr2t_reception_code         varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
                                    references gems__reception_codes (grc_id_reception_code),
        gr2t_comment                varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gr2t_changed                timestamp not null default current_timestamp on update current_timestamp,
        gr2t_changed_by             bigint unsigned not null,
        gr2t_created                timestamp not null,
        gr2t_created_by             bigint unsigned not null,

        PRIMARY KEY (gr2t_id_respondent_track),
        INDEX (gr2t_id_track),
        INDEX (gr2t_id_user),
        INDEX (gr2t_start_date),
        INDEX (gr2t_id_organization),
        INDEX (gr2t_created_by)
    )
    ENGINE=InnoDB
    auto_increment = 100000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
