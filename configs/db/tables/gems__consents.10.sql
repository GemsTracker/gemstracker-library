
CREATE TABLE if not exists gems__consents (
      gco_description varchar(20) not null,
      gco_order smallint not null default 10,
      gco_code varchar(20) not null default 'do not use',

      gco_changed timestamp not null default current_timestamp on update current_timestamp,
      gco_changed_by bigint unsigned not null,
      gco_created timestamp not null,
      gco_created_by bigint unsigned not null,

      PRIMARY KEY (gco_description)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


INSERT INTO gems__consents 
    (gco_description, gco_order, gco_code, gco_changed, gco_changed_by, gco_created, gco_created_by) 
    VALUES
    ('Yes', 10, 'consent given', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('No', 20, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('Unknown', 30, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
