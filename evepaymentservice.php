<?php

require_once 'eve.class.php';
require_once 'evemail.php';

class EvePaymentService
{
	//TODO create a Payment object with native objects not strings, pass this object on register payment and validate it on its creation.
	private $eve;
	private $evemail;

	const PAYMENT_ERROR = 0;	
	const PAYMENT_SUCCESSFUL = 1;
	const PAYMENT_SUCCESSFUL_WITH_EMAIL_ALERT = 2;

	const PAYMENT_OPTION_CREATE_ERROR_SQL = 'payment.option.create.error.sql';
	const PAYMENT_OPTION_CREATE_SUCCESS = 'payment.option.create.success';
	const PAYMENT_OPTION_DELETE_ERROR_SQL = 'payment.option.delete.error.sql';
	const PAYMENT_OPTION_DELETE_SUCCESS = 'payment.option.delete.success';
	
	function perform_payment($screenname, $payment_method, $date, $note, $value_paid, $value_received, $items = null)
	{
		// Default case, to be changed if a successful payment occurs
		$result = self::PAYMENT_ERROR;

		// If there's no associated payment, create one
		$stmt = $this->eve->mysqli->prepare("SELECT * FROM `{$this->eve->DBPref}payment` WHERE `email`=?;");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return self::PAYMENT_ERROR;
		}
		$stmt->bind_param('s', $screenname);
		$stmt->execute();
		$stmt->store_result();
		$payment_found = ($stmt->num_rows > 0);
		$stmt->close();
		if (!$payment_found)
		{
			$stmt1 = $this->eve->mysqli->prepare("INSERT INTO `{$this->eve->DBPref}payment` (`email`) VALUES (?);");
			if ($stmt1 === false)
			{
				trigger_error($this->eve->mysqli->error, E_USER_ERROR);
				return self::PAYMENT_ERROR;
			}
			$stmt1->bind_param('s', $screenname);
			$stmt1->execute();
			$stmt1->close();
		}
		
		$stmt2 = $this->eve->mysqli->prepare
		("
			UPDATE `{$this->eve->DBPref}payment`
			SET `payment_method` = ?, `value_paid` = ?, `value_received` = ?, `date` = ?, `note` = ?
			WHERE `email` = ?;
		");
		if ($stmt2 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return self::PAYMENT_ERROR;
		}
		$stmt2->bind_param('sddsss', $payment_method, $value_paid, $value_received, $date, $note, $screenname);
		if ($stmt2->execute())
			$result = self::PAYMENT_SUCCESSFUL;
		else
			$result = self::PAYMENT_ERROR;
		$stmt2->close();

		// Blocking user form if the setting says so
		if ($this->eve->getSetting('block_user_form') == 'after_payment')
		{
			$stmt3 = $this->eve->mysqli->prepare
			("
				UPDATE `{$this->eve->DBPref}userdata`
				SET `locked_form` = 1
				WHERE `email` = ?;
			");
			$stmt3->bind_param('s', $screenname);
			$stmt3->execute();
			$stmt3->close();
		}
		
		if ($result == self::PAYMENT_SUCCESSFUL && $this->eve->getSetting('email_snd_payment'))
		{
			$paymenttype_id_sql = intval($paymenttype_id); // Sanitizing value.
			// TODO USE PREPARED STATEMENTS - CREATE A METHOD IN THIS CLASS
			$paymenttype = $this->eve->mysqli->query("SELECT * FROM `{$this->eve->DBPref}paymenttype` where `id`=$paymenttype_id_sql;")->fetch_assoc();
			
			$placeholders = array
			(
				'$email' => $screenname,
				'$paymenttype_name' => $paymenttype['name'],
				'$paymenttype_description' => $paymenttype['description'],
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl()
			);
			$send_successful = $this->evemail->send_mail($screenname, $placeholders, $this->eve->getSetting('email_sbj_payment'), $this->eve->getSetting('email_msg_payment'));
			if ($send_successful) $result = self::PAYMENT_SUCCESSFUL_WITH_EMAIL_ALERT;
		}
		return $result;
	}

	function remove_payment($payment_id)
	{
		// Sanitizing input
		if (!is_numeric($payment_id)) return;
		
		// Updating DB
		// TODO Prepared statements
		$payment = $this->eve->mysqli->query("select * from `{$this->eve->DBPref}payment` where `{$this->eve->DBPref}payment`.`id` = $payment_id;")->fetch_assoc();
		$date_time = date("c");	
		$new_note = "PAYMENT DELETED AT $date_time BY {$_SESSION['screenname']}. This payment belonged to {$payment['email']}\n".$payment['note'];	
		$this->eve->mysqli->query("update `{$this->eve->DBPref}payment` set `{$this->eve->DBPref}payment`.`note` = '$new_note' where `{$this->eve->DBPref}payment`.`id` = $payment_id;");
		$this->eve->mysqli->query("update `{$this->eve->DBPref}payment` set `{$this->eve->DBPref}payment`.`email` = null where `{$this->eve->DBPref}payment`.`id` = $payment_id;");
		
		// Unblocking user form if the setting says so
		if ($this->eve->getSetting('block_user_form') == 'after_payment')
		{
			$stmt3 = $this->eve->mysqli->prepare
			("
				UPDATE `{$this->eve->DBPref}userdata`
				SET `locked_form` = 0
				WHERE `email` = ?;
			");
			$stmt3->bind_param('s', $payment['email']);
			$stmt3->execute();
			$stmt3->close();
		}

		if ($this->eve->getSetting('email_snd_payment'))
		{
			$placeholders = array
			(
				'$email' => $payment['email'],
				'$paymenttype_name' => $this->eve->_('paymenttype.name.null'),
				'$paymenttype_description' => $this->eve->_('paymenttype.description.null'),
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl()
			);
			$this->evemail->send_mail($payment['email'], $placeholders, $this->eve->getSetting('email_sbj_payment'), $this->eve->getSetting('email_msg_payment'));
		}
	}

	/* TODO IMPLEMENT
	function payment_get($id)
	{
	}
	*/
	
	function payment_get_by_user($email)
	{
		$payment = null;
		$stmt1 = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}payment`
			where  `user_email` = ?
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
			$id,
			$email_,
			$date,
			$paymenttype_id,
			$value_paid,
			$value_received,
			$note,
			$image
		);
		$payment = null;
		// Fetching values		
		if ($stmt1->fetch())
		{
			$payment = array();
			$payment['id'] = $id;
			$payment['email'] = $email;
			$payment['date'] = $date;
			$payment['paymenttype_id'] = $paymenttype_id;
			$payment['value_paid'] = $value_paid;
			$payment['value_received'] = $value_received;
			$payment['note'] = $note;
			$payment['image'] = $image;
		}
		$stmt1->close();
		return $payment;
	}

	function payment_list($order_by = 'name', $specific_emails = null)
	{
		$result = array();

		// TODO Remove SQL injection
		$where_sql_clause = '';
		if ($specific_emails !== null)
		{
			$where_sql_clause = "where `{$this->eve->DBPref}userdata`.`email` in ('" . implode("','",$specific_emails). "')";
		}

		$ordering = '';
		switch ($order_by)
		{
			case 'email':
				$ordering = "`{$this->eve->DBPref}userdata`.`email`";
				break;
			case 'payment-method':
				$ordering = "`{$this->eve->DBPref}payment`.`payment_method`";
				break;
			case 'value-paid':
				$ordering = "`{$this->eve->DBPref}payment`.`value_paid`";
				break;
			case 'value-received':
				$ordering = "`{$this->eve->DBPref}payment`.`value_received`";
				break;
			case 'date':
				$ordering = "`{$this->eve->DBPref}payment`.`date`";
				break;
			case 'note':
				$ordering = "`{$this->eve->DBPref}payment`.`note`";
				break;
			case 'name':
			default:
				$ordering = "`{$this->eve->DBPref}userdata`.`name`";
			break;
		}
		$resource = $this->eve->mysqli->query
		("	
			select 
				*
			from
				`{$this->eve->DBPref}userdata`
			left outer join
				`{$this->eve->DBPref}payment` on (`{$this->eve->DBPref}userdata`.`email` = `{$this->eve->DBPref}payment`.`user_email`)
			$where_sql_clause
			order by
				$ordering;
		");
		while ($item = $resource->fetch_assoc()) $result[] = $item;
		return $result;
	}

	function payment_option_create($name = "")
	{   
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}payment_option` (`name`) values (?)
		");
		if ($stmt === false)
		{
			return self::PAYMENT_OPTION_CREATE_ERROR_SQL;
		}
		$stmt->bind_param('s', $name);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::PAYMENT_OPTION_CREATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::PAYMENT_OPTION_CREATE_SUCCESS;
		}
	}

	function payment_option_delete($id)
	{	
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}payment_option`
			set		`{$this->eve->DBPref}payment_option`.`active` = 0
			where	`{$this->eve->DBPref}payment_option`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::PAYMENT_OPTION_DELETE_ERROR_SQL;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::PAYMENT_OPTION_DELETE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::PAYMENT_OPTION_DELETE_SUCCESS;
		}
	}

	function payment_option_get($id)
	{	// TODO ERROR MESSAGES
		$stmt1 = $this->eve->mysqli->prepare
		("
			select 	*
			from	`{$this->eve->DBPref}payment_option`
			where	`{$this->eve->DBPref}payment_option`.`id` = ?
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt1->bind_param('i', $id);		
		$stmt1->execute();

		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt1->bind_result
		(
			$id, $type, $name, $description, $value, $available_from,
			$available_to, $admin_only, $active
		);

		// Fetching values
		if ($stmt1->fetch())
		{	
			$stmt1->close();
			return array(
				'id' => $id, 'type' =>$type, 'name' => $name, 'description' => $description,
				'value' => $value, 'available_from' => $available_from, 'available_to' => $available_to,
				'admin_only' => $admin_only, 'active' => $active
			);
		}
		else
		{
			$stmt1->close();
			return null;
		}
	}


	function payment_option_list($id_as_array_key = false)
	{	// TODO ERROR MESSAGES
		$result = array();
		$stmt1 = $this->eve->mysqli->prepare
		("
			select *
			from		`{$this->eve->DBPref}payment_option`
			where		`{$this->eve->DBPref}payment_option`.`active` = 1
			order by	`{$this->eve->DBPref}payment_option`.`name`;
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}		
		$stmt1->execute();

		
		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt1->bind_result
		(
			$id, $type, $name, $description, $value, $available_from,
			$available_to, $admin_only, $active
		);
		// Fetching values
		while ($stmt1->fetch())
		{
			$payment_option = array(
				'id' => $id, 'type' =>$type, 'name' => $name, 'description' => $description,
				'value' => $value, 'available_from' => $available_from, 'available_to' => $available_to,
				'admin_only' => $admin_only, 'active' => $active
			);
			if ($id_as_array_key)
				$result[$id] = $payment_option;
			else
				$result[] = $payment_option;
		}
		$stmt1->close();
		return $result;
	}

	function payment_option_types()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}payment_option` WHERE Field = 'type'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	const PAYMENT_OPTION_UPDATE_SUCCESS = 'payment.option.update.success';
	const PAYMENT_OPTION_UPDATE_ERROR_SQL = 'payment.option.update.error.sql';

	function payment_option_update($payment_option)
	{	
		// Verifying the consistency of $payment_option['value'], 
		// $payment_option['available_from'] and $payment_option['available_to'] since
		// they arepassed as text. Any incorrect value may break the SQL query execution.
		$payment_option['value'] = floatval($payment_option['value']);
		$payment_option['available_from'] = DateTime::createFromFormat('Y-m-d', $payment_option['available_from']) ? $payment_option['available_from'] : null;
		$payment_option['available_to'] = DateTime::createFromFormat('Y-m-d', $payment_option['available_to']) ? $payment_option['available_to'] : null;
		
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}payment_option`
			set		`{$this->eve->DBPref}payment_option`.`type` = ?,
					`{$this->eve->DBPref}payment_option`.`name` = ?,
					`{$this->eve->DBPref}payment_option`.`description` = ?,
					`{$this->eve->DBPref}payment_option`.`value` = ?,
					`{$this->eve->DBPref}payment_option`.`available_from` = ?,
					`{$this->eve->DBPref}payment_option`.`available_to` = ?,
					`{$this->eve->DBPref}payment_option`.`admin_only` = ?
			where	`{$this->eve->DBPref}payment_option`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::PAYMENT_OPTION_UPDATE_ERROR_SQL;
		}
		$stmt->bind_param('sssdssii', $payment_option['type'], $payment_option['name'], $payment_option['description'], $payment_option['value'], $payment_option['available_from'], $payment_option['available_to'], $payment_option['admin_only'], $payment_option['id']);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::PAYMENT_OPTION_UPDATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::PAYMENT_OPTION_UPDATE_SUCCESS;
		}
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		$this->evemail = new EveMail($eve);
	}

}

?>
