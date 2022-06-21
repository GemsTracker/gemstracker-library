
CREATE TABLE if not exists gems__agenda_activities (
        gaa_id_activity     bigint unsigned not null auto_increment,
        gaa_name            varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gaa_id_organization bigint unsigned null references gems__organizations (gor_id_organization),

        gaa_name_for_resp   varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        gaa_match_to        varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        gaa_code            varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gaa_active          boolean not null default 1,
        gaa_filter          boolean not null default 0,

        gaa_changed         timestamp not null default current_timestamp on update current_timestamp,
        gaa_changed_by      bigint unsigned not null,
        gaa_created         timestamp not null default current_timestamp,
        gaa_created_by      bigint unsigned not null,

        PRIMARY KEY (gaa_id_activity),
        INDEX (gaa_name)
    )
    ENGINE=InnoDB
    auto_increment = 500
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

