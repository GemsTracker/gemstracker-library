

CREATE TABLE if not exists gems__log_respondent2track2field (
        glrtf_id                    bigint unsigned not null auto_increment,

        glrtf_id_respondent_track   bigint unsigned not null references gems__respondent2track (gr2t_id_respondent_track),
        glrtf_id_sub                varchar(8) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glrtf_id_field              bigint not null references gems__track_fields (gtf_id_field),

        glrtf_old_value             text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        glrtf_old_value_manual      boolean not null default 0,
        
        glrtf_new_value             text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        glrtf_new_value_manual      boolean not null default 0,
        
        glrtf_created               timestamp not null,
        glrtf_created_by            bigint unsigned not null,

        PRIMARY KEY (glrtf_id)
    )
    ENGINE=InnoDB
    auto_increment = 200000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
