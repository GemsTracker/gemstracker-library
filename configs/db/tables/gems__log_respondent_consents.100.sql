
-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE if not exists gems__log_respondent_consents (
        glrc_id                 bigint unsigned not null auto_increment,

        glrc_id_user            bigint unsigned not null references gems__respondents (grs_id_user),
        glrc_id_organization    bigint unsigned not null references gems__organizations (gor_id_organization),

        glrc_consent_field      varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' 
                                not null default 'gr2o_consent',
        glrc_old_consent        varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null
                                references gems__consents (gco_description),
        glrc_new_consent        varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null 
                                references gems__consents (gco_description),

        glrc_created            timestamp not null,
        glrc_created_by         bigint unsigned not null,

        PRIMARY KEY (glrc_id)
    )
    ENGINE=InnoDB
    auto_increment = 2000000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

