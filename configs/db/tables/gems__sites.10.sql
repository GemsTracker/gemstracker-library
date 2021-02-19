

CREATE TABLE if not exists gems__sites (
    gsi_id                      bigint unsigned not null auto_increment,

    gsi_url                     varchar(256) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
    gsi_order                   int not null default 100,

    gsi_select_organizations    boolean not null default 0,
    gsi_organizations           varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default '||',

    gsi_style                   varchar(15)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems',
    gsi_style_fixed             boolean not null default 0,

    gsi_iso_lang                char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'en',
    
    gsi_active                  boolean not null default 1,
    gsi_blocked                 boolean not null default 0,

    gsi_changed                 timestamp not null default current_timestamp on update current_timestamp,
    gsi_changed_by              bigint unsigned not null,
    gsi_created                 timestamp not null,
    gsi_created_by              bigint unsigned not null,

    PRIMARY KEY (gsi_id),
    UNIQUE KEY (gsi_url),
    INDEX (gsi_order)        
)
ENGINE=InnoDB
auto_increment = 200
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
