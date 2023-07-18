
CREATE TABLE if not exists gems__consents (
      gco_description varchar(20) not null,
      gco_order smallint not null default 10,
      gco_code varchar(20) not null default 'do not use',

      gco_changed timestamp not null default current_timestamp on update current_timestamp,
      gco_changed_by bigint unsigned not null,
      gco_created timestamp not null default current_timestamp,
      gco_created_by bigint unsigned not null,

      PRIMARY KEY (gco_description)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
