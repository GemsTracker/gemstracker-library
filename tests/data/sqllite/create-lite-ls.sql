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

CREATE TABLE survey_1 (
	`id` int(11) NOT NULL,
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
	PRIMARY KEY (`id`)
);