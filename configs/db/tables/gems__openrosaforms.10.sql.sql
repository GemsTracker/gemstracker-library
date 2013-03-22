CREATE TABLE IF NOT EXISTS `gems__openrosaforms` (
  `gof_id` bigint(20) NOT NULL auto_increment,
  `gof_form_id` varchar(249) collate utf8_unicode_ci NOT NULL,
  `gof_form_version` varchar(249) collate utf8_unicode_ci NOT NULL,
  `gof_form_active` int(1) NOT NULL default '1',
  `gof_form_title` text collate utf8_unicode_ci NOT NULL,
  `gof_form_xml` varchar(64) collate utf8_unicode_ci NOT NULL,
  `gof_changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `gof_changed_by` bigint(20) NOT NULL,
  `gof_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `gof_createf_by` bigint(20) NOT NULL,
  PRIMARY KEY  (`gof_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;