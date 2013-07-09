
CREATE TABLE if not exists gems__respondent2appointment (
    gr2a_id_appointment    bigint unsigned not null auto_increment,
    gr2a_id_user           bigint unsigned not null references gems__respondents (grs_id_user),
    gr2a_id_organization   bigint unsigned not null references gems__organizations (gor_id_organization),

    gr2a_id_in_source      varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

    gr2a_type              varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'A',
    -- one off A => Ambulatory, E => Emergency, F => Field, H => Home, I => Inpatient, S => Short stay, V => Virtual
    -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

    gr2a_active            boolean not null default 1,
    -- gr2a_status            varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'S',
    -- replaced by active
    -- one off A => Aborted, S => Scheduled / compled / trevies
    -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

    gr2a_admission_time    datetime not null,
    gr2a_discharge_time    datetime null,

    gr2a_attended_by       varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_referred_by       varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_activity          varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_procedures        varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_location          varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

    gr2a_subject           varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
    gr2a_comment           TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

    gr2a_changed           timestamp not null default current_timestamp on update current_timestamp,
    gr2a_changed_by        bigint unsigned not null,
    gr2a_created           timestamp not null,
    gr2a_created_by        bigint unsigned not null,

    PRIMARY KEY (gr2a_id_appointment),
    INDEX (gr2a_id_in_source, gr2a_id_organization),
    INDEX (gr2a_id_user, gr2a_id_organization)
    -- ,INDEX (gr2a_appointment)
)
ENGINE=InnoDB
auto_increment = 2000000
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
