
CREATE TABLE if not exists gems__track_fields (
        gtf_id_field   bigint unsigned not null auto_increment,
        gtf_id_track   int unsigned not null references gems__tracks (gtr_id_track),

        gtf_id_order   int not null default 10,

        gtf_field_name        varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gtf_field_code        varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtf_field_description varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gtf_field_values      text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gtf_calculate_using   varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gtf_field_type        varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gtf_required   boolean not null default false,
        gtf_readonly   boolean not null default false,

        gtf_changed    timestamp not null default current_timestamp on update current_timestamp,
        gtf_changed_by bigint unsigned not null,
        gtf_created    timestamp not null,
        gtf_created_by bigint unsigned not null,

        PRIMARY KEY (gtf_id_field)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 60000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

