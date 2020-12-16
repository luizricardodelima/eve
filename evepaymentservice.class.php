<?php

require_once 'eve.class.php';
require_once 'evemail.class.php';

class EvePaymentService
{
	private $eve;
	private $evemail;

	const PAYMENT_SAVE_ERROR = "payment.save.error";	
	const PAYMENT_SAVE_VALIDATION_ERROR_DATE = "payment.save.validationerror.date";
	const PAYMENT_SAVE_VALIDATION_ERROR_USER_EMAIL = "payment.save.validationerror.user.email";
	const PAYMENT_SAVE_VALIDATION_ERROR_OPTIONS_FROM_ANOTHER_GROUP = "payment.save.validationerror.options.from.another.group";

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
	

	/**
	 * Returns a list of payments made by the user (represented by $screenname) for 
	 * a certain payment group (represented by $payment_group_id)
	 */
	function payment_list_for_user($screenname, $payment_group_id)
	{
		$result = array();
	
		$stmt = $this->eve->mysqli->prepare
		("
			select 	*
			from	`{$this->eve->DBPref}payment`
			where	`{$this->eve->DBPref}payment`.`user_email` = ?
			and		`{$this->eve->DBPref}payment`.`payment_group_id` <=> ?
			and		`{$this->eve->DBPref}payment`.`active` = 1
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('si', $screenname, $payment_group_id);
		$stmt->execute();

		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
		$stmt->bind_result
		(
			$id, $payment_group_id, $user_email, $date, $payment_method, $value_paid, $value_received, $note, $file, $active
		);
		while ($stmt->fetch())
		{
			$result[] = array
			(
				'id' => $id, 'payment_group_id' => $payment_group_id,
				'user_email' => $user_email, 'date' => $date, 'payment_method' => $payment_method,
				'value_paid' => $value_paid, 'value_received' => $value_received, 'note' => $note,
				'file' => $file, 'active' => $active
			);
		}
		return $result;
	}

	function payment_register($user_email, $payment_method, $date, $note, $value_paid, $value_received, $payment_options = null)
	{
		// Detecting which Payment Group this payment refers to according to its payment_options
		$payment_group_id = null;
		foreach ($payment_options as $payment_option_id)
		{
			$payment_group_id = $this->payment_option_get($payment_option_id)['payment_group_id'];
		}

		return $this->payment_save(null, $payment_group_id, $user_email, $date, $payment_method, $note, $value_paid, $value_received, $payment_options);
	}

	function payment_delete($id)
	{
		$payment = $this->payment_get($id);
		$agent = (isset($_SESSION['screenname'])) ? $_SESSION['screenname'] : "unknown user";
		$new_note = "Payment deleted at " . date("c") . " by $agent.\n";

		$stmt1 = $this->eve->mysqli->prepare
		("
			update 	`{$this->eve->DBPref}payment`
			set 	`{$this->eve->DBPref}payment`.`note` = CONCAT(?, `{$this->eve->DBPref}payment`.`note`),
					`{$this->eve->DBPref}payment`.`active` = 0
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

		if ($this->eve->getSetting('email_snd_payment_update'))
		{
			// Payment_details still can be retrieved since payment is only deactivated,
			// note deleted from database
			$placeholders = array
			(
				'$email' => $payment['user_email'],
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl(),
				'$payment_details' => $this->payment_output_details_for_user($id)
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
			$payment['payment_group_id'],
			$payment['user_email'],
			$payment['date'],
			$payment['payment_method'],
			$payment['value_paid'],
			$payment['value_received'],
			$payment['note'],
			$payment['file'],
			$payment['active']
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

	function payment_list()
	{
		$result = array();

		$resource = $this->eve->mysqli->query
		("	
			select 
				`{$this->eve->DBPref}payment`.`id`,
				`{$this->eve->DBPref}payment`.`payment_group_id`,
				`{$this->eve->DBPref}payment`.`user_email`,
				`{$this->eve->DBPref}payment`.`date`,
				`{$this->eve->DBPref}payment`.`payment_method`,
				`{$this->eve->DBPref}payment`.`value_paid`,
				`{$this->eve->DBPref}payment`.`value_received`,
				`{$this->eve->DBPref}payment`.`note` as `payment_note`,
				`{$this->eve->DBPref}userdata`.*
			from
				`{$this->eve->DBPref}payment`
			inner join
				`{$this->eve->DBPref}userdata` on (`{$this->eve->DBPref}userdata`.`email` = `{$this->eve->DBPref}payment`.`user_email`)
			where
				`{$this->eve->DBPref}payment`.`active` = 1
		");
		while ($item = $resource->fetch_assoc()) $result[] = $item;
		return $result;
	}

	// Returns the id of payment if there a payment was successfully created, a success message
	// if a payment was successfully updated and error messages otherwise
	function payment_save($id, $payment_group_id, $user_email, $date, $payment_method, $note, $value_paid, $value_received, $payment_options)
	{
		// Validation - $payment_group and $payment_options. A payment can only contain payment
		// options of the same payment_group
		foreach ($payment_options as $payment_option_id) {
			$payment_option = $this->payment_option_get($payment_option_id);
			if ($payment_option['payment_group_id'] != $payment_group_id)
				return self::PAYMENT_SAVE_VALIDATION_ERROR_OPTIONS_FROM_ANOTHER_GROUP;
		}
		// Validation - $user_email
		if(!$this->eve->user_exists($user_email))
			return self::PAYMENT_SAVE_VALIDATION_ERROR_USER_EMAIL;
		// Validation - $date
		list($year, $month, $day) = explode('-', $date);
		if ((false === strtotime($date)) || (false === checkdate($month, $day, $year)))
			return self::PAYMENT_SAVE_VALIDATION_ERROR_DATE;

		// Performing changes on Database
		$payment_id = null;

		if ($id === null) // A payment object needs to be created in this case
		{
			// TODO ROLLBACK IF SOMETHING WRONG HAPPENS IN THE FURTHER QUERIES
			$stmt1 = $this->eve->mysqli->prepare
			("
				insert into	`{$this->eve->DBPref}payment`
							(`payment_group_id`, `user_email`, `date`, `payment_method`, `note`, `value_paid`, `value_received`)
				values		(?, ?, ?, ?, ?, ?, ?)
			");
			if ($stmt1 === false)
			{
				return self::PAYMENT_SAVE_ERROR;
			}
			$stmt1->bind_param
			(
				'issssdd', $payment_group_id, $user_email, $date, $payment_method,
				$note, $value_paid, $value_received
			);
			$stmt1->execute();
			if (!empty($stmt1->error))
			{
				$stmt1->close();
				return self::PAYMENT_SAVE_ERROR;
			}
			$payment_id = $stmt1->insert_id;
			$stmt1->close();
		}
		else // A payment object already exists. Updating its values
		{
			$stmt1 = $this->eve->mysqli->prepare
			("
				update	`{$this->eve->DBPref}payment`
				set		`{$this->eve->DBPref}payment`.`payment_group_id` = ?,
						`{$this->eve->DBPref}payment`.`user_email` = ?,
						`{$this->eve->DBPref}payment`.`date` = ?,
						`{$this->eve->DBPref}payment`.`payment_method` = ?,
						`{$this->eve->DBPref}payment`.`note` = ?,
						`{$this->eve->DBPref}payment`.`value_paid` = ?,
						`{$this->eve->DBPref}payment`.`value_received` = ?
				where	`{$this->eve->DBPref}payment`.`id` = ?
			");
			if ($stmt1 === false)
			{
				return self::PAYMENT_SAVE_ERROR;
			}
			$stmt1->bind_param
			(
				'issssddi', $payment_group_id, $user_email, $date, $payment_method,
				$note, $value_paid, $value_received, $id
			);
			$stmt1->execute();
			if (!empty($stmt1->error))
			{
				$stmt1->close();
				return self::PAYMENT_SAVE_ERROR;
			}
			$payment_id = $id;
			$stmt1->close();
		}

		// Clearing the list of payment items before re-inserting them
		$stmt2 = $this->eve->mysqli->prepare
		("
			delete from `{$this->eve->DBPref}payment_item` where `payment_id` = ?;
		");
		if ($stmt2 === false)
		{
			return self::PAYMENT_SAVE_ERROR;
		}
		$stmt2->bind_param('i', $payment_id);
		$stmt2->execute();
		if (!empty($stmt2->error))
		{
			$stmt2->close(); // TODO Rollback creation of payment...
			return self::PAYMENT_SAVE_ERROR;
		}
		$stmt2->close();

		// Inserting payment items
		$stmt3 = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}payment_item` (`payment_id`, `payment_option_id`)
			values (?, ?)
		");
		if ($stmt3 === false)
		{
			return self::PAYMENT_SAVE_ERROR;
		}
		foreach ($payment_options as $payment_option_id)
		{
			$stmt3->bind_param('ii', $payment_id,  $payment_option_id);
			$stmt3->execute();
			if (!empty($stmt3->error))
			{
				$stmt3->close(); // TODO Rollback creation of payment...
				return self::PAYMENT_SAVE_ERROR;
			}
		}
		$stmt3->close();

		// Sending email if it is defined on settings
		if ($this->eve->getSetting('email_snd_payment_update'))
		{
			$placeholders = array
			(
				'$email' => $user_email,
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->sysurl(),
				'$payment_details' => $this->payment_output_details_for_user($payment_id)
			);
			$this->evemail->send_mail($user_email, $placeholders, $this->eve->getSetting('email_sbj_payment_update'), $this->eve->getSetting('email_msg_payment_update'));
		}

		return $payment_id;
	}

	/** Output a human readable HTML formatted table containing the Payment details */
	function payment_output_details_for_user($id)
	{
		$payment = $this->payment_get($id);
		$payment_items = $this->payment_item_list($id);

		$date_formatter = new IntlDateFormatter($this->eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
		$curr_formatter = new NumberFormatter($this->eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
		$formatted_payment_date = $date_formatter->format(strtotime($payment['date']));
		$formatted_value_paid = $curr_formatter->format($payment['value_paid']);

		$result = "";
		$result.= "<table class=\"data_table\"><thead><th width=\"30%\"></th><th width=\"30%\"></th></thead>";
		$result.= "<tr><td><strong>{$this->eve->_('payment.id')}</strong></td><td>{$payment['id']}</td></tr>";
		$result.= "<tr><td><strong>{$this->eve->_('payment.date')}</strong></td><td>$formatted_payment_date</td></tr>";
		$result.= "<tr><td><strong>{$this->eve->_('payment.value.paid')}</strong></td><td>$formatted_value_paid</td></tr>";
		$result.= "<tr><td><strong>{$this->eve->_('payment.payment.method')}</strong></td><td>{$payment['payment_method']}</td></tr>";
		$result.= "<tr><td><strong>{$this->eve->_('payment.items')}</strong></td><td>";
		foreach ($payment_items as $payment_item) $result.= "{$payment_item['name']}<br/>";
		$result.= "</td></tr>";
		$result.= "</table>";
		return $result;
	}

	/**
	 */
	function payment_group_list($id_as_array_key = false)
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
			$id, $name, $payment_info, $unverified_payment_info, $verified_payment_info, $state
		);
		// Fetching values
		while ($stmt1->fetch())
		{
			$payment_group = array(
				'id' => $id, 'name' => $name, 'payment_info' => $payment_info, 
				'unverified_payment_info' => $unverified_payment_info,
				'verified_payment_info' => $verified_payment_info, 'state' => $state
			);
			if ($id_as_array_key)
				$result[$id] = $payment_group;
			else
				$result[] = $payment_group;
		}
		$stmt1->close();
		return $result;
	}

	/**
	 * Returns an array of payment group ids that indicates groups for user.
	 * If there are payment options that are not associated with a group, the
	 * array will contain a null element. This does not list payment groups
	 * which are invisible.
	 */
	function payment_group_list_for_user()
	{
		$result = array();

		$stmt1 = $this->eve->mysqli->prepare
		("
		select distinct `{$this->eve->DBPref}payment_group`.`id` as `id`
		from			`{$this->eve->DBPref}payment_group`
		join			`{$this->eve->DBPref}payment_option`
		on 				`{$this->eve->DBPref}payment_option`.`payment_group_id` = `{$this->eve->DBPref}payment_group`.`id`
		where			`{$this->eve->DBPref}payment_option`.`active` = 1
		and				`{$this->eve->DBPref}payment_group`.`state` != 'invisible'
	
		union			

		select distinct	`{$this->eve->DBPref}payment_option`.`payment_group_id` as `id`
		from			`{$this->eve->DBPref}payment_option`
		where 			`{$this->eve->DBPref}payment_option`.`payment_group_id` is null
		and				`{$this->eve->DBPref}payment_option`.`active` = 1
		order by		`id`
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}		
		$stmt1->execute();

		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt1->bind_result ($id);
		// Fetching values
		while ($stmt1->fetch())
		{
			$result[] = $id;
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
			$id, $name, $payment_info, $unverified_payment_info, $verified_payment_info, $state
		);

		// Fetching values
		if ($stmt1->fetch())
		{	
			$stmt1->close();
			return array(
				'id' => $id, 'name' => $name, 'payment_info' => $payment_info, 
				'unverified_payment_info' => $unverified_payment_info,
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
					`{$this->eve->DBPref}payment_group`.`payment_info` = ?,
					`{$this->eve->DBPref}payment_group`.`unverified_payment_info` = ?,
					`{$this->eve->DBPref}payment_group`.`verified_payment_info` = ?,
					`{$this->eve->DBPref}payment_group`.`state` = ?
			where	`{$this->eve->DBPref}payment_group`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::PAYMENT_GROUP_UPDATE_ERROR_SQL;
		}
		$stmt->bind_param('sssssi', $payment_group['name'], $payment_group['payment_info'], $payment_group['unverified_payment_info'], $payment_group['verified_payment_info'], $payment_group['state'], $payment_group['id']);
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
			$available_to, $payment_group_id, $admin_only, $active
		);

		// Fetching values
		if ($stmt1->fetch())
		{	
			$stmt1->close();
			return array(
				'id' => $id, 'type' =>$type, 'name' => $name, 'description' => $description,
				'value' => $value, 'available_from' => $available_from, 'available_to' => $available_to,
				'payment_group_id' => $payment_group_id, 'admin_only' => $admin_only, 'active' => $active
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
	 * @param boolean $available_to_users_only
	 * @param boolean $payment_group_filter
	 * @param boolean $payment_group_id
	 */
	function payment_option_list($id_as_array_key = false, $available_to_users_only = false, $payment_group_filter = false, $payment_group_id = null)
	{	// TODO ERROR MESSAGES
		$result = array();

		$payment_group_filter_query = $payment_group_filter ? "and `{$this->eve->DBPref}payment_option`.`payment_group_id` <=> ? " : "";
		$stmt1 = $this->eve->mysqli->prepare
		("
			select *
			from		`{$this->eve->DBPref}payment_option`
			where		`{$this->eve->DBPref}payment_option`.`active` = 1
			$payment_group_filter_query
			order by	`{$this->eve->DBPref}payment_option`.`name`;
		");
		if ($stmt1 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		if ($payment_group_filter) $stmt1->bind_param('i', $payment_group_id);	
		$stmt1->execute();

		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt1->bind_result
		(
			$id, $type, $name, $description, $value, $available_from,
			$available_to, $payment_group_id, $admin_only, $active
		);
		// Fetching values
		while ($stmt1->fetch())
		{
			// If this function is asked to retrive only currently available options to users
			// and the current option is only to be used by admins or the current time is out of
			// the range of availability, the loop continues without adding the option to the 
			// list. 
			if
			( 	$available_to_users_only &&
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
				'payment_group_id' => $payment_group_id, 'admin_only' => $admin_only, 'active' => $active
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
		$payment_option['payment_group_id'] = is_numeric($payment_option['payment_group_id']) ? intval($payment_option['payment_group_id']) : null;

		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}payment_option`
			set		`{$this->eve->DBPref}payment_option`.`type` = ?,
					`{$this->eve->DBPref}payment_option`.`name` = ?,
					`{$this->eve->DBPref}payment_option`.`description` = ?,
					`{$this->eve->DBPref}payment_option`.`value` = ?,
					`{$this->eve->DBPref}payment_option`.`available_from` = ?,
					`{$this->eve->DBPref}payment_option`.`available_to` = ?,
					`{$this->eve->DBPref}payment_option`.`payment_group_id` = ?,
					`{$this->eve->DBPref}payment_option`.`admin_only` = ?
			where	`{$this->eve->DBPref}payment_option`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::PAYMENT_OPTION_UPDATE_ERROR_SQL;
		}
		$stmt->bind_param('sssdssiii', $payment_option['type'], $payment_option['name'], $payment_option['description'], $payment_option['value'], $payment_option['available_from'], $payment_option['available_to'], $payment_option['payment_group_id'], $payment_option['admin_only'], $payment_option['id']);
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
