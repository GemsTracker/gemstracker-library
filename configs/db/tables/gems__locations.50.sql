
CREATE TABLE if not exists gems__locations (
        glo_id_location     bigint unsigned not null auto_increment,
        glo_name            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_id_organization bigint unsigned not null references gems__organizations (gor_id_organization),

        glo_match_to        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_code            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_url             varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_url_route       varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_address_1       varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_address_2       varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_zipcode         varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_city            varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_region          varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        glo_iso_country     char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NL',
        glo_phone_1         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_phone_2         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_phone_3         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- glo_phone_4         varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        glo_active          boolean not null default 1,

        glo_changed         timestamp not null default current_timestamp on update current_timestamp,
        glo_changed_by      bigint unsigned not null,
        glo_created         timestamp not null default '0000-00-00 00:00:00',
        glo_created_by      bigint unsigned not null,

        PRIMARY KEY (glo_id_location),
        INDEX (glo_name),
        INDEX (glo_id_organization),
        INDEX (glo_match_to)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 600
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
