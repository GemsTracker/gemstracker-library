
CREATE TABLE if not exists gems__appointments (
        gap_id_appointment      bigint unsigned not null auto_increment,
        gap_id_user             bigint unsigned not null references gems__respondents (grs_id_user),
        gap_id_organization     bigint unsigned not null references gems__organizations (gor_id_organization),

        gap_source              varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'manual',
        gap_id_in_source        varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gap_manual_edit         boolean not null default 0,

        gap_code                varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'A',
        -- one off A => Ambulatory, E => Emergency, F => Field, H => Home, I => Inpatient, S => Short stay, V => Virtual
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        -- moodCode http://wiki.ihe.net/index.php?title=1.3.6.1.4.1.19376.1.5.3.1.4.14
        -- one of  PRMS Scheduled, ARQ requested but no date, EVN has occurred
        gap_status              varchar(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'AC'
                                references gems__agenda_statuscodes (gasc_code),
        -- one off AB => Aborted, AC => active, CA => Cancelled, CO => completed
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        gap_admission_time      datetime not null,
        gap_discharge_time      datetime null,

        gap_id_attended_by      bigint unsigned null references gems__agenda_staff (gas_id_staff),
        gap_id_referred_by      bigint unsigned null references gems__agenda_staff (gas_id_staff),
        gap_id_activity         bigint unsigned null references gems__agenda_activities (gaa_id_activity),
        gap_id_procedure        bigint unsigned null references gems__agenda_procedures (gapr_id_procedure),
        gap_id_location         bigint unsigned null references gems__locations (glo_id_location),

        gap_subject             varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gap_comment             TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gap_changed             timestamp not null default current_timestamp on update current_timestamp,
        gap_changed_by          bigint unsigned not null,
        gap_created             timestamp not null,
        gap_created_by          bigint unsigned not null,

        PRIMARY KEY (gap_id_appointment),
        UNIQUE INDEX (gap_id_in_source, gap_id_organization, gap_source),
        INDEX (gap_id_user, gap_id_organization),
        INDEX (gap_admission_time),
        INDEX (gap_code),
        INDEX (gap_status)
    )
    ENGINE=InnoDB
    auto_increment = 2000000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
