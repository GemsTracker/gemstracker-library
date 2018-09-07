CREATE TABLE groups (
	gid int(11) NOT NULL,
	sid int(11) NOT NULL DEFAULT 0,
	group_name varchar(100) NOT NULL DEFAULT '',
	group_order int(11) NOT NULL DEFAULT 0,
	description text,
	language varchar(20) NOT NULL DEFAULT 'en',
	randomization_group varchar(20) NOT NULL DEFAULT '',
	grelevance text,

 	PRIMARY KEY (gid,language)
 );

CREATE TABLE questions (
	qid int(11) NOT NULL,
	parent_qid int(11) NOT NULL DEFAULT 0,
	sid int(11) NOT NULL DEFAULT 0,
	gid int(11) NOT NULL DEFAULT 0,
	type varchar(1) NOT NULL DEFAULT 'T',
	title varchar(20) NOT NULL DEFAULT '',
	question text NOT NULL,
	preg text,
	help text,
	other varchar(1) NOT NULL DEFAULT 'N',
	mandatory varchar(1) DEFAULT NULL,
	question_order int(11) NOT NULL,
	language varchar(20) NOT NULL DEFAULT 'en',
	scale_id int(11) NOT NULL DEFAULT 0,
	same_default int(11) NOT NULL DEFAULT 0,
	relevance text,

	PRIMARY KEY (qid,language)
);

CREATE TABLE answers (
	qid int(11) NOT NULL DEFAULT 0,
        code varchar(5) DEFAULT NULL,
	answer text DEFAULT NULL,
        sortorder int(11) NOT NULL DEFAULT 0,
	assessment_value int(11) NOT NULL DEFAULT 0,
	language varchar(20) NOT NULL DEFAULT 'en',
        scale_id int(11) NOT NULL DEFAULT 0,

	PRIMARY KEY (qid, code, language, scale_id)
);

CREATE TABLE question_attributes (
	qaid int(11) NOT NULL,
	qid int(11) NOT NULL DEFAULT 0,
	attribute varchar(50) DEFAULT NULL,
	value text,
	language varchar(20) DEFAULT NULL,

	PRIMARY KEY (qaid, language)
);

CREATE TABLE surveys (
	sid int(11) NOT NULL,
	owner_id int(11) NOT NULL,
	admin varchar(50) DEFAULT NULL,
	active varchar(1) NOT NULL DEFAULT 'N',
	expires datetime DEFAULT NULL,
	startdate datetime DEFAULT NULL,
	adminemail varchar(254) DEFAULT NULL,
	anonymized varchar(1) NOT NULL DEFAULT 'N',
	faxto varchar(20) DEFAULT NULL,
	format varchar(1) DEFAULT NULL,
	savetimings varchar(1) NOT NULL DEFAULT 'N',
	template varchar(100) DEFAULT 'default',
	language varchar(50) DEFAULT NULL,
	additional_languages varchar(255) DEFAULT NULL,
	datestamp varchar(1) NOT NULL DEFAULT 'N',
	usecookie varchar(1) NOT NULL DEFAULT 'N',
	allowregister varchar(1) NOT NULL DEFAULT 'N',
	allowsave varchar(1) NOT NULL DEFAULT 'Y',
	autonumber_start int(11) NOT NULL DEFAULT 0,
	autoredirect varchar(1) NOT NULL DEFAULT 'N',
	allowprev varchar(1) NOT NULL DEFAULT 'N',
	printanswers varchar(1) NOT NULL DEFAULT 'N',
	ipaddr varchar(1) NOT NULL DEFAULT 'N',
	refurl varchar(1) NOT NULL DEFAULT 'N',
	datecreated date DEFAULT NULL,
	publicstatistics varchar(1) NOT NULL DEFAULT 'N',
	publicgraphs varchar(1) NOT NULL DEFAULT 'N',
	listpublic varchar(1) NOT NULL DEFAULT 'N',
	htmlemail varchar(1) NOT NULL DEFAULT 'N',
	sendconfirmation varchar(1) NOT NULL DEFAULT 'Y',
	tokenanswerspersistence varchar(1) NOT NULL DEFAULT 'N',
	assessments varchar(1) NOT NULL DEFAULT 'N',
	usecaptcha varchar(1) NOT NULL DEFAULT 'N',
	usetokens varchar(1) NOT NULL DEFAULT 'N',
	bounce_email varchar(254) DEFAULT NULL,
	attributedescriptions text,
	emailresponseto text,
	emailnotificationto text,
	tokenlength int(11) NOT NULL DEFAULT '15',
	showxquestions varchar(1) DEFAULT 'Y',
	showgroupinfo varchar(1) DEFAULT 'B',
	shownoanswer varchar(1) DEFAULT 'Y',
	showqnumcode varchar(1) DEFAULT 'X',
	bouncetime int(11) DEFAULT NULL,
	bounceprocessing varchar(1) DEFAULT 'N',
	bounceaccounttype varchar(4) DEFAULT NULL,
	bounceaccounthost varchar(200) DEFAULT NULL,
	bounceaccountpass varchar(100) DEFAULT NULL,
	bounceaccountencryption varchar(3) DEFAULT NULL,
	bounceaccountuser varchar(200) DEFAULT NULL,
	showwelcome varchar(1) DEFAULT 'Y',
	showprogress varchar(1) DEFAULT 'Y',
	questionindex int(11) NOT NULL DEFAULT 0,
	navigationdelay int(11) NOT NULL DEFAULT 0,
	nokeyboard varchar(1) DEFAULT 'N',
	alloweditaftercompletion varchar(1) DEFAULT 'N',
	googleanalyticsstyle varchar(1) DEFAULT NULL,
	googleanalyticsapikey varchar(25) DEFAULT NULL,
	PRIMARY KEY (sid)
);

CREATE TABLE `surveys_languagesettings` (
  `surveyls_survey_id` int(11) NOT NULL,
  `surveyls_language` varchar(45) NOT NULL DEFAULT 'en',
  `surveyls_title` varchar(200) NOT NULL,
  `surveyls_description` text,
  `surveyls_welcometext` text,
  `surveyls_endtext` text,
  `surveyls_url` text,
  `surveyls_urldescription` varchar(255) DEFAULT NULL,
  `surveyls_email_invite_subj` varchar(255) DEFAULT NULL,
  `surveyls_email_invite` text,
  `surveyls_email_remind_subj` varchar(255) DEFAULT NULL,
  `surveyls_email_remind` text,
  `surveyls_email_register_subj` varchar(255) DEFAULT NULL,
  `surveyls_email_register` text,
  `surveyls_email_confirm_subj` varchar(255) DEFAULT NULL,
  `surveyls_email_confirm` text,
  `surveyls_dateformat` int(11) NOT NULL DEFAULT '1',
  `surveyls_attributecaptions` text,
  `email_admin_notification_subj` varchar(255) DEFAULT NULL,
  `email_admin_notification` text,
  `email_admin_responses_subj` varchar(255) DEFAULT NULL,
  `email_admin_responses` text,
  `surveyls_numberformat` int(11) NOT NULL DEFAULT '0',
  `attachments` text,
  PRIMARY KEY (`surveyls_survey_id`,`surveyls_language`)
);

CREATE TABLE survey_1 (
	`id` integer NOT NULL,
	`submitdate` datetime DEFAULT NULL,
	`lastpage` int(11) DEFAULT NULL,
	`startlanguage` varchar(20) NOT NULL,
	`token` varchar(36) DEFAULT NULL,
	`datestamp` datetime NOT NULL,
	`startdate` datetime NOT NULL,
	`ipaddr` text,
	`1X1X1` datetime DEFAULT NULL,
	`1X1X2` date DEFAULT NULL,
	`1X1X3` datetime DEFAULT NULL,
	`1X1X4` datetime DEFAULT NULL,
        `1X1X9` text DEFAULT NULL,
        `1X1X10` text DEFAULT NULL,
        `1X1X11` text DEFAULT NULL,
        `1X1X12` text DEFAULT NULL,
        `1X1X5main1_sub1` text DEFAULT NULL,
        `1X1X5main1_sub2` text DEFAULT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE tokens_1 (
  `tid` integer NOT NULL,
  `participant_id` varchar(50) DEFAULT NULL,
  `firstname` varchar(40) DEFAULT NULL,
  `lastname` varchar(40) DEFAULT NULL,
  `email` text,
  `emailstatus` text,
  `token` varchar(35) DEFAULT NULL,
  `language` varchar(25) DEFAULT NULL,
  `blacklisted` varchar(17) DEFAULT NULL,
  `sent` varchar(17) DEFAULT 'N',
  `remindersent` varchar(17) DEFAULT 'N',
  `remindercount` int(11) DEFAULT '0',
  `completed` varchar(17) DEFAULT 'N',
  `usesleft` int(11) DEFAULT '1',
  `validfrom` datetime DEFAULT NULL,
  `validuntil` datetime DEFAULT NULL,
  `mpid` int(11) DEFAULT NULL,
  `attribute_1` varchar(255) DEFAULT NULL,
  `attribute_2` varchar(255) DEFAULT NULL,
  `attribute_3` varchar(255) DEFAULT NULL,
  `attribute_4` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`tid`)
);

CREATE TABLE `survey_2` (
  `id` int(11) NOT NULL,
  `submitdate` datetime DEFAULT NULL,
  `lastpage` int(11) DEFAULT NULL,
  `startlanguage` varchar(20) NOT NULL,
  `startdate` datetime NOT NULL,
  `datestamp` datetime NOT NULL,
  `2X8X176` text,
  `2X8X177` text,
  `2X8X178` text,
  `2X8X179` text,
  `2X8X180` text,
  `2X8X181SQ01` text,
  `2X8X181SQ02` text,
  `2X8X181SQ03` text,
  `2X8X181SQ04` text,
  `2X8X182SQ01` text,
  `2X8X182SQ02` text,
  `2X8X182SQ03` text,
  `2X8X182SQ04` text,
  `2X8X183SQ01` text,
  `2X8X183SQ02` text,
  `2X8X183SQ03` text,
  `2X8X183SQ04` text,
  `2X8X206SQY01_SQX01` text,
  `2X8X206SQY01_SQX02` text,
  `2X8X206SQY01_SQX03` text,
  `2X8X206SQY01_SQX04` text,
  `2X8X206SQY02_SQX01` text,
  `2X8X206SQY02_SQX02` text,
  `2X8X206SQY02_SQX03` text,
  `2X8X206SQY02_SQX04` text,
  `2X8X206SQY03_SQX01` text,
  `2X8X206SQY03_SQX02` text,
  `2X8X206SQY03_SQX03` text,
  `2X8X206SQY03_SQX04` text,
  `2X8X206SQY04_SQX01` text,
  `2X8X206SQY04_SQX02` text,
  `2X8X206SQY04_SQX03` text,
  `2X8X206SQY04_SQX04` text,
  `2X9X184` decimal(30,10) DEFAULT NULL,
  `2X9X185SQ01` decimal(30,10) DEFAULT NULL,
  `2X9X185SQ02` decimal(30,10) DEFAULT NULL,
  `2X9X185SQ03` decimal(30,10) DEFAULT NULL,
  `2X9X185SQ04` decimal(30,10) DEFAULT NULL,
  `2X9X186SQ001` decimal(30,10) DEFAULT NULL,
  `2X9X186SQ002` decimal(30,10) DEFAULT NULL,
  `2X9X186SQ003` decimal(30,10) DEFAULT NULL,
  `2X9X187SQ01` decimal(30,10) DEFAULT NULL,
  `2X9X187SQ02` decimal(30,10) DEFAULT NULL,
  `2X9X187SQ03` decimal(30,10) DEFAULT NULL,
  `2X9X187SQ04` decimal(30,10) DEFAULT NULL,
  `2X9X207SQY01_SQX01` text,
  `2X9X207SQY01_SQX02` text,
  `2X9X207SQY01_SQX03` text,
  `2X9X207SQY01_SQX04` text,
  `2X9X207SQY02_SQX01` text,
  `2X9X207SQY02_SQX02` text,
  `2X9X207SQY02_SQX03` text,
  `2X9X207SQY02_SQX04` text,
  `2X9X207SQY03_SQX01` text,
  `2X9X207SQY03_SQX02` text,
  `2X9X207SQY03_SQX03` text,
  `2X9X207SQY03_SQX04` text,
  `2X9X207SQY04_SQX01` text,
  `2X9X207SQY04_SQX02` text,
  `2X9X207SQY04_SQX03` text,
  `2X9X207SQY04_SQX04` text,
  `2X10X188` varchar(1) DEFAULT NULL,
  `2X10X189` varchar(1) DEFAULT NULL,
  `2X10X190` varchar(1) DEFAULT NULL,
  `2X10X191` varchar(20) DEFAULT NULL,
  `2X10X213` varchar(5) DEFAULT NULL,
  `2X10X192` varchar(5) DEFAULT NULL,
  `2X10X193` varchar(5) DEFAULT NULL,
  `2X10X194` varchar(5) DEFAULT NULL,
  `2X10X194comment` text,
  `2X10X198` datetime DEFAULT NULL,
  `2X10X199` datetime DEFAULT NULL,
  `2X10X205` varchar(1) DEFAULT NULL,
  `2X10X201SQ01` varchar(5) DEFAULT NULL,
  `2X10X201SQ02` varchar(5) DEFAULT NULL,
  `2X10X201SQ03` varchar(5) DEFAULT NULL,
  `2X10X201SQ04` varchar(5) DEFAULT NULL,
  `2X10X211SQ01` varchar(5) DEFAULT NULL,
  `2X10X211SQ02` varchar(5) DEFAULT NULL,
  `2X10X211SQ03` varchar(5) DEFAULT NULL,
  `2X10X211SQ04` varchar(5) DEFAULT NULL,
  `2X10X202SQ01` varchar(5) DEFAULT NULL,
  `2X10X202SQ02` varchar(5) DEFAULT NULL,
  `2X10X202SQ03` varchar(5) DEFAULT NULL,
  `2X10X202SQ04` varchar(5) DEFAULT NULL,
  `2X10X203SQ01` varchar(5) DEFAULT NULL,
  `2X10X203SQ02` varchar(5) DEFAULT NULL,
  `2X10X203SQ03` varchar(5) DEFAULT NULL,
  `2X10X203SQ04` varchar(5) DEFAULT NULL,
  `2X10X204SQ01` varchar(5) DEFAULT NULL,
  `2X10X204SQ02` varchar(5) DEFAULT NULL,
  `2X10X204SQ03` varchar(5) DEFAULT NULL,
  `2X10X204SQ04` varchar(5) DEFAULT NULL,
  `2X10X214SQ01` varchar(5) DEFAULT NULL,
  `2X10X214SQ02` varchar(5) DEFAULT NULL,
  `2X10X214SQ03` varchar(5) DEFAULT NULL,
  `2X10X214SQ04` varchar(5) DEFAULT NULL,
  `2X10X209SQ01#0` varchar(5) DEFAULT NULL,
  `2X10X209SQ01#1` varchar(5) DEFAULT NULL,
  `2X10X209SQ02#0` varchar(5) DEFAULT NULL,
  `2X10X209SQ02#1` varchar(5) DEFAULT NULL,
  `2X10X209SQ03#0` varchar(5) DEFAULT NULL,
  `2X10X209SQ03#1` varchar(5) DEFAULT NULL,
  `2X10X209SQ04#0` varchar(5) DEFAULT NULL,
  `2X10X209SQ04#1` varchar(5) DEFAULT NULL,
  `2X10X210SQ01#0` varchar(5) DEFAULT NULL,
  `2X10X210SQ01#1` varchar(5) DEFAULT NULL,
  `2X10X210SQ02#0` varchar(5) DEFAULT NULL,
  `2X10X210SQ02#1` varchar(5) DEFAULT NULL,
  `2X10X210SQ03#0` varchar(5) DEFAULT NULL,
  `2X10X210SQ03#1` varchar(5) DEFAULT NULL,
  `2X10X210SQ04#0` varchar(5) DEFAULT NULL,
  `2X10X210SQ04#1` varchar(5) DEFAULT NULL,
  `2X10X212SQY01_SQX01` text,
  `2X10X212SQY01_SQX02` text,
  `2X10X212SQY01_SQX03` text,
  `2X10X212SQY01_SQX04` text,
  `2X10X212SQY02_SQX01` text,
  `2X10X212SQY02_SQX02` text,
  `2X10X212SQY02_SQX03` text,
  `2X10X212SQY02_SQX04` text,
  `2X10X212SQY03_SQX01` text,
  `2X10X212SQY03_SQX02` text,
  `2X10X212SQY03_SQX03` text,
  `2X10X212SQY03_SQX04` text,
  `2X10X212SQY04_SQX01` text,
  `2X10X212SQY04_SQX02` text,
  `2X10X212SQY04_SQX03` text,
  `2X10X212SQY04_SQX04` text,
  `2X11X195SQ01` varchar(5) DEFAULT NULL,
  `2X11X195SQ02` varchar(5) DEFAULT NULL,
  `2X11X195SQ03` varchar(5) DEFAULT NULL,
  `2X11X195SQ04` varchar(5) DEFAULT NULL,
  `2X11X195other` text,
  `2X11X196SQ01` varchar(5) DEFAULT NULL,
  `2X11X196SQ02` varchar(5) DEFAULT NULL,
  `2X11X196SQ03` varchar(5) DEFAULT NULL,
  `2X11X196SQ04` varchar(5) DEFAULT NULL,
  `2X11X196SQ05` varchar(5) DEFAULT NULL,
  `2X11X197SQ001` varchar(5) DEFAULT NULL,
  `2X11X197SQ001comment` text,
  `2X11X197SQ002` varchar(5) DEFAULT NULL,
  `2X11X197SQ002comment` text,
  `2X11X197SQ003` varchar(5) DEFAULT NULL,
  `2X11X197SQ003comment` text,
  `2X11X197SQ004` varchar(5) DEFAULT NULL,
  `2X11X197SQ004comment` text,
  `2X11X216` varchar(1) DEFAULT NULL,
  `2X11X208SQY01_SQX01` text,
  `2X11X208SQY01_SQX02` text,
  `2X11X208SQY01_SQX03` text,
  `2X11X208SQY01_SQX04` text,
  `2X11X208SQY02_SQX01` text,
  `2X11X208SQY02_SQX02` text,
  `2X11X208SQY02_SQX03` text,
  `2X11X208SQY02_SQX04` text,
  `2X11X208SQY03_SQX01` text,
  `2X11X208SQY03_SQX02` text,
  `2X11X208SQY03_SQX03` text,
  `2X11X208SQY03_SQX04` text,
  `2X11X208SQY04_SQX01` text,
  `2X11X208SQY04_SQX02` text,
  `2X11X208SQY04_SQX03` text,
  `2X11X208SQY04_SQX04` text,
  `2X11X217` varchar(1) DEFAULT NULL,
  `2X11X2001` varchar(5) DEFAULT NULL,
  `2X11X2002` varchar(5) DEFAULT NULL,
  `2X11X2003` varchar(5) DEFAULT NULL,
  `2X11X2004` varchar(5) DEFAULT NULL,
  `2X11X2151` varchar(5) DEFAULT NULL,
  `2X11X2152` varchar(5) DEFAULT NULL,
  `2X11X2153` varchar(5) DEFAULT NULL,
  `2X11X2154` varchar(5) DEFAULT NULL,
  `2X12X218` varchar(1) DEFAULT NULL,
  `2X12X219` varchar(1) DEFAULT NULL,
  `2X12X220` varchar(1) DEFAULT NULL,
  `2X12X221` varchar(1) DEFAULT NULL,
  `2X12X230` varchar(1) DEFAULT NULL,
  `2X12X231` varchar(1) DEFAULT NULL,
  `2X12X232` varchar(1) DEFAULT NULL,
  `2X12X222SH101` varchar(5) DEFAULT NULL,
  `2X12X222SH102` varchar(5) DEFAULT NULL,
  `2X12X223SQ201` varchar(5) DEFAULT NULL,
  `2X12X223SQ202` varchar(5) DEFAULT NULL,
  `2X12X226` varchar(1) DEFAULT NULL,
  `2X12X224` varchar(1) DEFAULT NULL,
  `2X12X225` varchar(1) DEFAULT NULL,
  `2X13X227SQ01` varchar(5) DEFAULT NULL,
  `2X13X227SQ02` varchar(5) DEFAULT NULL,
  `2X13X227SQ03` varchar(5) DEFAULT NULL,
  `2X13X227SQ04` varchar(5) DEFAULT NULL,
  `2X13X228SQ01` varchar(5) DEFAULT NULL,
  `2X13X228SQ02` varchar(5) DEFAULT NULL,
  `2X13X228SQ03` varchar(5) DEFAULT NULL,
  `2X13X228SQ04` varchar(5) DEFAULT NULL,
  `2X13X229SQ01` varchar(5) DEFAULT NULL,
  `2X13X229SQ02` varchar(5) DEFAULT NULL,
  `2X13X229SQ03` varchar(5) DEFAULT NULL,
  `2X13X229SQ04` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
