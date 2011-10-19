
CREATE TABLE if not exists gems__mail_jobs (
        gmj_id_message bigint unsigned not null auto_increment,

        gmj_id_organization bigint unsigned not null
                references gems__organizations (gor_id_organization),

        gmj_id_user_as bigint unsigned not null
                references gems__staff (gsf_id_user),

        -- O Use organization from address
        -- S Use site from address
        -- U Use gmj_id_user_as from address
        gmj_from_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        -- M => multiple per respondent, one for each token
        -- O => One per respondent, mark all tokens as send
        -- U / A? => Send only one token, do not mark
        gmj_process_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        -- N => notmailed
        -- R => reminder
        gmj_filter_mode varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gmj_changed timestamp not null default current_timestamp on update current_timestamp,
        gmj_changed_by bigint unsigned not null,
        gmj_created timestamp not null default '0000-00-00 00:00:00',
        gmj_created_by bigint unsigned not null,

        PRIMARY KEY (gmj_id_message)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
