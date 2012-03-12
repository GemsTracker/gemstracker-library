
CREATE TABLE IF NOT EXISTS `gems__sources` (
  `gso_id_source` int(10) unsigned NOT NULL auto_increment,
  `gso_source_name` varchar(40) NOT NULL,

  `gso_ls_url` varchar(255) NOT NULL,
  `gso_ls_class` varchar(60) NOT NULL default 'Gems_Source_LimeSurvey1m9Database',
  `gso_ls_adapter` varchar(20) default NULL,
  `gso_ls_dbhost` varchar(127) default NULL,
  `gso_ls_database` varchar(127) default NULL,
  `gso_ls_table_prefix` varchar(127) default NULL,
  `gso_ls_username` varchar(64) default NULL,
  `gso_ls_password` varchar(255) default NULL,
  `gso_ls_charset` varchar(8) NOT NULL,

  `gso_active` tinyint(1) NOT NULL default '1',

  `gso_status` varchar(20) default NULL,
  `gso_last_synch` timestamp NULL default NULL,

  `gso_changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `gso_changed_by` bigint(20) unsigned NOT NULL,
  `gso_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `gso_created_by` bigint(20) unsigned NOT NULL,

  PRIMARY KEY  (`gso_id_source`),
  UNIQUE KEY `gso_source_name` (`gso_source_name`),
  UNIQUE KEY `gso_ls_url` (`gso_ls_url`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8;