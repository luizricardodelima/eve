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
	
	function perform_payment($screenname, $paymenttype_id, $date, $note, $value_paid, $value_received)
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
			SET `paymenttype_id` = ?, `value_paid` = ?, `value_received` = ?, `date` = ?, `note` = ?
			WHERE `email` = ?;
		");
		if ($stmt2 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return self::PAYMENT_ERROR;
		}
		$stmt2->bind_param('iddsss', $paymenttype_id, $value_paid, $value_received, $date, $note, $screenname);
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
		
		if ($result == self::PAYMENT_SUCCESSFUL && $this->eve->getSetting('payment_send_email_on_update'))
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
				'$site_url' => $this->eve->url()
			);
			$send_successful = $this->evemail->send_mail($screenname, $placeholders, $this->eve->getSetting('payment_email_subject'), $this->eve->getSetting('payment_email_body_html'));
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

		if ($this->eve->getSetting('payment_send_email_on_update'))
		{
			$placeholders = array
			(
				'$email' => $payment['email'],
				'$paymenttype_name' => $this->eve->_('paymenttype.name.null'),
				'$paymenttype_description' => $this->eve->_('paymenttype.description.null'),
				'$support_email_address' => $this->eve->getSetting('support_email_address'),
				'$system_name' => $this->eve->getSetting('system_name'),
				'$site_url' => $this->eve->url()
			);
			$this->evemail->send_mail($payment['email'], $placeholders, $this->eve->getSetting('payment_email_subject'), $this->eve->getSetting('payment_email_body_html'));
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

	function paymenttype_create($name = "")
	{
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}paymenttype` (`name`) values (?)
		");
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->close();
	}

	function paymenttype_delete($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}paymenttype`
			set	`{$this->eve->DBPref}paymenttype`.`active` = 0
			where	`{$this->eve->DBPref}paymenttype`.`id` = ?
		");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
	}

	function paymenttype_get($id)
	{
		$stmt1 = $this->eve->mysqli->prepare
		("
			select 	*
			from	`{$this->eve->DBPref}paymenttype`
			where	`{$this->eve->DBPref}paymenttype`.`id` = ?
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
			$id,
			$name,
			$description,
			$active
		);

		// Fetching values
		if ($stmt1->fetch())
		{	
			$stmt1->close();
			return array('id' => $id, 'name' => $name, 'description' => $description, 'active' => $active);
		}
		else
		{
			$stmt1->close();
			return null;
		}
	}


	function paymenttype_list($id_as_array_key = false)
	{
		$result = array();
		$stmt1 = $this->eve->mysqli->prepare
		("
			select *
			from		`{$this->eve->DBPref}paymenttype`
			where		`{$this->eve->DBPref}paymenttype`.`active` = 1
			order by	`{$this->eve->DBPref}paymenttype`.`name`;
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
			$id,
			$name,
			$description,
			$active
		);
		// Fetching values
		while ($stmt1->fetch())
		{
			$paymenttype = array('id' => $id, 'name' => $name, 'description' => $description, 'active' => $active);
			if ($id_as_array_key)
				$result[$id] = $paymenttype;
			else
				$result[] = $paymenttype;
		}
		$stmt1->close();
		return $result;
	}

	function paymenttype_update($paymenttype)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}paymenttype`
			set	`{$this->eve->DBPref}paymenttype`.`name` = ?,
				`{$this->eve->DBPref}paymenttype`.`description` = ?,
				`{$this->eve->DBPref}paymenttype`.`active` = ?
			where	`{$this->eve->DBPref}paymenttype`.`id` = ?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('ssii', $paymenttype['name'], $paymenttype['description'], $paymenttype['active'], $paymenttype['id']);
		$stmt->execute();
		$stmt->close();
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		$this->evemail = new EveMail($eve);
	}

}

?>
