

CREATE TABLE if not exists gems__agenda_statuscodes (
        gasc_code            varchar(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gasc_name            varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gasc_is_active       boolean not null default 0,
        gasc_in_interface    boolean not null default 1,

        gasc_changed         timestamp not null default current_timestamp on update current_timestamp,
        gasc_changed_by      bigint unsigned not null,
        gasc_created         timestamp not null default '0000-00-00 00:00:00',
        gasc_created_by      bigint unsigned not null,

        PRIMARY KEY (gasc_code),
        INDEX (gasc_name)
    )
    ENGINE=InnoDB
    auto_increment = 500
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT ignore INTO gems__agenda_statuscodes
    (gasc_code, gasc_name, gasc_is_active, gasc_in_interface,
        gasc_changed, gasc_changed_by, gasc_created, gasc_created_by)
    VALUES
    ('AC', 'Active',    1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('CO', 'Completed', 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('AB', 'Aborted',   0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('CA', 'Cancelled', 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

