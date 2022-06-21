
CREATE TABLE if not exists gems__radius_config (
        grcfg_id                bigint(11) NOT NULL auto_increment,
        grcfg_id_organization   bigint(11) NOT NULL references gems__organizations (gor_id_organization),
        grcfg_ip                varchar(39) CHARACTER SET 'utf8mb4' collate utf8_unicode_ci default NULL,
        grcfg_port              int(5) default NULL,
        grcfg_secret            varchar(255) CHARACTER SET 'utf8mb4' collate utf8_unicode_ci default NULL,

        -- deprecated in 1.8.6  method was never used, now saved with password
        grcfg_encryption        varchar(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,
        -- end deprecated

        PRIMARY KEY (grcfg_id)
    )
ENGINE=MyISAM
DEFAULT CHARSET=utf8
COLLATE=utf8_unicode_ci;
