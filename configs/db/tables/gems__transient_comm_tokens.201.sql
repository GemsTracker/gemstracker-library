CREATE TABLE if not exists gems__transient_comm_tokens (
        gtct_id_token               varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        gtct_id_respondent          bigint unsigned NOT NULL,
        gtct_id_organization        bigint unsigned NOT NULL,
        gtct_id_track               bigint unsigned NOT NULL,
        gtct_id_survey              bigint unsigned NOT NULL,
        PRIMARY KEY (gtct_id_token),
        INDEX (gtct_id_respondent),
        INDEX (gtct_id_organization),
        INDEX (gtct_id_track),
        INDEX (gtct_id_survey)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
