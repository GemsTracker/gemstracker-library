

CREATE TABLE gems__token2appointments {
        gt2a_id_token           varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null
                                references gems__tokens (gto_id_token),
        gt2a_id_appointment     bigint unsigned not null references gems__appointments (gap_id_appointment),

        gt2a_created            timestamp not null default current_timestamp ,
        gt2a_created_by         bigint unsigned not null,

        PRIMARY KEY (gt2a_id_token),
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
