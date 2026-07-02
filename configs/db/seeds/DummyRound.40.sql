INSERT INTO gems__rounds (gro_id_round, gro_id_track, gro_id_order, gro_id_survey, gro_survey_name, gro_round_description, gro_valid_after_id, gro_valid_for_id, gro_active, gro_changed_by, gro_created_by)
    VALUES (0, 0, 10, 0, 'Dummy for inserted surveys', 'Dummy for inserted surveys', null, null, 1, 1, 1);

UPDATE gems__rounds SET gro_id_round = 0 WHERE gro_id_track = 0;