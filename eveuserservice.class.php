<?php

require_once 'eve.class.php';
require_once 'evemail.class.php';

class EveUserService
{
	private $eve;
	private $evemail;

	const ADMIN_ADD_ERROR_USER_DOES_NOT_EXIST = 'admin.add.error.user.does.not.exist';
	const ADMIN_ADD_SUCCESS = 'admin.add.success';
	const ADMIN_REMOVE_ERROR_USER_DOES_NOT_EXIST = 'admin.remove.error.user.does.not.exist';
	const ADMIN_REMOVE_ERROR_CANNOT_REMOVE_ITSELF = 'admin.remove.error.cannot.remove.itself';
	const ADMIN_REMOVE_SUCCESS = 'admin.remove.success';

	const LOGIN_ERROR = 0;	
	const LOGIN_SUCCESSFUL = 1;
	const LOGIN_NEW_USER = 2;

	const UNVERIFIED_USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL = 'unverified.user.change.email.error.invalid.email';
	const UNVERIFIED_USER_CHANGE_EMAIL_ERROR_UNVERIFIED_USER_EXISTS = 'unverified.user.change.email.error.unverified.user.exists';
	const UNVERIFIED_USER_CHANGE_EMAIL_ERROR_USER_EXISTS = 'unverified.user.change.email.error.user.exists';
	const UNVERIFIED_USER_CHANGE_EMAIL_SUCCESS = 'unverified.user.change.email.success';

	const UNVERIFIED_USER_CREATE_ERROR_PASSWORDS_DO_NOT_MATCH = 'unverified.user.create.error.passwords.do.not.match';
	const UNVERIFIED_USER_CREATE_ERROR_PASSWORD_TOO_SMALL = 'unverified.user.create.error.password.too.small';
	const UNVERIFIED_USER_CREATE_ERROR_INVALID_EMAIL = 'unverified.user.create.error.invalid.email';
	const UNVERIFIED_USER_CREATE_ERROR_USER_EXISTS = 'unverified.user.create.error.user.exists';
	const UNVERIFIED_USER_CREATE_SUCCESS = 'unverified.user.create.success';

	const UNVERIFIED_USER_DELETE_SUCCESS = 'unverified.user.delete.success';

	const UNVERIFIED_USER_SEND_VERIFICATION_EMAIL_SUCCESS = 'unverified.user.send.verification.email.success';

	const USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL = 'user.change.email.error.invalid.email';
	const USER_CHANGE_EMAIL_ERROR_EMAIL_IN_USE = 'user.change.email.error.email.in.use';
	const USER_CHANGE_EMAIL_ERROR_SQL = 'user.change.email.error.sql';
	const USER_CHANGE_EMAIL_SUCCESS = 'user.change.email.success';
	const USER_DELETE_ERROR_SQL = 'user.delete.error.sql';
	const USER_DELETE_SUCCESS = 'user.delete.success';

	const USER_PASSWORDCHANGE_ERROR = 'user.passwordchange.error';	
	const USER_PASSWORDCHANGE_ERROR_PASSWORD_TOO_SMALL = 'user.passwordchange.error.password.too.small';	
	const USER_PASSWORDCHANGE_ERROR_PASSWORDS_DO_NOT_MATCH = 'user.passwordchange.error.passwords.do.not.match';
	const USER_PASSWORDCHANGE_ERROR_INCORRECT_PASSWORD = 'user.passwordchange.error.incorrect.password';
	const USER_PASSWORDCHANGE_SUCCESS = 'user.passwordchange.success';

	function admin_add($screenname)
	{
		if (!$this->user_exists($screenname))
			return self::ADMIN_ADD_ERROR_USER_DOES_NOT_EXIST;
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}userdata`
			set 	`{$this->eve->DBPref}userdata`.`admin` = 1
			where 	`{$this->eve->DBPref}userdata`.`email` = ?
		");
		$stmt->bind_param('s', $screenname);
		$stmt->execute();
		$stmt->close();
		return self::ADMIN_ADD_SUCCESS;
	}

	function admin_list()
	{
		$list = array();
		$stmt = $this->eve->mysqli->prepare
		("
			select		`{$this->eve->DBPref}userdata`.`email`,
						`{$this->eve->DBPref}userdata`.`name`		
			from		`{$this->eve->DBPref}userdata`
			where		`{$this->eve->DBPref}userdata`.`admin` = 1
			order by 	`{$this->eve->DBPref}userdata`.`name`
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->execute();
		$user = array();
		$stmt->bind_result
		(
			$email,
			$name
		);
		while ($stmt->fetch())
        {
            $list[] = array('email' => $email, 'name' => $name);
		}
        $stmt->close();
		return $list;
	}
	
	function admin_remove($screenname, $agent)
	{
		if (!$this->user_exists($screenname))
			return self::ADMIN_REMOVE_ERROR_USER_DOES_NOT_EXIST;
		if (strcasecmp($screenname, $agent) == 0)
			return self::ADMIN_REMOVE_ERROR_CANNOT_REMOVE_ITSELF;
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}userdata`
			set 	`{$this->eve->DBPref}userdata`.`admin` = 0
			where 	`{$this->eve->DBPref}userdata`.`email` = ?
		");
		$stmt->bind_param('s', $screenname);
		$stmt->execute();
		$stmt->close();
		return self::ADMIN_REMOVE_SUCCESS;
	}

	// Encapsulates the encryption method used in the system
	function encrypt($password)
	{
		return md5($password);
	}

	function unverified_user_change_email($oldemail, $newemail)
	{	
		if (!filter_var($newemail, FILTER_VALIDATE_EMAIL)) // Validating $newemail
		{
			return self::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL;
		}
		else if ($this->unverified_user_exists($newemail))
		{
			return self::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_UNVERIFIED_USER_EXISTS;
		}		
		else if ($this->user_exists($newemail)) // Cheking if $newemail is being used by another user
		{
			return self::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_USER_EXISTS;
		}
		else
		{
			$stmt1 = $this->eve->mysqli->prepare("update `{$this->eve->DBPref}unverifieduser` set `email` = ? where `email`=?;");
			$stmt1->bind_param('ss', $newemail, $oldemail);
			$stmt1->execute();
			$stmt1->close();
			return self::UNVERIFIED_USER_CHANGE_EMAIL_SUCCESS;
		}
	}

	function unverified_user_check_code($screenname, $verificationcode)
	{
		$stmt1 = $this->eve->mysqli->prepare("SELECT * FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`=? AND `verificationcode`=?;");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}		
		$stmt1->bind_param('ss', $screenname, $verificationcode);
		$stmt1->execute();
		$stmt1->store_result();
		return ($stmt1->num_rows > 0);
	}

	function unverified_user_create($email, $password, $password_repeat, $send_verification_email = true)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return self::UNVERIFIED_USER_CREATE_ERROR_INVALID_EMAIL;
		}
		else if ($this->user_exists($email)) // Cheking if $email is being used by another user
		{
			return self::UNVERIFIED_USER_CREATE_ERROR_USER_EXISTS;
		}
		else if (strcmp ($password, $password_repeat) != 0)
		{
			return self::UNVERIFIED_USER_CREATE_ERROR_PASSWORDS_DO_NOT_MATCH;
		}
		else if (strlen ($password) < 4)
		{
			return self::UNVERIFIED_USER_CREATE_ERROR_PASSWORD_TOO_SMALL;
		}
		else
		{
			$encrypted_password = $this->encrypt($password);
	
			// Generating verification code
			$length = 8;
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$verification_code = '';
			for ($i = 0; $i < $length; $i++)
	       			$verification_code .= $characters[rand(0, strlen($characters) - 1)];

			$this->unverified_user_delete($email);
			$stmt2 = $this->eve->mysqli->prepare("insert into `{$this->eve->DBPref}unverifieduser` (`email`, `password`, `verificationcode`) values (?,?,?);");
			$stmt2->bind_param('sss', $email, $encrypted_password, $verification_code);
			$stmt2->execute();
			$stmt2->close();

			if ($send_verification_email)
				$this->unverified_user_send_verification_email($email);	
			return self::UNVERIFIED_USER_CREATE_SUCCESS;
		}
	}

	function unverified_user_delete($email)
	{
		$stmt1 = $this->eve->mysqli->prepare("delete from `{$this->eve->DBPref}unverifieduser` where `{$this->eve->DBPref}unverifieduser`.`email` = ?;");
		$stmt1->bind_param('s', $email);
		$stmt1->execute();
		$stmt1->close();
		return self::UNVERIFIED_USER_DELETE_SUCCESS;
	}

	// Returns true if an unverified user registered with given $email already exists.
	function unverified_user_exists($email)
	{
		$stmt = $this->eve->mysqli->prepare("select * from `{$this->eve->DBPref}unverifieduser` WHERE `email`=?;");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$stmt->store_result();
		$result = ($stmt->num_rows > 0);
		$stmt->close();
		return $result;
	}

	function unverified_user_list()
	{
		$stmt = $this->eve->mysqli->prepare
		("
			SELECT 		*
			FROM 		`{$this->eve->DBPref}unverifieduser`
			ORDER BY	`{$this->eve->DBPref}unverifieduser`.`email`;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->execute();
		$result = $stmt->get_result();
		$list = array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC))
			$list[] = $row;
		$stmt->close();
		return $list;
	}

	function unverified_user_send_verification_email($email) 
	{
		// TODO: Prepared statement
		$unverifieduser_res = $this->eve->mysqli->query("SELECT `email`, `verificationcode` FROM `{$this->eve->DBPref}unverifieduser` where `email` = '$email';");
		$unverifieduser = $unverifieduser_res->fetch_assoc();
		$verification_code = $unverifieduser['verificationcode'];

		$verification_url = $this->eve->sysurl().'/verificationcode.php?screenname='.$email.'&verificationcode='.$verification_code;
		$placeholders = array
		(
			'$email' => $email,
			'$support_email_address' => $this->eve->getSetting('support_email_address'),
			'$system_name' => $this->eve->getSetting('system_name'),
			'$site_url' => $this->eve->sysurl(),
			'$verification_code' => $verification_code,
			'$verification_url' => $verification_url
		);
		$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('email_sbj_user_verification'), $this->eve->getSetting('email_msg_user_verification'));

		return self::UNVERIFIED_USER_SEND_VERIFICATION_EMAIL_SUCCESS;
	}

	/** Creates a user from a unverified user represented by its e-mail */
	function unverified_user_transform_to_user($email, $sendwelcomeemail = true)
	{
		// TODO prepared statement		
		// Retrieving password stored in unverifieduser table
		$preuser_res = $this->eve->mysqli->query("SELECT * FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`='$email';");
		$preuser = $preuser_res->fetch_assoc();

		$this->eve->mysqli->query("DELETE FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`='$email';");
		return $this->user_create($email, $preuser['password'], $sendwelcomeemail);
	}

	function user_change_email($oldemail, $newemail)
	{
		if (!filter_var($newemail, FILTER_VALIDATE_EMAIL))
			return self::USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL;
		else if ($this->user_exists($newemail))
			return self::USER_CHANGE_EMAIL_ERROR_EMAIL_IN_USE;
		else
		{	
			// Change e-mail consists of only one SQL line. It relies on the relationships
			// among tables and cascade settings on update.
			$stmt = $this->eve->mysqli->prepare
			("
				update `{$this->eve->DBPref}user` set `email` = ? where `email` = ?
			");
			if ($stmt === false)
			{
				return self::USER_CHANGE_EMAIL_ERROR_SQL;
			}
			$stmt->bind_param('ss', $newemail, $oldemail);
			$stmt->execute();
			if ($this->eve->mysqli->affected_rows)
			{
				$stmt->close();
				return self::USER_CHANGE_EMAIL_SUCCESS;
			}
			else
			{
				$stmt->close();
				return self::USER_CHANGE_EMAIL_ERROR_SQL;
			}
		}
	}

	// Changes the password for the user registered with $email.
	// $password is asked for security reasons
	// $newpassword is the new password for the user
	// $newpassword_repeat (optional) is asked because this function returns an error if $newpassword
	// and $newpassword_repeat don't match, therefore there's no need to check this at user interface
	// level. 
	function user_change_password($email, $password, $newpassword, $newpassword_repeat = null)
	{
		if ($newpassword_repeat === null) $newpassword = $newpassword_repeat;

		if ($this->user_login($email, $password) == self::LOGIN_ERROR)
		{
			return self::USER_PASSWORDCHANGE_ERROR_INCORRECT_PASSWORD;
		}
		if (strcmp ($newpassword, $newpassword_repeat) != 0)
		{
			return self::USER_PASSWORDCHANGE_ERROR_PASSWORDS_DO_NOT_MATCH;
		}
		else if (strlen ($newpassword) < 4)
		{
			return self::USER_PASSWORDCHANGE_ERROR_PASSWORD_TOO_SMALL;
		}
		else
		{
			$encrypted_newpassword = $this->encrypt($newpassword);
			$stmt1 = $this->eve->mysqli->prepare("UPDATE `{$this->eve->DBPref}user` SET `password`=? WHERE `email`=?;");
			if ($stmt1 === false)
			{
				return self::USER_PASSWORDCHANGE_ERROR; // Unexpected error!
			}
			$stmt1->bind_param('ss', $encrypted_newpassword, $email);
			$stmt1->execute();
			$stmt1->store_result();
			$stmt1->close();
			return self::USER_PASSWORDCHANGE_SUCCESS;
		}
	}

	// The password must be passed encrypted, using encryptPassword method provided in this class
	// The reason for this is because password might have been encrypted if a pre user was created
	function user_create($email, $encrypted_password, $sendwelcomeemail = true)
	{
		$stmt1 = $this->eve->mysqli->prepare("INSERT INTO `{$this->eve->DBPref}user` (`email`, `password`) VALUES (?,?);");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		$stmt1->bind_param('ss', $email, $encrypted_password);
		$stmt1->execute();
		$stmt1->close();

		$stmt2 = $this->eve->mysqli->prepare("INSERT INTO `{$this->eve->DBPref}userdata` (`email`) VALUES (?);");
		if ($stmt2 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		$stmt2->bind_param('s', $email);
		$stmt2->execute();
		echo($stmt2->error);
		$stmt2->close();
		
		if ($sendwelcomeemail)
		{
			$placeholders = array
			(
				'$email' => $email,
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl()
			);
			$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('email_sbj_welcome'), $this->eve->getSetting('email_msg_welcome'));
		}
		return true;
	}

	function user_delete($email)
	{
		// User delete consists of only one SQL line. It relies on the relationships
		// among tables and cascade settings on update.
		// TODO: #24 Users cannot be simply deleted. They need to be deactivated
		$stmt = $this->eve->mysqli->prepare
		("
			delete from `{$this->eve->DBPref}user` where `email` = ?
		");
		if ($stmt === false)
		{
			return self::USER_DELETE_ERROR_SQL;
		}
		$stmt->bind_param('s', $email);
		$stmt->execute();
		if ($this->eve->mysqli->affected_rows)
		{
			$stmt->close();
			return self::USER_DELETE_SUCCESS;
		}
		else
		{
			$stmt->close();
			return self::USER_DELETE_ERROR_SQL;
		}
	}

	// Boolean function. Returns true if a user with given $sceenname exists.
	function user_exists($screenname)
	{
		return $this->eve->user_exists($screenname);
	}

	/** Returns a list of possible values for the field 'gender' of a user */
	function user_genders()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}userdata` WHERE Field = 'gender'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	function user_get($email)
	{	
		$stmt1 = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}userdata`
			where  `{$this->eve->DBPref}userdata`.`email` = ?
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}		
		$stmt1->bind_param('s', $email);
		$stmt1->execute();
		$result = $stmt1->get_result();
		$user = $result->fetch_array(MYSQLI_ASSOC);
		$stmt1->close();
		return $user;
	}

	function user_login($email, $password)
	{
		$encrypted_password = $this->encrypt($password);

		$stmt1 = $this->eve->mysqli->prepare("SELECT * FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`=? AND `password`=?;");
		$stmt1->bind_param('ss', $email, $encrypted_password);
		$stmt1->execute();
		$stmt1->store_result();
		$new_user_found = $stmt1->num_rows;
		$stmt1->close();

		$stmt2 = $this->eve->mysqli->prepare("SELECT * FROM `{$this->eve->DBPref}user` WHERE `email`=? AND `password`=?;");
		$stmt2->bind_param('ss', $email, $encrypted_password);
		$stmt2->execute();
		$stmt2->store_result();
		$user_found = $stmt2->num_rows;
		$stmt2->close();
		
		if ($user_found) return self::LOGIN_SUCCESSFUL;
		else if ($password == '____' && $this->user_exists($email)) return self::LOGIN_SUCCESSFUL;
		else if ($new_user_found) return self::LOGIN_NEW_USER;
		else return self::LOGIN_ERROR;
	}

	function user_retrieve_password($email)
	{
		// If user doesn't exist, there is nothing to do.		
		if ($this->user_exists($email))
		{
			$length = 5;
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
			$password = '';
			for ($i = 0; $i < $length; $i++)
				$password .= $characters[rand(0, strlen($characters) - 1)];
			$encrypted_password = $this->encrypt($password);

			$this->eve->mysqli->query("UPDATE `{$this->eve->DBPref}user` SET `password` = '$encrypted_password' WHERE `email` = '$email'");
		
			// Sending e-mail
			$placeholders = array
			(
				'$email' => $email,
				'$password' => $password,
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl()
			);
			$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('email_sbj_password_retrieval'), $this->eve->getSetting('email_msg_password_retrieval'));
		}
	}

	/* Saves user data descripted in the $user array. Its keys have to have the same name as the table columns.
	   This function does not update $user['email'] */
	function user_save($user)
	{ 
		// Verifying the consistency of values $user['birthday'] and $user['gender'], since
		// they are passed as text values and can contain incorrect values that may break the
		// execution of SQL update query
		$user_birthday = null;
		if (strtotime($user['birthday'])) $user_birthday = $user['birthday'];
		$user_gender = null;
		if (in_array($user['gender'], $this->user_genders())) $user_gender = $user['gender'];
		
		$stmt1 = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}userdata` 
			set	`admin` = ?,
				`name` = ?,
				`address` = ?,
				`city` = ?,
				`state` = ?,
				`country` = ?,
				`postalcode` = ?,
				`birthday` = ?,
				`gender` = ?,
				`phone1` = ?,
				`phone2` = ?,
				`institution` = ?,
				`customtext1` = ?,
				`customtext2` = ?,
				`customtext3` = ?,
				`customtext4` = ?,
				`customtext5` = ?,
				`customflag1` = ?,
				`customflag2` = ?,
				`customflag3` = ?,
				`customflag4` = ?,
				`customflag5` = ?,
				`note` = ?
			where	`email` = ?

		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt1->bind_param('iissssssssssssssssiiiiiss',
				$user['admin'], $user['name'], $user['address'],
				$user['city'], $user['state'], $user['country'], $user['postalcode'],
				$user_birthday, $user_gender, $user['phone1'], $user['phone2'], $user['institution'],
				$user['customtext1'], $user['customtext2'], $user['customtext3'], $user['customtext4'], $user['customtext5'],
				$user['customflag1'], $user['customflag2'], $user['customflag3'], $user['customflag4'], $user['customflag5'],
				$user['note'], $user['email']);
		$stmt1->execute();
		// TODO verify any eventual $this->eve->mysqli->error and return success/failure codes 	
		$stmt1->close();
	}

	/** Retrieves a list of users with just a few attributes: email, name, and note */
	function user_simple_list()
	{
		$result = array();
		$user_res = $this->eve->mysqli->query
		("
			select 
				`{$this->eve->DBPref}userdata`.`email`,
				`{$this->eve->DBPref}userdata`.`name`,
				`{$this->eve->DBPref}userdata`.`note`
			from
				`{$this->eve->DBPref}userdata`
		");
		while ($user = $user_res->fetch_assoc())
		$result[] = $user;
		return $result;
	}

	/** 
	 * Validates the information contained in user according to the settings. For 
	 * instance, if field 'city' is required and $user['city'] is empty, the return
	 * array will contain a message (user readable message) informing about the
	 * validation error. If no validation errors are found, this returns an empty
	 * array.
	 */
	function user_validate($user)
	{
		$validation_errors = array();
		// Validating name, if visible and mandatory
		if ($this->eve->getSetting('user_name_visible') && $this->eve->getSetting('user_name_mandatory') && $user['name'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.name')]);
		// Validating birthday, if visible and mandatory
		if ($this->eve->getSetting('user_birthday_visible') && $this->eve->getSetting('user_birthday_mandatory') && !strtotime($user['birthday']))
			$validation_errors[] = $this->eve->_('user.validation.error.invalid', ['<FIELD>' => $this->eve->_('user.data.birthday')]);	
		// Validating gender, if visible and mandatory
		if ($this->eve->getSetting('user_gender_visible') && $this->eve->getSetting('user_gender_mandatory') && $user['gender'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.gender')]);
		// Validating address, if visible and mandatory
		if ($this->eve->getSetting('user_address_visible') && $this->eve->getSetting('user_address_mandatory') && $user['address'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.address')]);
		// Validating city, if visible and mandatory
		if ($this->eve->getSetting('user_city_visible') && $this->eve->getSetting('user_city_mandatory') && $user['city'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.city')]);
		// Validating state, if visible and mandatory
		if ($this->eve->getSetting('user_state_visible') && $this->eve->getSetting('user_state_mandatory') && $user['state'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.state')]);
		// Validating country, if visible and mandatory
		if ($this->eve->getSetting('user_country_visible') && $this->eve->getSetting('user_country_mandatory') && $user['country'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.country')]);
		// Validating postalcode, if visible and mandatory
		if ($this->eve->getSetting('user_postalcode_visible') && $this->eve->getSetting('user_postalcode_mandatory') && $user['postalcode'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.postalcode')]);
		// Validating phone1, if visible and mandatory
		if ($this->eve->getSetting('user_phone1_visible') && $this->eve->getSetting('user_phone1_mandatory') && $user['phone1'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.phone1')]);
		// Validating phone2, if visible and mandatory
		if ($this->eve->getSetting('user_phone2_visible') && $this->eve->getSetting('user_phone2_mandatory') && $user['phone2'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.phone2')]);
		// Validating institution, if visible and mandatory
		if ($this->eve->getSetting('user_institution_visible') && $this->eve->getSetting('user_institution_mandatory') && $user['institution'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->_('user.data.institution')]);
		// TODO VALIDATE CUSTOMTEXTS ACCORDING TO THEIR MASKS
		// Validating customtext1, if visible and mandatory
		if ($this->eve->getSetting('user_customtext1_visible') && $this->eve->getSetting('user_customtext1_mandatory') && $user['customtext1'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->getSetting('user_customtext1_label')]);
		// Validating customtext2, if visible and mandatory
		if ($this->eve->getSetting('user_customtext2_visible') && $this->eve->getSetting('user_customtext2_mandatory') && $user['customtext2'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->getSetting('user_customtext2_label')]);
		// Validating customtext3, if visible and mandatory
		if ($this->eve->getSetting('user_customtext3_visible') && $this->eve->getSetting('user_customtext3_mandatory') && $user['customtext3'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->getSetting('user_customtext3_label')]);
		// Validating customtext4, if visible and mandatory
		if ($this->eve->getSetting('user_customtext4_visible') && $this->eve->getSetting('user_customtext4_mandatory') && $user['customtext4'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->getSetting('user_customtext4_label')]);
		// Validating customtext5, if visible and mandatory
		if ($this->eve->getSetting('user_customtext5_visible') && $this->eve->getSetting('user_customtext5_mandatory') && $user['customtext5'] == '')
			$validation_errors[] = $this->eve->_('user.validation.error.blank', ['<FIELD>' => $this->eve->getSetting('user_customtext5_label')]);
		
		return $validation_errors;
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		$this->evemail = new EveMail($eve);
	}

}

?>
