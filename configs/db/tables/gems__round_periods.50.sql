
CREATE TABLE if not exists gems__round_periods (
        grp_id_round bigint unsigned not null references gems__rounds (gro_id_round),

        grp_valid_after_id bigint unsigned null 
                references gems__rounds (gro_id_round),
        grp_valid_after_source varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'tok',
        grp_valid_after_field  varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gto_valid_from',
        grp_valid_after_unit   char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M',
        grp_valid_after_length int not null default 0,

        grp_valid_for_id bigint unsigned null 
                references gems__rounds (gro_id_round),
        grp_valid_for_source varchar(12) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'nul',
        grp_valid_for_field  varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        grp_valid_for_unit   char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'M',
        grp_valid_for_length int not null default 0,

        grp_changed timestamp not null default current_timestamp on update current_timestamp,
        grp_changed_by bigint unsigned not null,
        grp_created timestamp not null,
        grp_created_by bigint unsigned not null,

        PRIMARY KEY (grp_id_round)
    )
    ENGINE=InnoDB
    auto_increment = 40000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
    
INSERT INTO gems__round_periods 
    (grp_id_round, 

        grp_valid_after_id, grp_valid_after_source, grp_valid_after_field, grp_valid_after_unit, grp_valid_after_length,

        grp_valid_for_id, grp_valid_for_source, grp_valid_for_field, grp_valid_for_unit, grp_valid_for_length,

        grp_changed, grp_changed_by, grp_created, grp_created_by)
    SELECT r1.gro_id_round AS grp_id_round,

            r2.gro_id_round AS grp_valid_after_id,
            CASE 
                WHEN r2.gro_id_round IS NULL THEN 'rtr'
                WHEN r1.gro_used_date = 'A' THEN 'tok'
                WHEN r1.gro_used_date = 'C' THEN 'tok'
                WHEN r1.gro_used_date = 'F' AND (gsu_followup_field IS NULL OR gsu_followup_field = 'submitdate') THEN 'tok'
                ELSE 'ans'
            END as grp_valid_after_source, 
            CASE 
                WHEN r2.gro_id_round IS NULL THEN 'gr2t_start_date'
                WHEN r1.gro_used_date = 'A' THEN 'gto_valid_from'
                WHEN r1.gro_used_date = 'C' THEN 'gto_completion_time'
                WHEN r1.gro_used_date = 'F' AND (gsu_followup_field IS NULL OR gsu_followup_field = 'submitdate') THEN 'gto_completion_time'
                ELSE gsu_followup_field
            END as grp_valid_after_field, 
            coalesce(substring(r1.gro_valid_after, 1, 1), 'M') AS grp_valid_after_unit, 
            coalesce(convert(substring(r1.gro_valid_after, 2), signed), 0) AS grp_valid_after_length,

            r1.gro_id_round AS grp_valid_for_id,
            CASE 
                WHEN coalesce(convert(substring(r1.gro_valid_for, 2), signed), 0) = 0 THEN 'nul' 
                ELSE 'tok' 
            END AS grp_valid_for_source, 
            CASE 
                WHEN coalesce(convert(substring(r1.gro_valid_for, 2), signed), 0) = 0 THEN null 
                ELSE 'gto_valid_from' 
            END AS grp_valid_for_field, 
            coalesce(substring(r1.gro_valid_for, 1, 1), 'M') AS grp_valid_for_unit, 
            coalesce(convert(substring(r1.gro_valid_for, 2), signed), 0) AS grp_valid_for_length,

            r1.gro_changed, r1.gro_changed_by, r1.gro_created, r1.gro_created_by
        FROM gems__rounds AS r1
            INNER JOIN gems__tracks ON r1.gro_id_track = gtr_id_track
            LEFT JOIN (gems__rounds AS r2 
                INNER JOIN gems__surveys ON r2.gro_id_survey = gsu_id_survey)
                ON r1.gro_id_track = r2.gro_id_track AND r1.gro_id_order > r2.gro_id_order 
                    AND r2.gro_id_order = 
                        (SELECT MAX(r3.gro_id_order) FROM gems__rounds AS r3 WHERE r1.gro_id_track = r3.gro_id_track AND r1.gro_id_order > r3.gro_id_order)
        WHERE gtr_track_type = 'T' AND gtr_track_model = 'TrackModel'
        ORDER BY r1.gro_id_round;

INSERT INTO gems__round_periods 
    (grp_id_round, 

        grp_valid_after_id, grp_valid_after_source, grp_valid_after_field, grp_valid_after_unit, grp_valid_after_length,

        grp_valid_for_id, grp_valid_for_source, grp_valid_for_field, grp_valid_for_unit, grp_valid_for_length,
        grp_changed, grp_changed_by, grp_created, grp_created_by)
    SELECT r1.gro_id_round AS grp_id_round,

            r2.gro_id_round AS grp_valid_after_id,
            CASE 
                WHEN r2.gro_id_round IS NULL THEN 'rtr'
                WHEN r1.gro_used_date = 'A' THEN 'tok'
                WHEN r1.gro_used_date = 'C' THEN 'tok'
                WHEN r1.gro_used_date = 'F' AND (gsu_followup_field IS NULL OR gsu_followup_field = 'submitdate') THEN 'tok'
                ELSE 'ans'
            END as grp_valid_after_source, 
            CASE 
                WHEN r2.gro_id_round IS NULL THEN 'gr2t_start_date'
                WHEN r1.gro_used_date = 'A' THEN 'gto_valid_from'
                WHEN r1.gro_used_date = 'C' THEN 'gto_completion_time'
                WHEN r1.gro_used_date = 'F' AND (gsu_followup_field IS NULL OR gsu_followup_field = 'submitdate') THEN 'gto_completion_time'
                ELSE gsu_followup_field
            END as grp_valid_after_field, 
            coalesce(substring(r1.gro_valid_after, 1, 1), 'M') AS grp_valid_after_unit, 
            coalesce(convert(substring(r1.gro_valid_after, 2), signed), 0) AS grp_valid_after_length,

            r1.gro_id_round AS grp_valid_for_id,
            CASE 
                WHEN coalesce(convert(substring(r1.gro_valid_for, 2), signed), 0) = 0 THEN 'nul' 
                ELSE 'tok' 
            END AS grp_valid_for_source, 
            CASE 
                WHEN coalesce(convert(substring(r1.gro_valid_for, 2), signed), 0) = 0 THEN null 
                ELSE 'gto_valid_from' 
            END AS grp_valid_for_field, 
            coalesce(substring(r1.gro_valid_for, 1, 1), 'M') AS grp_valid_for_unit, 
            coalesce(convert(substring(r1.gro_valid_for, 2), signed), 0) AS grp_valid_for_length,

            r1.gro_changed, r1.gro_changed_by, r1.gro_created, r1.gro_created_by
        FROM gems__rounds AS r1
            INNER JOIN gems__tracks ON r1.gro_id_track = gtr_id_track
            LEFT JOIN (gems__rounds AS r2 
                INNER JOIN gems__surveys ON r2.gro_id_survey = gsu_id_survey)
                ON r1.gro_id_track = r2.gro_id_track AND r1.gro_used_date_order = r2.gro_id_order 
        WHERE gtr_track_type = 'T' AND gtr_track_model = 'NewTrackModel'
        ORDER BY r1.gro_id_round;
