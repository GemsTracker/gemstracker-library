
-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE if not exists gems__systemuser_setup (
        gsus_id_user				bigint unsigned not null references gems__staff (gsf_id_user),

        gsus_secret_key             varchar(400) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gsus_create_user            tinyint(4) unsigned NOT NULL DEFAULT '0',
        gsus_authentication         varchar(200) COLLATE 'utf8_general_ci' NULL default 'Gems\\User\\Embed\\Auth\\HourKeySha256',
        gsus_deferred_user_loader   varchar(200) COLLATE 'utf8_general_ci' NULL default 'Gems\\User\\Embed\\DeferredUserLoader\\StaffUser',

        -- This group can contain negative values for other options than groups
        gsus_deferred_user_group    bigint(20) signed NULL default null,
        gsus_redirect               varchar(200) COLLATE 'utf8_general_ci' NULL default 'Gems\\User\\Embed\\Redirect\\RespondentShowPage',
        gsus_deferred_user_layout   varchar(200) COLLATE 'utf8_general_ci' NULL default null,

        gsus_changed                timestamp not null default current_timestamp on update current_timestamp,
        gsus_changed_by             bigint unsigned not null,
        gsus_created                timestamp not null,
        gsus_created_by             bigint unsigned not null,

        PRIMARY KEY (gsus_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

