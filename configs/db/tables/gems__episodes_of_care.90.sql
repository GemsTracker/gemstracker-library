
CREATE TABLE if not exists gems__episodes_of_care (
        gec_episode_of_care_id      bigint unsigned not null auto_increment,
        gec_id_user                 bigint unsigned not null references gems__respondents (grs_id_user),
        gec_id_organization         bigint unsigned not null references gems__organizations (gor_id_organization),

        gec_source                  varchar(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null default 'manual',
        gec_id_in_source            varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,
        gec_manual_edit             boolean not null default 0,

        gec_status                  varchar(1) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null default 'A',
        -- one off A => active, C => Cancelled, E => Error, F => Finished, O => Onhold, P => Planned, W => Waitlist
        -- see https://www.hl7.org/fhir/episodeofcare.html

        gec_startdate               date not null,
        gec_enddate                 date null,

        gec_id_attended_by          bigint unsigned null references gems__agenda_staff (gas_id_staff),

        gec_subject                 varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,
        gec_comment                 text CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,

        gec_diagnosis               varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,
        gec_diagnosis_data          text CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,
        gec_extra_data              text CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,

        gec_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gec_changed_by              bigint unsigned not null,
        gec_created                 timestamp not null,
        gec_created_by              bigint unsigned not null,

        PRIMARY KEY (gec_episode_of_care_id)
    )
    ENGINE=InnoDB
    auto_increment = 400000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

