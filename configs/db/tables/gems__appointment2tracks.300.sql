

CREATE TABLE gems__appointment2tracks (
        ga2t_id_resp_track  bigint unsigned not null references gems__respondent2track (gr2t_id_respondent_track),
        ga2t_id_appointment bigint unsigned not null references gems__appointments (gap_id_appointment),

        ga2t_code           varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        ga2t_changed        timestamp not null default current_timestamp on update current_timestamp,
        ga2t_changed_by     bigint unsigned not null,
        ga2t_created        timestamp not null default '0000-00-00 00:00:00',
        ga2t_created_by     bigint unsigned not null,

        PRIMARY KEY (ga2t_id _respondent_track, ga2t_id_appointment)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
