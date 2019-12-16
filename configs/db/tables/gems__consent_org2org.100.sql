
-- Created by Matijs de Jong <mjong@magnafacta.nl>
CREATE TABLE if not exists gems__consent_org2org (
        gco2o_id_user               bigint unsigned not null references gems__respondents (grs_id_user),
        gco2o_organization_from     bigint unsigned not null references gems__organizations (gor_id_organization),
        gco2o_organization_to       bigint unsigned not null references gems__organizations (gor_id_organization),

        gco2o_consent               varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'Unknown'
                                    references gems__consents (gco_description),

        gco2o_changed               timestamp not null default current_timestamp on update current_timestamp,
        gco2o_changed_by            bigint unsigned not null,
        gco2o_created               timestamp not null,
        gco2o_created_by            bigint unsigned not null,

        PRIMARY KEY (gco2o_id_user, gco2o_organization_from, gco2o_organization_to)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

