
CREATE TABLE if not exists gems__respondent2track2field (
        gr2t2f_id_respondent_track  bigint unsigned not null references gems__respondent2track (gr2t_id_respondent_track),
        gr2t2f_id_field             bigint unsigned not null references gems__track_fields (gtf_id_field),

        gr2t2f_value                text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gr2t2f_value_manual         boolean not null default 0,

        gr2t2f_changed              timestamp not null default current_timestamp on update current_timestamp,
        gr2t2f_changed_by           bigint unsigned not null,
        gr2t2f_created              timestamp not null,
        gr2t2f_created_by           bigint unsigned not null,

        PRIMARY KEY(gr2t2f_id_respondent_track, gr2t2f_id_field)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

