
-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE if not exists gems__systemuser_setup (
        gsus_id_user				bigint unsigned not null references gems__staff (gsf_id_user),

        gsus_secret_key             varchar(400) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gsus_changed                timestamp not null default current_timestamp on update current_timestamp,
        gsus_changed_by             bigint unsigned not null,
        gsus_created                timestamp not null,
        gsus_created_by             bigint unsigned not null,

        PRIMARY KEY (gsus_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

