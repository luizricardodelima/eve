<?php

require_once 'eve.class.php';
require_once 'evemail.class.php';

class EvePaymentService
{
	private $eve;
	private $evemail;

	const PAYMENT_ERROR = "payment.error";	
	const PAYMENT_ERROR_ID_NOT_RETRIEVED = "payment.error.id.not.retrieved";
	const PAYMENT_ERROR_SQL = "payment.error.sql";
	const PAYMENT_SUCCESSFUL = "payment.successful";
	const PAYMENT_SUCCESSFUL_WITH_EMAIL_ALERT = "payment.successful.with.email.alert";

	const PAYMENT_GROUP_CREATE_ERROR_SQL = 'payment.group.create.error.sql';
	const PAYMENT_GROUP_CREATE_SUCCESS = 'payment.group.create.success';
	const PAYMENT_GROUP_DELETE_ERROR_SQL = 'payment.group.delete.error.sql';
	const PAYMENT_GROUP_DELETE_SUCCESS = 'payment.group.delete.success';
	const PAYMENT_GROUP_UPDATE_ERROR_SQL = 'payment.group.update.error.sql';
	const PAYMENT_GROUP_UPDATE_SUCCESS = 'payment.group.update.success';

	const PAYMENT_OPTION_CREATE_ERROR_SQL = 'payment.option.create.error.sql';
	const PAYMENT_OPTION_CREATE_SUCCESS = 'payment.option.create.success';
	const PAYMENT_OPTION_DELETE_ERROR_SQL = 'payment.option.delete.error.sql';
	const PAYMENT_OPTION_DELETE_SUCCESS = 'payment.option.delete.success';
	const PAYMENT_OPTION_UPDATE_ERROR_SQL = 'payment.option.update.error.sql';
	const PAYMENT_OPTION_UPDATE_SUCCESS = 'payment.option.update.success';
	
	const PAYMENT_DELETE_ERROR_SQL = 'payment.delete.error.sql';
	const PAYMENT_DELETE_SUCCESS = 'payment.delete.success';
	
	// TODO Maybe payment does not need this restriction of one payment per user.

	/** 
	 * Returns the Payment id associated to the $screenname. If $create_new_if_not_found
	 * is true and no payment is found, the function creates a payment object associated
	 * to the $screenname and then returns the id. The function retuns null otherwise.
	 */
	function payment_get_id($screenname, $create_new_if_not_found = false)
	{
		$id = null;
		$stmt = $this->eve->mysqli->prepare
		("
			select	`{$this->eve->DBPref}payment`.`id`
			from	`{$this->eve->DBPref}payment`
			where	`{$this->eve->DBPref}payment`.`user_email` = ?
		");
		if ($stmt === false)
		{
			return null;
		}	
		$stmt->bind_param('s', $screenname);
		$stmt->execute();
		$stmt->bind_result($id_);
		if ($stmt->fetch())
		{
			$id = $id_;
			$stmt->close();
			return $id;
		}
		else if ($create_new_if_not_found)
		{
			$stmt->close();
			$stmt1 = $this->eve->mysqli->prepare
			("
				insert into `{$this->eve->DBPref}payment` (`user_email`)
				values (?)
			");
			if ($stmt1 === false)
			{
				return null;
			}
			$stmt1->bind_param('s', $screenname);
			$stmt1->execute();
			if (!empty($stmt1->error))
			{
				$stmt1->close();
				return null;
			}
			$id = $stmt1->insert_id;
			$stmt1->close();
			return $id;
		}
		else
		{
			return null;
		}
	}

	function payment_register($screenname, $payment_method, $date, $note, $value_paid, $value_received, $items = null)
	{
		// Default case, to be changed if a successful payment occurs
		$result = self::PAYMENT_ERROR;

		// Getting or creating payment id
		$id = $this->payment_get_id($screenname, true);
		if ($id === null) return self::PAYMENT_ERROR_ID_NOT_RETRIEVED;

		//Populating payment with data
		$stmt1 = $this->eve->mysqli->prepare
		("
			update 
				`{$this->eve->DBPref}payment`
			set
				`payment_method` = ?,
				`value_paid` = ?,
				`value_received` = ?,
				`date` = ?,
				`note` = ?
			where
				`id` = ?;
		");
		if ($stmt1 === false)
		{
			return self::PAYMENT_ERROR_SQL;
		}
		$stmt1->bind_param('sddssi', $payment_method, $value_paid, $value_received, $date, $note, $id);
		if ($stmt1->execute())
			$result = self::PAYMENT_SUCCESSFUL;
		else
			$result = self::PAYMENT_ERROR_SQL;
		$stmt1->close();

		// Inserting payment items
		$stmt2 = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}payment_item` (`payment_id`, `payment_option_id`)
			values (?, ?)
		");
		if ($stmt2 === false)
		{
			return self::PAYMENT_ERROR_SQL;
		}
		foreach ($items as $item)
		{
			$stmt2->bind_param('ii', $id, $item);
			$stmt2->execute();
			if (!empty($stmt2->error))
			{
				$stmt2->close();
				return self::PAYMENT_ERROR_SQL;
			}
		}
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

		if ($result == self::PAYMENT_SUCCESSFUL && $this->eve->getSetting('email_snd_payment_update'))
		{
			$date_formatter = new IntlDateFormatter($this->eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
			$money_formatter = new NumberFormatter($this->eve->getSetting('system_locale'), NumberFormatter::CURRENCY);

			$placeholders = array
			(
				'$email' => $screenname,
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl(),
				'$payment_date' => $date_formatter->format(strtotime($date)),
				'$payment_method' => $payment_method,
				'$payment_value_paid' => $money_formatter->format($value_paid)
			);
			$send_successful = $this->evemail->send_mail($screenname, $placeholders, $this->eve->getSetting('email_sbj_payment_update'), $this->eve->getSetting('email_msg_payment_update'));
			if ($send_successful) $result = self::PAYMENT_SUCCESSFUL_WITH_EMAIL_ALERT;
		}
		return $result;
	}

	function payment_delete($id, $send_email = 'true')
	{
		$payment = $this->payment_get($id);
		$agent = (isset($_SESSION['screenname'])) ? $_SESSION['screenname'] : "unknown user";
		$new_note = "Payment deleted at " . date("c") . " by $agent. This payment belonged to {$payment['user_email']}\n";

		$stmt1 = $this->eve->mysqli->prepare
		("
			update 	`{$this->eve->DBPref}payment`
			set 	`{$this->eve->DBPref}payment`.`note` = CONCAT(?, `{$this->eve->DBPref}payment`.`note`),
					`{$this->eve->DBPref}payment`.`user_email` = NULL
			where 	`{$this->eve->DBPref}payment`.`id` = ?;
		");
		if ($stmt1 === false)
		{
			return self::PAYMENT_DELETE_ERROR_SQL;
		}
		$stmt1->bind_param('si', $new_note, $id);
		$stmt1->execute();
		if (!empty($stmt1->error))
		{
			$stmt1->close();
			return self::PAYMENT_DELETE_ERROR_SQL;
		}
		$stmt1->close();

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

		if ($send_email)
		{
			$placeholders = array
			(
				'$email' => $payment['user_email'],
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl()
			);
			$this->evemail->send_mail($payment['user_email'], $placeholders, $this->eve->getSetting('email_sbj_payment_delete'), $this->eve->getSetting('email_msg_payment_delete'));
		}

		return self::PAYMENT_DELETE_SUCCESS;
	}

	function payment_get($id)
	{
		$stmt1 = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}payment`
			where  `{$this->eve->DBPref}payment`.`id` = ?
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}		
		$stmt1->bind_param('i', $id);
		$stmt1->execute();
		$payment = array();
		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt1->bind_result
		(
			$payment['id'],
			$payment['user_email'],
			$payment['date'],
			$payment['payment_method'],
			$payment['value_paid'],
			$payment['value_received'],
			$payment['note'],
			$payment['file']
		);
		// Fetching values		
		if ($stmt1->fetch())
		{
			$stmt1->close();
			return $payment;
		}
		else
		{
			$stmt1->close();
			return null;
		}
	}

	function payment_list($order_by = 'name', $specific_emails = null)
	{
		$result = array();

		// If $specific_emails is set as an array, code will sanitize input and
		// generate sql where clause
		$where_sql_clause = '';
		if (is_array($specific_emails))
		{
			foreach($specific_emails as $i => $specific_email)
				if (!filter_var($specific_email, FILTER_VALIDATE_EMAIL))
					unset($specific_emails[$i]);
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

	function payment_list_summary()
	{
		$result = array();
		$resource = $this->eve->mysqli->query
		("	
			select 
				`{$this->eve->DBPref}payment`.`payment_method` as `payment_method`,
				count(`{$this->eve->DBPref}userdata`.`email`) as `user_count`,
				sum(`{$this->eve->DBPref}payment`.`value_paid`) as `value_paid_sum`,
				sum(`{$this->eve->DBPref}payment`.`value_received`) as `value_received_sum`
			from
				`{$this->eve->DBPref}userdata`
			left outer join
				`{$this->eve->DBPref}payment` on (`{$this->eve->DBPref}userdata`.`email` = `{$this->eve->DBPref}payment`.`user_email`)
			group by
				`{$this->eve->DBPref}payment`.`payment_method`;
		");
		while ($item = $resource->fetch_assoc()) $result[] = $item;
		return $result;
	}

	/**
	 */
	function payment_group_list()
	{	// TODO ERROR MESSAGES
		$result = array();
		$stmt1 = $this->eve->mysqli->prepare
		("
			select *
			from		`{$this->eve->DBPref}payment_group`
			order by	`{$this->eve->DBPref}payment_group`.`id`;
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
			$id, $name, $unverified_payment_info, $verified_payment_info, $state
		);
		// Fetching values
		while ($stmt1->fetch())
		{
			$payment_group = array(
				'id' => $id, 'name' => $name, 'unverified_payment_info' => $unverified_payment_info,
				'verified_payment_info' => $verified_payment_info, 'state' => $state
			);
			$result[] = $payment_group;
		}
		$stmt1->close();
		return $result;
	}

	function payment_group_create($name = "")
	{   
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}payment_group` (`name`) values (?)
		");
		if ($stmt === false)
		{
			return self::PAYMENT_GROUP_CREATE_ERROR_SQL;
		}
		$stmt->bind_param('s', $name);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::PAYMENT_GROUP_CREATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::PAYMENT_GROUP_CREATE_SUCCESS;
		}
	}

	function payment_group_delete($id)
	{	
		$stmt = $this->eve->mysqli->prepare
		("
			delete from
					`{$this->eve->DBPref}payment_group`
			where	`{$this->eve->DBPref}payment_group`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::PAYMENT_GROUP_DELETE_ERROR_SQL;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::PAYMENT_GROUP_DELETE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::PAYMENT_GROUP_DELETE_SUCCESS;
		}
	}

	function payment_group_get($id)
	{	
		$stmt1 = $this->eve->mysqli->prepare
		("
			select 	*
			from	`{$this->eve->DBPref}payment_group`
			where	`{$this->eve->DBPref}payment_group`.`id` = ?
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
			$id, $name, $unverified_payment_info, $verified_payment_info, $state
		);

		// Fetching values
		if ($stmt1->fetch())
		{	
			$stmt1->close();
			return array(
				'id' => $id, 'name' => $name, 'unverified_payment_info' => $unverified_payment_info,
				'verified_payment_info' => $verified_payment_info, 'state' => $state
			);
		}
		else
		{
			$stmt1->close();
			return null;
		}
	}

	function payment_group_states()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}payment_group` WHERE Field = 'state'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	function payment_group_update($payment_group)
	{	
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}payment_group`
			set		`{$this->eve->DBPref}payment_group`.`name` = ?,
					`{$this->eve->DBPref}payment_group`.`unverified_payment_info` = ?,
					`{$this->eve->DBPref}payment_group`.`verified_payment_info` = ?,
					`{$this->eve->DBPref}payment_group`.`state` = ?
			where	`{$this->eve->DBPref}payment_group`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::PAYMENT_GROUP_UPDATE_ERROR_SQL;
		}
		$stmt->bind_param('ssssi', $payment_group['name'], $payment_group['unverified_payment_info'], $payment_group['verified_payment_info'], $payment_group['state'], $payment_group['id']);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::PAYMENT_GROUP_UPDATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::PAYMENT_GROUP_UPDATE_SUCCESS;
		}
	}

	function payment_item_list($payment_id)
	{
		$result = array();
		$stmt1 = $this->eve->mysqli->prepare
		("
			select 	`{$this->eve->DBPref}payment_item`.`id`,
					`{$this->eve->DBPref}payment_item`.`payment_id`,
					`{$this->eve->DBPref}payment_item`.`payment_option_id`,
					`{$this->eve->DBPref}payment_option`.`type`,
					`{$this->eve->DBPref}payment_option`.`name`,
					`{$this->eve->DBPref}payment_option`.`description`,
					`{$this->eve->DBPref}payment_option`.`value`,
					`{$this->eve->DBPref}payment_option`.`available_from`,
					`{$this->eve->DBPref}payment_option`.`available_to`,
					`{$this->eve->DBPref}payment_option`.`admin_only`,
					`{$this->eve->DBPref}payment_option`.`active`
			from	`{$this->eve->DBPref}payment_item`, `{$this->eve->DBPref}payment_option`
			where	`{$this->eve->DBPref}payment_item`.`payment_option_id` = `{$this->eve->DBPref}payment_option`.`id` and
					`{$this->eve->DBPref}payment_item`.`payment_id` = ?
		");
		if ($stmt1 === false)
		{
			return null;
		}
		$stmt1->bind_param('i', $payment_id);
		$stmt1->execute();		
		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt1->bind_result
		(
			$id, $payment_id, $payment_option_id, $type, $name, $description,
			$value, $available_from, $available_to, $admin_only, $active
		);
		// Fetching values
		while ($stmt1->fetch())
		{
			$result[] = array(
				'id' => $id, 'payment_id' => $payment_id, 'payment_option_id' => $payment_option_id, 'type' =>$type, 'name' => $name, 'description' => $description,
				'value' => $value, 'available_from' => $available_from, 'available_to' => $available_to,
				'admin_only' => $admin_only, 'active' => $active
			);
		}
		$stmt1->close();
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
	{	
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

	/**
	 * @param boolean $id_as_array_key
	 * @param boolean $only_currently_available_to_users
	 */
	function payment_option_list($id_as_array_key = false, $only_currently_available_to_users = false)
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
			// If this function is asked to retrive only currently available options to users
			// and the current option is only to be used by admins or the current time is out of
			// the range of availability, the loop continues without adding the option to the 
			// list. 
			if
			( 	$only_currently_available_to_users &&
				(
					($admin_only) ||
					($available_from && (strtotime($available_from) > strtotime('now'))) ||
					($available_to && (strtotime($available_to) < strtotime('now')))
				)
			)
			continue; 
				
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

	function payment_option_update($payment_option)
	{	
		// Verifying the consistency of $payment_option['value'], 
		// $payment_option['available_from'] and $payment_option['available_to'] since
		// they arepassed as text. Any incorrect value may break the SQL query execution.
		$payment_option['value'] = floatval($payment_option['value']);
		$payment_option['available_from'] = strtotime($payment_option['available_from']) ? $payment_option['available_from'] : null;
		$payment_option['available_to'] = strtotime($payment_option['available_to']) ? $payment_option['available_to'] : null;
		
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
