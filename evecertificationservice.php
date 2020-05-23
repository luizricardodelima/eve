<?php
require_once 'eve.class.php';
require_once 'evemail.php';
require_once 'evesubmissionservice.class.php';

class EveCertificationService
{
	const CERTIFICATION_ATTRIBUITION_ERROR = 0;	
	const CERTIFICATION_ATTRIBUITION_ERROR_SQL = 1;
	const CERTIFICATION_ATTRIBUITION_SUCCESS = 2;

	const CERTIFICATIONMODEL_CREATE_ERROR_SQL = 10;	
	const CERTIFICATIONMODEL_CREATE_SUCCESS = 11;

	const CERTIFICATIONMODEL_DELETE_ERROR_SQL = 12;	
	const CERTIFICATIONMODEL_DELETE_SUCCESS = 13;

	const CERTIFICATIONMODEL_DUPLICATE_ERROR_INVALID_ID = 14;
	const CERTIFICATIONMODEL_DUPLICATE_ERROR_SQL = 15;
	const CERTIFICATIONMODEL_DUPLICATE_SUCCESS = 16;

	private $eve;
	private $evemail;

	/** $structure - array of objects */
	function certification_text_output($structure, $user, $submission)
	{
		$output = "";
		if(is_array($structure)) foreach ($structure as $structure_item) 
			$output .= $this->certification_text_output_item($structure_item, $user, $submission);
		return $output;
	}
	
	private function certification_text_output_item($structure_item, $user, $submission)
	{
		switch ($structure_item->type)
		{
			case "text":
				return $this->certification_text_output_text($structure_item);
			case "variable":
				return $this->certification_text_output_variable($structure_item, $user, $submission);
			case "list":
				return $this->certification_text_output_list($structure_item, $user, $submission);
		}
	}

	private function certification_text_output_text($structure_item)
	{	
		return $structure_item->value;
	}

	private function certification_text_output_variable($structure_item, $user, $submission)
	{	
		$text = "";	
		switch ($structure_item->entity)
		{
			case "user":
				$text = $user[$structure_item->parameter];
				break;
			case "submission-content":
				$structure = json_decode($submission['structure']);
				$content = json_decode($submission['content']);
				$parameter = explode("-", $structure_item->parameter);
					
				// TODO this should be handled by DynamicInput
				switch ($structure[$parameter[0] - 1]->type)
				{
					case "array":
						$text = $content[$parameter[0] - 1][$parameter[1] - 1];
						break;
					case "text":
					case "bigtext":
						$text = $content[$parameter[0] - 1];
						break;
					case "enum":
						$text = $structure[$parameter[0] - 1]->spec->items[$content[$parameter[0] - 1]];
						break;
					case "check":
						if ($content[$parameter[0] - 1])
							$text = $this->eve->_('common.label.yes');
						else
							$text = $this->eve->_('common.label.no');
						break;
					case "file":
						$text =  $content[$parameter[0] - 1];
						break;
				}
				break;
		}
		if ($structure_item->uppercase) $text = mb_strtoupper($text);
		return $text;
	}

	private function certification_text_output_list($structure_item, $user, $submission)
	{
		$output_array = array();
		foreach ($structure_item->content as $item)
		{
			$text = $this->certification_text_output_item($item, $user, $submission);
			if (!empty($text))
				$output_array[] = $text;
		}
		if (count($output_array) > 1)
			return implode(", ", array_slice($output_array, 0, count($output_array)-1)) . " e " . $output_array[count($output_array)-1];
		else
			return $output_array[0];
	}

	function certification_attribuition($certificationtemplate_id, $screenname, $submission_id, $locked = 0)
	{
		// TODO check if screenname and submissionid are valid to return more specific errors
		// Preparing insert statement
		$stmt2 = $this->eve->mysqli->prepare
		("
			insert
			into `{$this->eve->DBPref}certification` (`certificationdef_id`, `screenname`, `submissionid`, `locked`)
			values (?, ?, ?, ?);
		");
		if ($stmt2 === false)
		{
			return self::CERTIFICATION_ATTRIBUITION_ERROR_SQL;
		}
		$stmt2->bind_param('isii', $certificationtemplate_id, $screenname, $submission_id, $locked);
		$stmt2->execute();
		if ($this->eve->mysqli->affected_rows)
		{
			if (!$locked && $this->eve->getSetting('email_snd_certification'))
			$this->send_certification_mail($stmt2->insert_id);
			$stmt2->close();
			return self::CERTIFICATION_ATTRIBUITION_SUCCESS;
		}
		else
		{
			$stmt2->close();
			return self::CERTIFICATION_ATTRIBUITION_ERROR;
		}
	}
	
	// $submission_ids must be an array // TODO REUSE certification_attribuition FUNCTION
	function certification_attribuition_submission($certificationtemplate_id, $submission_ids, $locked = 0)
	{
		// Preparing insert statement
		$stmt2 = $this->eve->mysqli->prepare
		("
			insert
			into `{$this->eve->DBPref}certification` (`certificationdef_id`, `screenname`, `submissionid`, `locked`)
			values (?, ?, ?, ?);
		");
		if ($stmt2 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		$user_screennames = array();
		$eveSubmissionService = new EveSubmissionService($this->eve);
		foreach ($submission_ids as $key => $submission_id)
		{
			$user_screennames[$key] = $eveSubmissionService->submission_get($submission_id)['email'];
		}
		foreach ($submission_ids as $key => $submission_id)
		{
			$stmt2->bind_param('isii', $certificationtemplate_id, $user_screennames[$key], $submission_id, $locked);
			$stmt2->execute();
			if (!$locked && $this->eve->getSetting('email_snd_certification'))
				$this->send_certification_mail($stmt2->insert_id);
		}
		$stmt2->close();
	}

	// $users must be an array // TODO REUSE certification_attribuition FUNCTION
	function certification_attribuition_user($certificationtemplate_id, $users, $locked = 0)
	{
		// Preparing insert statement
		$stmt2 = $this->eve->mysqli->prepare
		("
			insert
			into `{$this->eve->DBPref}certification` (`certificationdef_id`, `screenname`, `locked`)
			values (?, ?, ?);
		");
		if ($stmt2 === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		foreach ($users as $user)
		{
			$stmt2->bind_param('isi', $certificationtemplate_id, $user, $locked);
			$stmt2->execute();
			if (!$locked && $this->eve->getSetting('email_snd_certification'))
				$this->send_certification_mail($stmt2->insert_id);
		}
		$stmt2->close();
	}

	function certification_list($ordernation)
	{
		$ordering = 0;
		switch($ordernation)
		{
			case 'name':
				$ordering = "`{$this->eve->DBPref}userdata`.`name`";
				break;
			case 'certificationname':
				$ordering = "`{$this->eve->DBPref}certificationdef`.`name`";
				break;
			case 'locked':
				$ordering = "`{$this->eve->DBPref}certification`.`locked` desc";
				break;
			case 'views':
				$ordering = "`{$this->eve->DBPref}certification`.`views` desc";
				break;
			default:
				$ordering = "`{$this->eve->DBPref}userdata`.`name`";
				break;
		}
		$certification_res = $this->eve->mysqli->query
		("
			SELECT
				`{$this->eve->DBPref}certification`.`id`,	
				`{$this->eve->DBPref}userdata`.`name`,
				`{$this->eve->DBPref}certificationdef`.`name`, 
				`{$this->eve->DBPref}certification`.`locked`,
				`{$this->eve->DBPref}certification`.`views`
			FROM
				`{$this->eve->DBPref}certificationdef`,
				`{$this->eve->DBPref}certification`,
				`{$this->eve->DBPref}userdata`
			WHERE
				`{$this->eve->DBPref}certification`.`screenname` = `{$this->eve->DBPref}userdata`.`email`
				AND
				`{$this->eve->DBPref}certificationdef`.`id` = `{$this->eve->DBPref}certification`.`certificationdef_id`
			ORDER BY
				$ordering;
		");
		$result = array();
		while ($certification_row = $certification_res->fetch_row())
		{
			$result[] = $certification_row;
		}
		return $result;
	}

	function get_certifications_for_user($screenname)
	{
		// TODO REMOVE SQL INJECTION (Although this methods is currently not being used with get/post values)
		$certifications = array();
		$certifications_res = $this->eve->mysqli->query
		("
			SELECT  
				`{$this->eve->DBPref}certification`.`id`,
				`{$this->eve->DBPref}certificationdef`.`name`,
				`{$this->eve->DBPref}certificationdef`.`hasopenermsg`
			FROM
				`{$this->eve->DBPref}certification`,
				`{$this->eve->DBPref}certificationdef`
			WHERE
				`{$this->eve->DBPref}certification`.`certificationdef_id` = `{$this->eve->DBPref}certificationdef`.`id` 
				AND
				`{$this->eve->DBPref}certification`.`screenname` = '$screenname'
				AND
				`{$this->eve->DBPref}certification`.`locked` = 0;
		");
		while ($certification = $certifications_res->fetch_assoc())
			$certifications[] = $certification;
		return $certifications;
	}
	
	// $certifications must be an array with certification ids
	function lock_certifications($certifications)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			update `{$this->eve->DBPref}certification`
			set `locked` = 1
			where `id` = ?;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		foreach ($certifications as $certification)
		{
			$stmt->bind_param('i', $certification);
			$stmt->execute();
		}
		$stmt->close();
	}

	// $certifications must be an array with certification ids
	function unlock_certifications($certifications)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			update `{$this->eve->DBPref}certification`
			set `locked` = 0
			where `id` = ?;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		foreach ($certifications as $certification)
		{
			$stmt->bind_param('i', $certification);
			$stmt->execute();
			if ($this->eve->getSetting('email_snd_certification'))
				$this->send_certification_mail($certification);
		}
		$stmt->close();
	}

	// $certifications must be an array with certification ids
	function delete_certifications($certifications)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			delete from `{$this->eve->DBPref}certification`
			where `id` = ?
			limit 1;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		foreach ($certifications as $certification)
		{
			$stmt->bind_param('i', $certification);
			$stmt->execute();
		}
		$stmt->close();
	}

	private function send_certification_mail($certification_id)
	{
		// Retrieving certification information
		$stmt = $this->eve->mysqli->prepare
		("
			select  
				`{$this->eve->DBPref}certification`.`screenname`,
				`{$this->eve->DBPref}certificationdef`.`name`
			from
				`{$this->eve->DBPref}certification`,
				`{$this->eve->DBPref}certificationdef`
			where
				`{$this->eve->DBPref}certification`.`certificationdef_id` = `{$this->eve->DBPref}certificationdef`.`id` 
				AND
				`{$this->eve->DBPref}certification`.`id` = ?;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false;
		}
		$stmt->bind_param('i', $certification_id);
		$stmt->execute();
		$certification = array();
		$stmt->bind_result
		(
			$certification['owner'],
			$certification['name']
		);
		$stmt->fetch();
		$stmt->close();

		$placeholders = array
		(
			'$email' => $certification['owner'],
			'$certification_name' => $certification['name'],
			'$support_email_address' => $this->eve->getSetting('support_email_address'),
			'$system_name' => $this->eve->getSetting('system_name'),
			'$site_url' => $this->eve->url()
		);
		$this->evemail->send_mail($certification['owner'], $placeholders, $this->eve->getSetting('email_sbj_certification'), $this->eve->getSetting('email_msg_certification'));
	}

	function certificationmodel_create($name = "")
	{
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}certificationdef`
			(`name`) values (?);
		");
		if ($stmt === false)
		{
			return self::CERTIFICATIONMODEL_CREATE_ERROR_SQL;
		}
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->close();
		return self::CERTIFICATIONMODEL_CREATE_SUCCESS;
	}

	function certificationmodel_delete($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			delete from `{$this->eve->DBPref}certificationdef`
			where `{$this->eve->DBPref}certificationdef`.`id` = ?
			limit 1;
		");
		if ($stmt === false)
		{
			return self::CERTIFICATIONMODEL_DELETE_ERROR_SQL;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
		return self::CERTIFICATIONMODEL_DELETE_SUCCESS;
	}

	function certificationmodel_duplicate($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}certificationdef` 
				(`type`, `name`, `text`, `backgroundimage`, `topmargin`, `leftmargin`, `rightmargin`, `text_lineheight`, `text_fontsize`, `hasopenermsg`, `openermsg` )
				select `type`, `name`, `text`, `backgroundimage`, `topmargin`, `leftmargin`, `rightmargin`, `text_lineheight`, `text_fontsize`, `hasopenermsg`, `openermsg`
				from `{$this->eve->DBPref}certificationdef` where `{$this->eve->DBPref}certificationdef`.`id` = ?;
		");
		if ($stmt === false)
		{
			return self::CERTIFICATIONMODEL_DUPLICATE_ERROR_SQL;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
		if ($this->eve->mysqli->affected_rows)
			return self::CERTIFICATIONMODEL_DUPLICATE_SUCCESS;
		else
			return self::CERTIFICATIONMODEL_DUPLICATE_ERROR_INVALID_ID;
		
	}
	
	function certificationmodel_get($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}certificationdef`
			where  `id`=?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$certificationmodel = array();
		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    		$stmt->bind_result
		(
			$certificationmodel['id'],
			$certificationmodel['type'],
			$certificationmodel['name'],
			$certificationmodel['pagesize'],
			$certificationmodel['pageorientation'],
			$certificationmodel['backgroundimage'],
			$certificationmodel['text'],
			$certificationmodel['topmargin'],
			$certificationmodel['leftmargin'],
			$certificationmodel['rightmargin'],
			$certificationmodel['text_lineheight'],
			$certificationmodel['text_fontsize'],
			$certificationmodel['hasopenermsg'],
			$certificationmodel['openermsg']
		);
		// Fetching values
		if ($stmt->fetch())
		{
			$stmt->close();
			return $certificationmodel;
		}
		else
		{
			$stmt->close();
			return null;
		}
	}

	function certificationmodel_list($orderby = "id")
	{
		// Sanitizing input		
		switch ($orderby)
		{
			case "id":			
			case "type":
			case "name":
			case "text":
				// Everything is fine, these are the acceptable values.
				break;
			default:		
				// Unnacceptable value. Changing it to "id".
				$orderby = "id";
				break;
		}
		return $this->eve->mysqli->query
		("	
			select *
			from `{$this->eve->DBPref}certificationdef`
			order by `{$this->eve->DBPref}certificationdef`.`$orderby` ;
		");
	}

	/** Returns list of possible values for the field 'pageorientation' of a certification model */
	function certificationmodel_pageorientations()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certificationdef` WHERE Field = 'pageorientation'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	/** Returns list of possible values for the field 'pagesize' of a certification model */
	function certificationmodel_pagesizes()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certificationdef` WHERE Field = 'pagesize'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}
	
	/** Returns list of possible values for the field 'type' of a certification model */
	function certificationmodel_types()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certificationdef` WHERE Field = 'type'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		$this->evemail = new EveMail($eve);
	}

}

?>
