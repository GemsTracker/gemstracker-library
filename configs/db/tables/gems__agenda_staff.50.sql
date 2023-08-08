
CREATE TABLE if not exists gems__agenda_staff (
        gas_id_staff        bigint unsigned not null auto_increment,
        gas_name            varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        gas_function        varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gas_id_organization bigint unsigned not null references gems__organizations (gor_id_organization),
        gas_id_user         bigint unsigned null references gems__staff (gsf_id_user),

        gas_match_to        varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gas_source          varchar(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null default 'manual',
        gas_id_in_source    varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,

        gas_active          boolean not null default 1,
        gas_filter          boolean not null default 0,

        gas_changed         timestamp not null default current_timestamp on update current_timestamp,
        gas_changed_by      bigint unsigned not null,
        gas_created         timestamp not null default current_timestamp,
        gas_created_by      bigint unsigned not null,

        PRIMARY KEY (gas_id_staff),
        INDEX (gas_name)
    )
    ENGINE=InnoDB
    auto_increment = 3000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

