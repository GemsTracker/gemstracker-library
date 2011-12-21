CREATE TABLE IF NOT EXISTS `gems__radius_config` (
  `grcfg_id` bigint(11) NOT NULL auto_increment,
  `grcfg_id_organization` bigint(11) NOT NULL,
  `grcfg_ip` varchar(39) collate utf8_unicode_ci NOT NULL,
  `grcfg_port` int(5) default NULL,
  `grcfg_secret` varchar(32) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`grcfg_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
