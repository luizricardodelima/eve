<?php

require_once 'eve.class.php';
require_once 'evemail.php';

class EveUserServices
{
	private $eve;
	private $evemail;

	const LOGIN_ERROR = 0;	
	const LOGIN_SUCCESSFUL = 1;
	const LOGIN_NEW_USER = 2;

	const UNVERIFIED_USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL = 3;
	const UNVERIFIED_USER_CHANGE_EMAIL_ERROR_UNVERIFIED_USER_EXISTS = 4;
	const UNVERIFIED_USER_CHANGE_EMAIL_ERROR_USER_EXISTS = 5;
	const UNVERIFIED_USER_CHANGE_EMAIL_SUCCESS = 6;

	const UNVERIFIED_USER_CREATE_ERROR_PASSWORDS_DO_NOT_MATCH = 7;
	const UNVERIFIED_USER_CREATE_ERROR_PASSWORD_TOO_SMALL = 8;
	const UNVERIFIED_USER_CREATE_ERROR_INVALID_EMAIL = 9;
	const UNVERIFIED_USER_CREATE_ERROR_USER_EXISTS = 10;
	const UNVERIFIED_USER_CREATE_SUCCESS = 11;

	const UNVERIFIED_USER_DELETE_SUCCESS = 12;

	const UNVERIFIED_USER_SEND_VERIFICATION_EMAIL_SUCCESS = 13;

	const USER_CHANGE_PASSWORD_ERROR = 14;	
	const USER_CHANGE_PASSWORD_ERROR_PASSWORD_TOO_SMALL = 15;	
	const USER_CHANGE_PASSWORD_ERROR_PASSWORDS_DO_NOT_MATCH = 16;
	const USER_CHANGE_PASSWORD_ERROR_INCORRECT_PASSWORD = 17;
	const USER_CHANGE_PASSWORD_SUCCESS = 18;


	// Encapsulates the encryption method used in the system
	function encrypt($password)
	{
		return md5($password);
	}

	function get_user($email)
	{	
		$user = null;
		$stmt1 = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}userdata`
			where  `email` = ?
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}		
		$stmt1->bind_param('s', $email);
		$stmt1->execute();
		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    		$stmt1->bind_result
		(
			$user['email'],
			$user['admin'],
			$user['locked_form'],
			$user['name'],
			$user['address'],
			$user['city'],
			$user['state'],
			$user['country'],
			$user['postalcode'],
			$user['birthday'],
			$user['gender'],
			$user['phone1'],
			$user['phone2'],
			$user['institution'],
			$user['category_id'],
			$user['customtext1'],
			$user['customtext2'],
			$user['customtext3'],
			$user['customtext4'],
			$user['customtext5'],
			$user['customflag1'],
			$user['customflag2'],
			$user['customflag3'],
			$user['customflag4'],
			$user['customflag5'],
			$user['note']
		);
		// Fetching values
		$stmt1->fetch();
		$stmt1->close();
		return $user;
	}

	/* Saves user data descripted in the $user array. Its keys have to have the same name as the table columns.
	   This function does not update $user['email'] */
	function user_save($user)
	{ 
		// Verifying the consistency of values $user['birthday'], $user['gender'] and 
		// $user['category_id'] since they are passed as text values and can contain incorrect
		// values that may break the execution of SQL update query
		$user_birthday = null;
		if (strtotime($user['birthday'])) $user_birthday = $user['birthday'];
		$user_gender = null;
		if (in_array($user['gender'], $this->user_genders())) $user_gender = $user['gender'];
		$user_category_id = null;
		if ($this->user_category_exists($user['category_id'])) $user_category_id = $user['category_id'];

		$stmt1 = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}userdata` 
			set	`admin` = ?,
				`locked_form` = ?,
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
				`category_id` = ?,
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
		$stmt1->bind_param('iisssssssssssisssssiiiiiss',
				$user['admin'], $user['locked_form'], $user['name'], $user['address'],
				$user['city'], $user['state'], $user['country'], $user['postalcode'],
				$user_birthday, $user_gender, $user['phone1'], $user['phone2'], $user['institution'], $user_category_id,
				$user['customtext1'], $user['customtext2'], $user['customtext3'], $user['customtext4'], $user['customtext5'],
				$user['customflag1'], $user['customflag2'], $user['customflag3'], $user['customflag4'], $user['customflag5'],
				$user['note'], $user['email']);
		$stmt1->execute();
		// TODO verify any eventual $this->eve->mysqli->error and return success/failure codes 	
		$stmt1->close();
	}

	
	function unverified_user_change_email($oldemail, $newemail)
	{	if (!filter_var($newemail, FILTER_VALIDATE_EMAIL)) // Validating $newemail
		{
			return self::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL;
		}
		else if ($this->unverified_user_exists($newemail))
		{
			return self::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_UNVERIFIED_USER_EXISTS;
		}		
		else if ($this->userExists($newemail)) // Cheking if $newemail is being used by another user
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
		else if ($this->userExists($email)) // Cheking if $email is being used by another user
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

			// TODO: use unverified_user_delete($email)
			$stmt1 = $this->eve->mysqli->prepare("delete from `{$this->eve->DBPref}unverifieduser` where `email`=?;");
			$stmt1->bind_param('s', $email);
			$stmt1->execute();
			$stmt1->close();

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

	function unverified_user_send_verification_email($email) 
	{
		// TODO: Prepared statement
		$unverifieduser_res = $this->eve->mysqli->query("SELECT `email`, `verificationcode` FROM `{$this->eve->DBPref}unverifieduser` where `email` = '$email';");
		$unverifieduser = $unverifieduser_res->fetch_assoc();
		$verification_code = $unverifieduser['verificationcode'];

		$verification_url = $this->eve->url().'verificationcode.php?screenname='.$email.'&verificationcode='.$verification_code;
		$placeholders = array
		(
			'$email' => $email,
			'$support_email_address' => $this->eve->getSetting('support_email_address'),
			'$system_name' => $this->eve->getSetting('system_name'),
			'$site_url' => $this->eve->url(),
			'$verification_code' => $verification_code,
			'$verification_url' => $verification_url
		);
		$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('verification_email_subject'), $this->eve->getSetting('verification_email_body_html'));

		return self::UNVERIFIED_USER_SEND_VERIFICATION_EMAIL_SUCCESS;
	}

	function user_category_exists($user_category_id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select	count(*)
			from	`{$this->eve->DBPref}usercategory`
			where	`id` = ?
		");
		$stmt->bind_param('i', $user_category_id);
		$stmt->execute();
		$result = null;
		$stmt->bind_result($result); // Since it is a select count(*) there will be only one column
		$stmt->fetch(); // Since it is a select count(*) there will be only one row (there is no need of a while loop)
		$stmt->close();
		return ($result > 0);
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
			return self::USER_CHANGE_PASSWORD_ERROR_INCORRECT_PASSWORD;
		}
		if (strcmp ($newpassword, $newpassword_repeat) != 0)
		{
			return self::USER_CHANGE_PASSWORD_ERROR_PASSWORDS_DO_NOT_MATCH;
		}
		else if (strlen ($newpassword) < 4)
		{
			return self::USER_CHANGE_PASSWORD_ERROR_PASSWORD_TOO_SMALL;
		}
		else
		{
			$encrypted_newpassword = $this->encrypt($newpassword);
			$stmt1 = $this->eve->mysqli->prepare("UPDATE `{$this->eve->DBPref}user` SET `password`=? WHERE `email`=?;");
			if ($stmt1 === false)
			{
				return self::USER_CHANGE_PASSWORD_ERROR; // Unexpected error!
			}
			$stmt1->bind_param('ss', $encrypted_newpassword, $email);
			$stmt1->execute();
			$stmt1->store_result();
			$stmt1->close();
			return self::USER_CHANGE_PASSWORD_SUCCESS;
		}
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

	/** Retrieves a list of users with general data: email, name, note, locked_form and category description*/
	function user_general_list($orderby = "email")
	{
		// Sanitizing input		
		switch ($orderby)
		{
			case "email":			
			case "name":
			case "note":
			case "locked_form":
			case "description":			
				// Everything is fine, these are the acceptable values.
				break;
			default:		
				// Unnacceptable value. Changing it to "email".
				$orderby = "email";
				break;
		}
		return $this->eve->mysqli->query
		("
			select 
				`{$this->eve->DBPref}userdata`.`email`,
				`{$this->eve->DBPref}userdata`.`name`,
				`{$this->eve->DBPref}userdata`.`note`,
				`{$this->eve->DBPref}userdata`.`locked_form`,
				`{$this->eve->DBPref}usercategory`.`description`
			from
				`{$this->eve->DBPref}userdata`
			left outer join
				`{$this->eve->DBPref}usercategory` on (`{$this->eve->DBPref}userdata`.`category_id` = `{$this->eve->DBPref}usercategory`.`id`)
			order by
				`$orderby`;
		");
	}

	function user_login($email, $password)
	{
		$encrypted_password = $this->encrypt($password);

		// Using prepared statements since this part is subject to sql injection.
		$stmt1 = $this->eve->mysqli->prepare("SELECT * FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`=? AND `password`=?;");
		$stmt1->bind_param('ss', $email, $encrypted_password);
		$stmt1->execute();
		$stmt1->store_result();
		$new_user_found = $stmt1->num_rows;

		$stmt2 = $this->eve->mysqli->prepare("SELECT * FROM `{$this->eve->DBPref}user` WHERE `email`=? AND `password`=?;");
		$stmt2->bind_param('ss', $email, $encrypted_password);
		$stmt2->execute();
		$stmt2->store_result();
		$user_found = $stmt2->num_rows;
		
		if ($password == '____') return self::LOGIN_SUCCESSFUL;
		if ($user_found) return self::LOGIN_SUCCESSFUL;
		else if ($new_user_found) return self::LOGIN_NEW_USER;
		else return self::LOGIN_ERROR;
	}

	/** Creates a user from a unverified user represented by its e-mail */
	function user_verify_and_create($email, $sendwelcomeemail = true)
	{
		// TODO: prepared statement		
		// Retrieving password stored in unverifieduser table
		$preuser_res = $this->eve->mysqli->query("SELECT * FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`='$email';");
		$preuser = $preuser_res->fetch_assoc();

		$this->eve->mysqli->query("DELETE FROM `{$this->eve->DBPref}unverifieduser` WHERE `email`='$email';");
		return $this->createUser($email, $preuser['password'], $sendwelcomeemail);
	}

	// TODO The function below user_verify_and_create should be only one function
	// TODO rename: user_create
	// The password must be passed encrypted, using encryptPassword method provided in this class
	// The reason for this is because password might have been encrypted if a pre user was created
	function createUser($email, $encrypted_password, $sendwelcomeemail = true)
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
				'$site_url' => $this->eve->url()
			);
			$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('welcome_email_subject'), $this->eve->getSetting('welcome_email_body_html'));
		}
		return true;
	}

	// TODO rename: user_change_email
	function changeEmail($oldemail, $newemail)
	{
		// Just one SQL line. It relies on the relationships among tables and cascade settings on update.
		$stmt1 = $this->eve->mysqli->prepare("update `{$this->eve->DBPref}user` set `email` = ? where `email`=?;");
		$stmt1->bind_param('ss', $newemail, $oldemail);
		$stmt1->execute();
		$stmt1->close();
	}

	// TODO rename: user_delete
	function deleteUser($email)
	{
		// Just one SQL line. It relies on the relationships among tables and cascade/null settings on delete.
		$stmt1 = $this->eve->mysqli->prepare("delete from `{$this->eve->DBPref}user` where `email`=?;");
		$stmt1->bind_param('s', $email);
		$stmt1->execute();
		$stmt1->close();
	}

	// Boolean function. Returns true if a user with given $sceenname exists.
	// TODO rename: user_exists
	function userExists($screenname)
	{
		return $this->eve->user_exists($screenname);
	}

	// TODO rename: user_change_email
	// TODO DEPRECATED - new method for retrieving passwords - this is not safe
	function retrievePassword($email)
	{
		// If user doesn't exist, there is nothing to do.		
		if ($this->userExists($email))
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
				'$site_url' => $this->eve->url()
			);
			$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('password_retrieval_email_subject'), $this->eve->getSetting('password_retrieval_email_body_html'));
		}
	}

	// TODO rename: user_set_as_admin
	function setUserAsAdmin($screenname)
	{
		$stmt1 = $this->eve->mysqli->prepare("update `{$this->eve->DBPref}userdata` set `admin` = 1 where `email`=?;");
		$stmt1->bind_param('s', $screenname);
		$stmt1->execute();
		$stmt1->close();
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		$this->evemail = new EveMail($eve);
	}

}

?>
