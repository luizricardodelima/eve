<?php
require_once '../evedbconfig.php';
require_once '../eve.class.php';
require_once '../eveuserservice.class.php';

function create_database_4($dbpassword, $screenname, $password)
{
	// TODO rename errors to log
	$errors = array();
	
	// Validating input
	if ($dbpassword != EveDBConfig::$password)
	{
		$errors[] = "ERROR - Database provided is not the same as defined in evedbconfig.php";
	}
	else if (!filter_var($screenname, FILTER_VALIDATE_EMAIL))
	{
		$errors[] = "ERROR - Superuser e-mail is invalid";
	}
	if (trim($password) === '')
	{
		$errors[] = "ERROR - Superuser password cannot be blank";
	}

	// If $messages array is not empty at this point, it means that fundamental requirements were
	// not met and therefore the database creation sequence cannot go on. Returning messages.
	if (!empty($errors)) return($errors);
	else $errors[] = "SUCCESS - Parameters are valid";

	// Connecting to database
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if (!$mysqli)
	{
		$errors[] = "ERROR - Impossible to conect to database. Mysqli error: ". $mysqli->error;
		return($errors);
	}
	else $errors[] = "SUCCESS - Connected to database";
	
	$mysqli->set_charset("utf8");

	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition` (
		  `id` int(11) NOT NULL,
		  `description` text COLLATE utf8_unicode_ci,
		  `information` text COLLATE utf8_unicode_ci,
		  `requirement` enum('none','after_payment') COLLATE utf8_unicode_ci DEFAULT 'none',
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
	if ($mysqli->error) $errors['submission_definition creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition_access` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) NOT NULL,
		  `type` enum('specific_user','specific_category','submission_after_deadline') COLLATE utf8_unicode_ci DEFAULT 'specific_user',
		  `content` varchar(255) COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	
	if ($mysqli->error) $errors['submission_definition_access_rule creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition_reviewer` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) NOT NULL,
		  `email` varchar(255) COLLATE utf8_unicode_ci,
		  `type` varchar(255) COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	
	if ($mysqli->error) $errors['submission_definition_reviewer creation'] = $mysqli->error;

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
		  `active` tinyint(4) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['submission creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}usercategory` (
		  `id` int(11) NOT NULL,
		  `description` varchar(255) COLLATE utf8_unicode_ci,
		  `special` tinyint(4) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	
	if ($mysqli->error) $errors['user_category creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}certification` (
		  `id` int(11) NOT NULL,
		  `certificationdef_id` int(11) DEFAULT NULL,
		  `screenname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `submissionid` int(11) DEFAULT NULL,
		  `locked` int(11) NOT NULL DEFAULT '0',
		  `views` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['certification creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}certificationdef` (
		  `id` int(11) NOT NULL,
		  `type` enum('usercertification','submissioncertification') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'usercertification',
		  `name` text COLLATE utf8_unicode_ci,
		  `pagesize` enum('A3','A4','A5','Letter','Legal') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A4',
		  `pageorientation` enum('P','L') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'L',
		  `backgroundimage` text COLLATE utf8_unicode_ci,
		  `text` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '[]',
		  `topmargin` smallint(6) NOT NULL DEFAULT '0',
		  `leftmargin` smallint(6) NOT NULL DEFAULT '0',
		  `rightmargin` smallint(6) NOT NULL DEFAULT '0',
		  `text_lineheight` int(11) NOT NULL DEFAULT '0',
		  `text_fontsize` int(11) NOT NULL DEFAULT '0',
		  `hasopenermsg` smallint(6) NOT NULL DEFAULT '0',
		  `openermsg` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['certificationdef creation'] = $mysqli->error;
	
	$mysqli->query
	("
		CREATE TABLE `{$pref}unverifieduser` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `verificationcode` varchar(255) COLLATE utf8_unicode_ci NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['unverified_user creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}pages` (
		  `id` int(11) NOT NULL,
		  `position` smallint(6) NOT NULL DEFAULT '0',
		  `is_visible` tinyint(4) NOT NULL DEFAULT '1',
		  `is_homepage` tinyint(4) NOT NULL DEFAULT '0',
		  `title` text COLLATE utf8_unicode_ci,
		  `content` text COLLATE utf8_unicode_ci,
		  `views` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['pages creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}payment` (
		  `id` int(11) NOT NULL,
		  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `date` date,
		  `paymenttype_id` int(11) DEFAULT NULL,
		  `value_paid` double NOT NULL DEFAULT '0',
		  `value_received` double NOT NULL DEFAULT '0',
		  `note` text COLLATE utf8_unicode_ci,
		  `image` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['payment creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}paymenttype` (
		  `id` int(11) NOT NULL,
		  `name` text COLLATE utf8_unicode_ci,
		  `description` text COLLATE utf8_unicode_ci,
		  `active` tinyint(11) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['paymenttype creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}settings` (
		  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `value` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['settings creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}user` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['user creation'] = $mysqli->error;

	// TODO Move admin to user table
	$mysqli->query
	("
		CREATE TABLE `{$pref}userdata` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `admin` tinyint(4) NOT NULL DEFAULT '0',
		  `locked_form` tinyint(4) NOT NULL DEFAULT '0',
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
		  `category_id` int(11) DEFAULT NULL,
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

	if ($mysqli->error) $errors['user_data creation'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['submission pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_access`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `email` (`content`),
		  ADD KEY `submission_definition_id` (`submission_definition_id`);
	");

	if ($mysqli->error) $errors['submission_definition_access pk fk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_reviewer`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `submission_definition_id` (`submission_definition_id`);
	");

	if ($mysqli->error) $errors['submission_definition_reviewer pk fk'] = $mysqli->error;


	$mysqli->query
	("
		ALTER TABLE `{$pref}submission`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `submission_definition_id` (`submission_definition_id`),
		  ADD KEY `email` (`email`),
		  ADD KEY `reviewer_email` (`reviewer_email`);
	");

	if ($mysqli->error) $errors['submission pk fk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}usercategory`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['user_category pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}certification`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `certificationdef_id` (`certificationdef_id`),
		  ADD KEY `screenname` (`screenname`),
		  ADD KEY `submissionid` (`submissionid`);
	");

	if ($mysqli->error) $errors['certification pk fk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}certificationdef`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['certificationdef pk'] = $mysqli->error;
	
	$mysqli->query
	("
		ALTER TABLE `{$pref}unverifieduser`
		  ADD PRIMARY KEY (`email`);
	");
	
	if ($mysqli->error) $errors['unverified_user pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}pages`
		  ADD PRIMARY KEY (`id`);
	");
	
	if ($mysqli->error) $errors['pages pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `paymenttype_id` (`paymenttype_id`),
		  ADD UNIQUE KEY `email` (`email`);
	");

	if ($mysqli->error) $errors['payment pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}paymenttype`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['paymenttype pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}settings`
		  ADD PRIMARY KEY (`key`);
	");

	if ($mysqli->error) $errors['settings pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}user`
		  ADD PRIMARY KEY (`email`);
	");

	if ($mysqli->error) $errors['user pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}userdata`
		  ADD UNIQUE KEY `email` (`email`),
		  ADD KEY `category_id` (`category_id`);
	");
	
	if ($mysqli->error) $errors['user_data pk fk'] = $mysqli->error;

	// Auto increments
	$mysqli->query("ALTER TABLE `{$pref}submission_definition` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}submission_definition_access` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}submission_definition_reviewer` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}submission` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}usercategory` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}certification` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}certificationdef` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}pages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}payment` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}paymenttype` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) $errors['autoincrements'] = $mysqli->error;

	// Constraints
	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_access`
		  ADD CONSTRAINT `{$pref}submission_definition_access_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['submission_definition_access_rule constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_reviewer`
		  ADD CONSTRAINT `{$pref}submission_definition_reviewer_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['submission_definition_reviewer constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission`
		  ADD CONSTRAINT `{$pref}submission_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_ibfk_2` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_ibfk_3` FOREIGN KEY (`reviewer_email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['submission constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}certification`
		  ADD CONSTRAINT `{$pref}certification_ibfk_1` FOREIGN KEY (`certificationdef_id`) REFERENCES `{$pref}certificationdef` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}certification_ibfk_2` FOREIGN KEY (`screenname`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}certification_ibfk_3` FOREIGN KEY (`submissionid`) REFERENCES `{$pref}submission`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['certification constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment`
		  ADD CONSTRAINT `{$pref}payment_ibfk_1` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}payment_ibfk_2` FOREIGN KEY (`paymenttype_id`) REFERENCES `{$pref}paymenttype` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;"
	);
	if ($mysqli->error) $errors['payment constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}userdata`
		  ADD CONSTRAINT `{$pref}userdata_ibfk_1` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}userdata_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{$pref}usercategory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['user_data constraints'] = $mysqli->error;

	// Loading settings
	$base_settings = json_decode(file_get_contents('settings.json'), true);
    $user_settings = json_decode(file_get_contents('settings_user.json'), true);
    $settings = array_merge($base_settings, $user_settings);

	// Populating settings		
	$stmt = $mysqli->prepare("insert into `{$pref}settings` (`key`, `value`) values (?, ?)");
	foreach($settings as $key => $value)
	{
		$stmt->bind_param('ss', $key, $value);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			$errors['settings values'] = $stmt->error;
			// TODO TRANSACTION ROLLBACK, BETTER ERROR INFO AND QUIT
		}
	}
	$stmt->close();
	
	$eve = new Eve();
	$eveUserServices = new EveUserServices($eve);
	$encryptedPassword = $eveUserServices->encrypt($password);
	$eveUserServices->createUser($screenname, $encryptedPassword, false);
	$eveUserServices->setUserAsAdmin($screenname);

	$errors[] = "SUCCESS - Database successfully created";
	return $errors;
}

function delete_database_4($dbpassword)
{
	// TODO rename errors to log
	$errors = array();
	
	// Validating input
	if ($dbpassword != EveDBConfig::$password)
	{
		$errors[] = "ERROR - Database provided is not the same as defined in evedbconfig.php";
	}
	
	// If $messages array is not empty at this point, it means that fundamental requirements were
	// not met and therefore the database creation sequence cannot go on. Returning messages.
	if (!empty($errors)) return($errors);
	else $errors[] = "SUCCESS - Parameters are valid";
	
	// Connecting to database
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if (!$mysqli)
	{
		$errors[] = "ERROR - Impossible to conect to database. Mysqli error: ". $mysqli->error;
		return($errors);
	}
	else $errors[] = "SUCCESS - Connected to database";

	// Deleting free tables
	$mysqli->query("DROP TABLE if exists `{$pref}settings`;");
	if ($mysqli->error) $errors['settings delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}pages`;");
	if ($mysqli->error) $errors['pages delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}unverifieduser`;");
	if ($mysqli->error) $errors['unverified_user delete error'] = $mysqli->error;

	// Deleting payment tables
	$mysqli->query("DROP TABLE if exists `{$pref}payment`;");
	if ($mysqli->error) $errors['payment delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}paymenttype`;");
	if ($mysqli->error) $errors['paymenttype delete error'] = $mysqli->error;

	// Deleting certification tables
	$mysqli->query("DROP TABLE if exists `{$pref}certification`;");
	if ($mysqli->error) $errors['certification delete error'] = $mysqli->error;	
	$mysqli->query("DROP TABLE if exists `{$pref}certificationdef`;");
	if ($mysqli->error) $errors['certificationdef delete error'] = $mysqli->error;

	// Deleting submission tables
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition_access`;");
	if ($mysqli->error) $errors['submission_definition_access_rule delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition_reviewer`;");
	if ($mysqli->error) $errors['submission_definition_reviewer delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission`;");
	if ($mysqli->error) $errors['submission delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition`;");
	if ($mysqli->error) $errors['submission_definitions delete error'] = $mysqli->error;

	// Deleting user tables
	$mysqli->query("DROP TABLE if exists `{$pref}userdata`;");
	if ($mysqli->error) $errors['user_data delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}usercategory`;");
	if ($mysqli->error) $errors['user_category delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}user`;");
	if ($mysqli->error) $errors['user delete error'] = $mysqli->error;

	$errors[] = "SUCCESS - Database successfully deleted";
	return $errors;
}

function check_database()
{
	$messages = array();

	// Connecting to database
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
		$eve = new Eve();
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
		$messages = create_database_4($_POST['db_password'],$_POST['su_email'],$_POST['su_password']);
		echo "<ul>"; foreach ($messages as $message) echo"<li>$message</li>"; echo "</ul>";
	break;
	case 'delete':
		$messages = delete_database_4($_POST['db_password']);
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
