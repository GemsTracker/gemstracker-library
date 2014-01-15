
CREATE TABLE if not exists gems__track_appointments (
        gtap_id_app_field       bigint unsigned not null auto_increment,
        gtap_id_track           int unsigned not null references gems__tracks (gtr_id_track),

        gtap_id_order           int not null default 10,

        gtap_field_name         varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtap_field_code         varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtap_field_description  varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gtap_required           boolean not null default false,
        gtap_readonly           boolean not null default false,

        gtap_changed            timestamp not null default current_timestamp on update current_timestamp,
        gtap_changed_by         bigint unsigned not null,
        gtap_created            timestamp not null,
        gtap_created_by         bigint unsigned not null,

        PRIMARY KEY (gtap_id_field),
        INDEX (gtap_id_track),
        INDEX (gtap_id_order)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 80000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

