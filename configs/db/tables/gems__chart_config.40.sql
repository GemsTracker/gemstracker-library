CREATE TABLE IF NOT EXISTS `gems__chart_config` (
  `gcc_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gcc_tid` bigint(20) NULL,
  `gcc_rid` bigint(20) NULL,
  `gcc_sid` bigint(20) NULL,
  `gcc_code` varchar(16) COLLATE utf8_unicode_ci NULL,
  `gcc_config` text COLLATE utf8_unicode_ci NULL,
  `gcc_description` varchar(64) COLLATE utf8_unicode_ci NULL,

  `gcc_changed`          timestamp not null default current_timestamp on update current_timestamp,
  `gcc_changed_by`       bigint unsigned not null,
  `gcc_created`          timestamp not null,
  `gcc_created_by`       bigint unsigned not null,

  PRIMARY KEY (`gcc_id`),
  INDEX (gcc_tid),
  INDEX (gcc_rid), 
  INDEX (gcc_sid),
  INDEX (gcc_code)

) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=101;