<?php
require_once '../evedbconfig.php';
require_once '../eve.class.php';
require_once '../eveuserservice.class.php';

function create_database($dbpassword, $screenname, $password)
{
	$log = array();
	
	// Validating input
	if ($dbpassword != EveDBConfig::$password)
	{
		$log[] = "ERROR - Database provided is not the same as defined in evedbconfig.php";
	}
	else if (!filter_var($screenname, FILTER_VALIDATE_EMAIL))
	{
		$log[] = "ERROR - Superuser e-mail is invalid";
	}
	if (trim($password) === '')
	{
		$log[] = "ERROR - Superuser password cannot be blank";
	}

	// If $messages array is not empty at this point, it means that fundamental requirements were
	// not met and therefore the database creation sequence cannot go on. Returning messages.
	if (!empty($log)) return($log);
	else $log[] = "Parameters are valid";

	// Connecting to database
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if (!$mysqli)
	{
		$log[] = "ERROR - Impossible to conect to database. Mysqli error: ". $mysqli->error;
		return($log);
	}
	else $log[] = "Connected to database";
	
	// Charset ////////////////////////////////////////////////////////////////////////////////////
	$mysqli->set_charset("utf8");
	if ($mysqli->error) {$log[] = "ERROR - Charset error - ".$mysqli->error; return $log;}

	// Tables /////////////////////////////////////////////////////////////////////////////////////
	// Create table submission_definition
	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition` (
		  `id` int(11) NOT NULL,
		  `description` text COLLATE utf8_unicode_ci,
		  `information` text COLLATE utf8_unicode_ci,
		  `requirement` enum('none') COLLATE utf8_unicode_ci DEFAULT 'none',
		  `allow_multiple_submissions` tinyint(4) NOT NULL DEFAULT '0',
		  `deadline` datetime,
		  `submission_structure` text COLLATE utf8_unicode_ci,
		  `revision_structure` text COLLATE utf8_unicode_ci,
		  `access_restricted` tinyint(4) NOT NULL DEFAULT '0',
		  `send_email_on_create` tinyint(4) NOT NULL DEFAULT '1',
		  `send_email_on_delete` tinyint(4) NOT NULL DEFAULT '1',
		  `send_email_on_update` tinyint(4) NOT NULL DEFAULT '1',
		  `active` tinyint(4) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table submission_definition - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table submission_definition_access
	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition_access` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) NOT NULL,
		  `type` enum('specific_user','submission_after_deadline') COLLATE utf8_unicode_ci DEFAULT 'specific_user',
		  `content` varchar(255) COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table submission_definition_access - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table submission_definition_reviewer
	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition_reviewer` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) NOT NULL,
		  `email` varchar(255) COLLATE utf8_unicode_ci,
		  `type` varchar(255) COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table submission_definition_reviewer - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table submission
	$mysqli->query
	("
		CREATE TABLE `{$pref}submission` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) DEFAULT NULL,
		  `structure` text COLLATE utf8_unicode_ci,
		  `email` varchar(255) COLLATE utf8_unicode_ci,
		  `date` datetime,
		  `content` text COLLATE utf8_unicode_ci,
		  `reviewer_email` varchar(255) COLLATE utf8_unicode_ci,
		  `revision_structure` text COLLATE utf8_unicode_ci,
		  `revision_content` text COLLATE utf8_unicode_ci,
		  `revision_status` int(11) NOT NULL DEFAULT '0',
		  `access` enum('default','user_review', 'reviewer_review') COLLATE utf8_unicode_ci DEFAULT 'default',
		  `active` tinyint(4) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table submission - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table submission_history
	$mysqli->query
		("
			CREATE TABLE `{$pref}submission_history` (
			  `id` int(11) NOT NULL,
			  `submission_id` int(11) NOT NULL,
			  `agent_email` varchar(255) COLLATE utf8_unicode_ci,
			  `date` datetime,
			  `type` enum('creation','update','removal') COLLATE utf8_unicode_ci DEFAULT 'creation',
			  `submission_structure` text COLLATE utf8_unicode_ci,
			  `submission_content` text COLLATE utf8_unicode_ci,
			  `revision_structure` text COLLATE utf8_unicode_ci,
			  `revision_content` text COLLATE utf8_unicode_ci
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		");
	if ($mysqli->error) {$log[] = "ERROR - Create table submission_history - ".$mysqli->error; delete_database($dbpassword); return $log;}

	
	// Create table certification
	$mysqli->query
	("
		CREATE TABLE `{$pref}certification` (
		  `id` int(11) NOT NULL,
		  `certification_model_id` int(11) DEFAULT NULL,
		  `screenname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `submissionid` int(11) DEFAULT NULL,
		  `views` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table certification - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table certification_model
	$mysqli->query
	("
		CREATE TABLE `{$pref}certification_model` (
		  `id` int(11) NOT NULL,
		  `type` enum('usercertification','submissioncertification') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'usercertification',
		  `name` text COLLATE utf8_unicode_ci,
		  `pagesize` enum('A3','A4','A5','Letter','Legal') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A4',
		  `pageorientation` enum('P','L') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'L',
		  `backgroundimage` text COLLATE utf8_unicode_ci,
		  `text` text COLLATE utf8_unicode_ci,
		  `topmargin` smallint(6) NOT NULL DEFAULT '0',
		  `leftmargin` smallint(6) NOT NULL DEFAULT '0',
		  `rightmargin` smallint(6) NOT NULL DEFAULT '0',
		  `text_lineheight` int(11) NOT NULL DEFAULT '0',
		  `text_fontsize` int(11) NOT NULL DEFAULT '0',
		  `text_font` text COLLATE utf8_unicode_ci,
		  `text_alignment` enum('left','right','center','justified') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'left',
		  `hasopenermsg` smallint(6) NOT NULL DEFAULT '0',
		  `openermsg` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table certification_model - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table unverifieduser
	$mysqli->query
	("
		CREATE TABLE `{$pref}unverifieduser` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `verificationcode` varchar(255) COLLATE utf8_unicode_ci NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table unverifieduser - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table pages
	$mysqli->query
	("
		CREATE TABLE `{$pref}page` (
		  `id` int(11) NOT NULL,
		  `position` smallint(6) NOT NULL DEFAULT '0',
		  `is_visible` tinyint(4) NOT NULL DEFAULT '1',
		  `is_homepage` tinyint(4) NOT NULL DEFAULT '0',
		  `title` text COLLATE utf8_unicode_ci,
		  `content` text COLLATE utf8_unicode_ci,
		  `views` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table pages - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table payment
	$mysqli->query
	("
		CREATE TABLE `{$pref}payment` (
		  `id` int(11) NOT NULL,
		  `payment_group_id` int(11) DEFAULT NULL,
		  `user_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `date` date,
		  `payment_method` text COLLATE utf8_unicode_ci,
		  `value_paid` double NOT NULL DEFAULT '0',
		  `value_received` double NOT NULL DEFAULT '0',
		  `note` text COLLATE utf8_unicode_ci,
		  `file` text COLLATE utf8_unicode_ci,
		  `active` tinyint(11) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table payment - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table payment_item
	$mysqli->query
	("
		CREATE TABLE `{$pref}payment_item` (
		  `id` int(11) NOT NULL,
		  `payment_id` int(11) NOT NULL,
		  `payment_option_id` int(11) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table payment_item - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table payment_group
	$mysqli->query
	("
		CREATE TABLE `{$pref}payment_group` (
		  `id` int(11) NOT NULL,
		  `name` text COLLATE utf8_unicode_ci,
		  `payment_info` text COLLATE utf8_unicode_ci,
		  `unverified_payment_info` text COLLATE utf8_unicode_ci,
		  `verified_payment_info` text COLLATE utf8_unicode_ci,
		  `state` enum('active','inactive','invisible') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table payment_group - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table payment_option
	$mysqli->query
	("
		CREATE TABLE `{$pref}payment_option` (
		  `id` int(11) NOT NULL,
		  `type` enum('main','accessory') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'main',
		  `name` text COLLATE utf8_unicode_ci,
		  `description` text COLLATE utf8_unicode_ci,
		  `value` double NOT NULL DEFAULT '0',
		  `available_from` datetime,
		  `available_to` datetime,
		  `payment_group_id` int(11) DEFAULT NULL,
		  `admin_only` tinyint(11) NOT NULL DEFAULT '0',
		  `active` tinyint(11) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table payment_option - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table settings
	$mysqli->query
	("
		CREATE TABLE `{$pref}settings` (
		  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `value` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table settings - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table user
	$mysqli->query
	("
		CREATE TABLE `{$pref}user` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table user - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Create table userdata
	$mysqli->query // TODO: Move admin to userdata
	("
		CREATE TABLE `{$pref}userdata` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `admin` tinyint(4) NOT NULL DEFAULT '0',
		  `name` text COLLATE utf8_unicode_ci,
		  `address` text COLLATE utf8_unicode_ci,
		  `city` text COLLATE utf8_unicode_ci,
		  `state` text COLLATE utf8_unicode_ci,
		  `country` text COLLATE utf8_unicode_ci,
		  `postalcode` text COLLATE utf8_unicode_ci,
		  `birthday` date,
		  `gender` enum('male','female','rathernotsay') COLLATE utf8_unicode_ci DEFAULT NULL,
		  `phone1` text COLLATE utf8_unicode_ci,
		  `phone2` text COLLATE utf8_unicode_ci,
		  `institution` text COLLATE utf8_unicode_ci,
		  `customtext1` text COLLATE utf8_unicode_ci,
		  `customtext2` text COLLATE utf8_unicode_ci,
		  `customtext3` text COLLATE utf8_unicode_ci,
		  `customtext4` text COLLATE utf8_unicode_ci,
		  `customtext5` text COLLATE utf8_unicode_ci,
		  `customflag1` int(11) NOT NULL DEFAULT '0',
		  `customflag2` int(11) NOT NULL DEFAULT '0',
		  `customflag3` int(11) NOT NULL DEFAULT '0',
		  `customflag4` int(11) NOT NULL DEFAULT '0',
		  `customflag5` int(11) NOT NULL DEFAULT '0',
		  `note` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) {$log[] = "ERROR - Create table userdata - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Keys and primary keys //////////////////////////////////////////////////////////////////////
	$mysqli->query("ALTER TABLE `{$pref}submission_definition` ADD PRIMARY KEY (`id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys submission_definition - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}submission_definition_access` ADD PRIMARY KEY (`id`), ADD KEY `submission_definition_id` (`submission_definition_id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys submission_definition_access - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}submission_definition_reviewer` ADD PRIMARY KEY (`id`), ADD KEY `submission_definition_id` (`submission_definition_id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys submission_definition_reviewer - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}submission` ADD PRIMARY KEY (`id`), ADD KEY `submission_definition_id` (`submission_definition_id`), ADD KEY `email` (`email`), ADD KEY `reviewer_email` (`reviewer_email`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys submission - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}submission_history` ADD PRIMARY KEY (`id`), ADD KEY `submission_id` (`submission_id`), ADD KEY `agent_email` (`agent_email`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys submission_history - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}certification` ADD PRIMARY KEY (`id`), ADD KEY `certification_model_id` (`certification_model_id`), ADD KEY `screenname` (`screenname`), ADD KEY `submissionid` (`submissionid`), ADD UNIQUE( `certification_model_id`, `screenname`, `submissionid`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys certification - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}certification_model` ADD PRIMARY KEY (`id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys certification_model- ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}unverifieduser` ADD PRIMARY KEY (`email`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys unverifieduser - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}page` ADD PRIMARY KEY (`id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys pages - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}payment` ADD PRIMARY KEY (`id`), ADD KEY `payment_group_id` (`payment_group_id`), ADD KEY `user_email` (`user_email`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys payment - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}payment_item` ADD PRIMARY KEY (`id`), ADD KEY `payment_id` (`payment_id`), ADD KEY `payment_option_id` (`payment_option_id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys payment_item - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}payment_group` ADD PRIMARY KEY (`id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys payment_group - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}payment_option` ADD PRIMARY KEY (`id`), ADD KEY `payment_group_id` (`payment_group_id`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys payment_option - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}settings` ADD PRIMARY KEY (`key`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys settings - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}user` ADD PRIMARY KEY (`email`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys user - ".$mysqli->error; delete_database($dbpassword); return $log;}
	
	$mysqli->query("ALTER TABLE `{$pref}userdata` ADD UNIQUE KEY `email` (`email`);");
	if ($mysqli->error) {$log[] = "ERROR - Keys and primary keys userdata - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Auto increments ////////////////////////////////////////////////////////////////////////////

	$mysqli->query("ALTER TABLE `{$pref}submission_definition` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment submission_definition - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}submission_definition_access` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment submission_definition_access - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}submission_definition_reviewer` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment submission_definition_reviewer - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}submission` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment submission - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}submission_history` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment submission_history - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}certification` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment certification - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}certification_model` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment certification_model - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}page` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment pages - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}payment` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment payment - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}payment_item` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment payment_item - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}payment_group` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment payment_group - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query("ALTER TABLE `{$pref}payment_option` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) {$log[] = "ERROR - Auto increment payment_option - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Foreign keys ///////////////////////////////////////////////////////////////////////////////

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_access`
		  ADD CONSTRAINT `{$pref}submission_definition_access_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys submission_definition_access - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_reviewer`
		  ADD CONSTRAINT `{$pref}submission_definition_reviewer_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys submission_definition_reviewer - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission`
		  ADD CONSTRAINT `{$pref}submission_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_ibfk_2` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_ibfk_3` FOREIGN KEY (`reviewer_email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE;
	");
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys submission - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_history`
		  ADD CONSTRAINT `{$pref}submission_history_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `{$pref}submission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_history_ibfk_2` FOREIGN KEY (`agent_email`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
		if ($mysqli->error) {$log[] = "ERROR - Foreign keys submission_history - ".$mysqli->error; delete_database($dbpassword); return $log; }

	$mysqli->query
	("
		ALTER TABLE `{$pref}certification`
		  ADD CONSTRAINT `{$pref}certification_ibfk_1` FOREIGN KEY (`certification_model_id`) REFERENCES `{$pref}certification_model` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}certification_ibfk_2` FOREIGN KEY (`screenname`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}certification_ibfk_3` FOREIGN KEY (`submissionid`) REFERENCES `{$pref}submission`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys certification - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment`
		  ADD CONSTRAINT `{$pref}payment_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}payment_ibfk_2` FOREIGN KEY (`payment_group_id`) REFERENCES `{$pref}payment_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;"
	);
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys payment - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment_option`
		  ADD CONSTRAINT `{$pref}payment_option_ibfk_1` FOREIGN KEY (`payment_group_id`) REFERENCES `{$pref}payment_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;"
	);
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys payment_option - ".$mysqli->error; delete_database($dbpassword); return $log;}

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment_item`
		  ADD CONSTRAINT `{$pref}payment_item_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `{$pref}payment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}payment_item_ibfk_2` FOREIGN KEY (`payment_option_id`) REFERENCES `{$pref}payment_option` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;"
	);
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys payment_item - ".$mysqli->error; delete_database($dbpassword); return $log;}

	
	$mysqli->query
	("
		ALTER TABLE `{$pref}userdata`
		  ADD CONSTRAINT `{$pref}userdata_ibfk_1` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) {$log[] = "ERROR - Foreign keys userdata - ".$mysqli->error; delete_database($dbpassword); return $log;}

	// Settings ///////////////////////////////////////////////////////////////////////////////////

	// Loading settings
	$base_settings = json_decode(file_get_contents('settings.json'), true);
    $user_settings = json_decode(file_get_contents('settings_user.json'), true);
	if ($user_settings != null)
		$settings = array_merge($base_settings, $user_settings);
	else
	{
		//if settings_user.json is malformed or nonexistent, ignore it.
		$log[] = "WARNING - Malformed settings_user.json";
		$settings = $base_settings;
	}

	// Populating settings		
	$stmt = $mysqli->prepare("insert into `{$pref}settings` (`key`, `value`) values (?, ?)");
	foreach($settings as $key => $value)
	{
		$stmt->bind_param('ss', $key, $value);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			if ($mysqli->error) {$log[] = "ERROR - Populating settings - ".$mysqli->error; delete_database($dbpassword); return $log;}
		}
	}
	$stmt->close();
	
	$eve = new Eve();
	$EveUserService = new EveUserService($eve);
	$encryptedPassword = $EveUserService->encrypt($password);
	$EveUserService->user_create($screenname, $encryptedPassword, false);
	$EveUserService->admin_add($screenname);
	// TODO Check error according to settings

	$log[] = "SUCCESS - Database successfully created";
	return $log;
}

function delete_database($dbpassword)
{
	$log = array();
	
	// Validating input
	if ($dbpassword != EveDBConfig::$password)
	{
		$log[] = "ERROR - Database provided is not the same as defined in evedbconfig.php";
	}
	
	// If $messages array is not empty at this point, it means that fundamental requirements were
	// not met and therefore the database creation sequence cannot go on. Returning messages.
	if (!empty($log)) return($log);
	else $log[] = "Parameters are valid";
	
	// Connecting to database
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if (!$mysqli)
	{
		$log[] = "ERROR - Impossible to conect to database. Mysqli error: ". $mysqli->error;
		return($log);
	}
	else $log[] = "Connected to database";

	// Deleting free tables
	$mysqli->query("DROP TABLE if exists `{$pref}settings`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}page`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}unverifieduser`;");
	if ($mysqli->error) $log[] = $mysqli->error;

	// Deleting payment tables
	$mysqli->query("DROP TABLE if exists `{$pref}payment_item`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}payment_option`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}payment`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}payment_group`;");
	if ($mysqli->error) $log[] = $mysqli->error;

	// Deleting certification tables
	$mysqli->query("DROP TABLE if exists `{$pref}certification`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}certification_model`;");
	if ($mysqli->error) $log[] = $mysqli->error;

	// Deleting submission tables
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition_access`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition_reviewer`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_history`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition`;");
	if ($mysqli->error) $log[] = $mysqli->error;

	// Deleting user tables
	$mysqli->query("DROP TABLE if exists `{$pref}userdata`;");
	if ($mysqli->error) $log[] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}user`;");
	if ($mysqli->error) $log[] = $mysqli->error;

	$log[] = "SUCCESS - Database successfully deleted";
	return $log;
}

function check_database()
{
	$eve = new Eve(true);
	$messages = array();

	// Connecting to database //TODO: Eve already tried to connect. use eves values.
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if ($mysqli->connect_error)
	{
		$messages[] = "❌ Couldn't establish a connection to database. Check the values on <code>'evedbconf.php'</code> file and reload this page. Mysqli error: ". $mysqli->connect_error;
		return($messages);
	}
	else $messages[] = "✔️ Database is accessible";

	// TODO: Perform a better checking. check at least if the tables with the same name we have were created
	$result = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.tables WHERE TABLE_NAME LIKE '{$pref}%'");
	if ($result->fetch_assoc()) 
	{
		$messages[] = "✔️ Database tables were created.";
		$messages[] = "✔️ You can test the system at <a href=\"{$eve->sysurl()}\">{$eve->sysurl()}</a> and if everything is okay, delete the <code>/setup</code> folder.";
	}
	else $messages[] = "⚠️ Database is empty.";

	

	return $messages;
}

header("Content-Type: text/plain");

if (isset($_POST['action'])) switch ($_POST['action'])
{
	case 'create':
		$messages = create_database($_POST['db_password'],$_POST['su_email'],$_POST['su_password']);
		echo "<ul>"; foreach ($messages as $message) echo"<li>$message</li>"; echo "</ul>";
	break;
	case 'delete':
		$messages = delete_database($_POST['db_password']);
		echo "<ul>"; foreach ($messages as $message) echo"<li>$message</li>"; echo "</ul>";
	break;
	case 'check':
		$messages = check_database();
		echo "<ul>"; foreach ($messages as $message) echo"<li>$message</li>"; echo "</ul>";
	break;
}
else
{
	echo "Invalid input";
}
?>
