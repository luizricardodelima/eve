<?php
require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'eve.class.php';
require_once 'evemail.class.php';
require_once 'evesubmissionservice.class.php';

class EveCertificationService
{
	const CERTIFICATION_ATTRIBUITION_ERROR = 0;	
	const CERTIFICATION_ATTRIBUITION_ERROR_SQL = 1;
	const CERTIFICATION_ATTRIBUITION_SUCCESS = 2;

	const CERTIFICATIONMODEL_CREATE_ERROR_SQL = 'certificationmodel.create.error.sql';
	const CERTIFICATIONMODEL_CREATE_SUCCESS = 'certificationmodel.create.success';

	const CERTIFICATIONMODEL_DELETE_ERROR_SQL = 'certificationmodel.delete.error.sql';
	const CERTIFICATIONMODEL_DELETE_SUCCESS = 'certificationmodel.delete.success';

	const CERTIFICATIONMODEL_DUPLICATE_ERROR_INVALID_ID = 'certificationmodel.duplicate.error.invalid.id';
	const CERTIFICATIONMODEL_DUPLICATE_ERROR_SQL = 'certificationmodel.duplicate.error.sql';
	const CERTIFICATIONMODEL_DUPLICATE_SUCCESS = 'certificationmodel.duplicate.success';

	private $eve;
	private $evemail;

	function certification_get($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}certification`
			where  `id`=?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$certification = array();
		// Binding result variable - Column by column to ensure compability
		// From PHP Verions 5.3+ there is the get_result() method
    	$stmt->bind_result
		(
			$certification['id'],
			$certification['certification_model_id'],
			$certification['screenname'],
			$certification['submissionid'],
			$certification['locked'],
			$certification['views']
		);
		// Fetching values
		if ($stmt->fetch())
		{
			$stmt->close();
			return $certification;
		}
		else
		{
			$stmt->close();
			return null;
		}
	}

	/**
	 * Generates the certification text defined by the structure defined in $structure
	 * 
	 * @param $structure array The content structure is defined as an array of objects
	 * (created with json and passed to this function decoded with json_decode). The objects
	 * can be the following:
	 * 
	 * - text: shows a fixed text
	 *   {"type": "text", "value": "The text here will be shown."}
	 * 
	 * - variable: shows an attribute from the user or the submission that are associated with
	 * the certification. If the certification is of type 'user_certification' and if there is
	 * an attempt to retrieve an certificaion variable, it will simply return '', not an error.
	 *   {"type": "variable", "entity": "user", "parameter" : "name"}
	 *   {"type": "variable", "entity": "submission-content", "parameter" : "1"}
	 *   {"type": "variable", "entity": "submission-content", "parameter" : "1-1"}
	 * 
	 * Parameters for the entity "user": admin, locked_form, name, address, city, state, country,
	 * postalcode, birthday, gender, phone1, phone2, instituition, customtext1,
	 * customtext2, customtext3, customtext4, customtext5, customflag1, customflag2, customflag3,
	 * customflag4, customflag5, note.
	 * 
	 * Parameter for the entity "submission-content": The number indicating the position. If the
	 * element content is an array (such as grouped texts or multiple choice items), the array
	 * position has to be specified ater an hypen.
	 * 
	 * - list: shows a list of the other objects, separated according grammatical rules. For
	 * example in english, if it is a list of three objects, it displays "[object1], [object2]
	 * and [object3]". Objects that are output with an empty string '' are not included in the
	 * list.
	 *   {"type": "list", "content":
	 * 		   [{"type": "variable", "entity": "submission-content", "parameter" : "1"},
	 * 			{"type": "variable", "entity": "submission-content", "parameter" : "2"}]
	 *    }
	 * 
	 * All objects accept the "uppercase" parameter, which returns the content in uppercase.
	 */
	public function certification_text($structure, $user, $submission)
	{
		$output = "";
		$submission_structure = is_array($submission) ? $submission['structure'] : null;
		$submission_content = is_array($submission)? $submission['content'] : null;
		DynamicFormHelper::$locale = $this->eve->getSetting('system_locale');
		$submission_dform = new DynamicForm($submission_structure, json_decode($submission_content));
		if(is_array($structure)) foreach ($structure as $structure_item) 
			$output .= $this->certification_text_item($structure_item, $user, $submission, $submission_dform);
		else
			$output = $this->eve->_('certification.text.error.invalid.structure');
			return $output;
	}
	
	private function certification_text_item($structure_item, $user, $submission, $submission_dform)
	{
		$text = "";
		if (!isset($structure_item->type)) 
			$text = $this->eve->_('certification.text.error.invalid.element');
		else switch ($structure_item->type)
		{
			case "text":
				$text = $this->certification_text_text($structure_item);
				break;
			case "variable":
				$text = $this->certification_text_variable($structure_item, $user, $submission, $submission_dform);
				break;
			case "list":
				$text = $this->certification_text_list($structure_item, $user, $submission, $submission_dform);
				break;
			default:
				$text = $this->eve->_('certification.text.error.invalid.type');
				break;
			}
		if (isset($structure_item->uppercase) && $structure_item->uppercase) $text = mb_strtoupper($text);
		return $text;
	}

	private function certification_text_text($structure_item)
	{	
		if (!isset($structure_item->value)) return $this->eve->_('certification.text.error.invalid.value');
		else return $structure_item->value;
	}

	private function certification_text_variable($structure_item, $user, $submission, $submission_dform)
	{	
		if (!isset($structure_item->parameter)) return $this->eve->_('certification.text.error.invalid.parameter');
		switch ($structure_item->entity)
		{
			case "user":
				if ($user === null) // For the cases of template viewing
					return ""; 
				else if (!isset($user[$structure_item->parameter]))
					return $this->eve->_('certification.text.error.invalid.parameter');
				else
					return $user[$structure_item->parameter];
				break;
			case "submission-content":
				
				if ($submission === null) // For the cases of template viewing
					return "";
				$parameter = explode("-", $structure_item->parameter);
				if(!is_numeric($parameter[0]) || !isset($submission_dform->structure[$parameter[0] - 1]))
					return $this->eve->_('certification.text.error.invalid.parameter');
				$content = $submission_dform->structure[$parameter[0] - 1]->getFormattedContent();
				if (is_array($content))
				{
					if(!is_numeric($parameter[1]) || !isset($content[$parameter[1] - 1]))
						return $this->eve->_('certification.text.error.invalid.parameter');
					else
						return $content[$parameter[1] - 1];
				}
				else
					return $content;
				break;
		}
	}

	private function certification_text_list($structure_item, $user, $submission, $submission_dform)
	{
		$output_array = array();
		if(!isset($structure_item->content) || !is_array($structure_item->content))
			return $this->eve->_('certification.text.error.invalid.list.content');
		foreach ($structure_item->content as $item)
		{
			$text = $this->certification_text_item($item, $user, $submission, $submission_dform);
			if (!empty($text)) $output_array[] = $text;
		}
		$comma = $this->eve->_('certification.text.list.comma');
		$and = $this->eve->_('certification.text.list.and');
		if (count($output_array) == 0)
			return '';
		else if (count($output_array) == 1)
			return $output_array[0];
		else // (count($output_array) >= 2)
			return implode($comma, array_slice($output_array, 0, count($output_array)-1)) . $and . $output_array[count($output_array)-1];
	}

	function certification_attribuition($certificationtemplate_id, $screenname, $submission_id, $locked = 0)
	{
		// TODO check if screenname and submissionid are valid to return more specific errors
		// Preparing insert statement
		$stmt2 = $this->eve->mysqli->prepare
		("
			insert
			into `{$this->eve->DBPref}certification` (`certification_model_id`, `screenname`, `submissionid`, `locked`)
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
			into `{$this->eve->DBPref}certification` (`certification_model_id`, `screenname`, `submissionid`, `locked`)
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
			into `{$this->eve->DBPref}certification` (`certification_model_id`, `screenname`, `locked`)
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

	function certification_list()
	{
		$certification_res = $this->eve->mysqli->query
		("
			SELECT
				`{$this->eve->DBPref}certification`.`id`,	
				`{$this->eve->DBPref}userdata`.`name`,
				`{$this->eve->DBPref}certification_model`.`name`, 
				`{$this->eve->DBPref}certification`.`locked`,
				`{$this->eve->DBPref}certification`.`views`
			FROM
				`{$this->eve->DBPref}certification_model`,
				`{$this->eve->DBPref}certification`,
				`{$this->eve->DBPref}userdata`
			WHERE
				`{$this->eve->DBPref}certification`.`screenname` = `{$this->eve->DBPref}userdata`.`email`
			AND
				`{$this->eve->DBPref}certification_model`.`id` = `{$this->eve->DBPref}certification`.`certification_model_id`
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
				`{$this->eve->DBPref}certification_model`.`name`,
				`{$this->eve->DBPref}certification_model`.`hasopenermsg`
			FROM
				`{$this->eve->DBPref}certification`,
				`{$this->eve->DBPref}certification_model`
			WHERE
				`{$this->eve->DBPref}certification`.`certification_model_id` = `{$this->eve->DBPref}certification_model`.`id` 
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
				`{$this->eve->DBPref}certification_model`.`name`
			from
				`{$this->eve->DBPref}certification`,
				`{$this->eve->DBPref}certification_model`
			where
				`{$this->eve->DBPref}certification`.`certification_model_id` = `{$this->eve->DBPref}certification_model`.`id` 
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
			'$site_url' => $this->eve->sysurl()
		);
		$this->evemail->send_mail($certification['owner'], $placeholders, $this->eve->getSetting('email_sbj_certification'), $this->eve->getSetting('email_msg_certification'));
	}

	function certificationmodel_create($name = "")
	{
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}certification_model`
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
			delete from `{$this->eve->DBPref}certification_model`
			where `{$this->eve->DBPref}certification_model`.`id` = ?
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
		if($this->certificationmodel_get($id) === null)
			return self::CERTIFICATIONMODEL_DUPLICATE_ERROR_INVALID_ID;
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}certification_model` 
				(`type`, `name`, `text`, `backgroundimage`, `topmargin`, `leftmargin`, `rightmargin`, `text_lineheight`, `text_fontsize`, `text_font`, `text_alignment`, `hasopenermsg`, `openermsg` )
				select `type`, `name`, `text`, `backgroundimage`, `topmargin`, `leftmargin`, `rightmargin`, `text_lineheight`, `text_fontsize`, `text_font`, `text_alignment`, `hasopenermsg`, `openermsg`
				from `{$this->eve->DBPref}certification_model` where `{$this->eve->DBPref}certification_model`.`id` = ?;
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
			from   `{$this->eve->DBPref}certification_model`
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
			$certificationmodel['text_font'],
			$certificationmodel['text_alignment'],
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

	function certificationmodel_list()
	{
		return $this->eve->mysqli->query
		("	
			select *
			from `{$this->eve->DBPref}certification_model`;
		");
	}

	const CERTIFICATION_MODEL_SAVE_ERROR_SQL = 'certification.model.save.error.sql';
	const CERTIFICATION_MODEL_SAVE_SUCCESS = 'certification.model.save.success';

	function certificationmodel_save($certification_model)
	{
		$stmt1 = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}certification_model`
			set		`{$this->eve->DBPref}certification_model`.`type` = ?,
					`{$this->eve->DBPref}certification_model`.`name` = ?,
					`{$this->eve->DBPref}certification_model`.`pagesize` = ?,
					`{$this->eve->DBPref}certification_model`.`pageorientation` = ?,
					`{$this->eve->DBPref}certification_model`.`backgroundimage` = ?,
					`{$this->eve->DBPref}certification_model`.`text` = ?,
					`{$this->eve->DBPref}certification_model`.`topmargin` = ?,
					`{$this->eve->DBPref}certification_model`.`leftmargin` = ?,
					`{$this->eve->DBPref}certification_model`.`rightmargin` = ?,
					`{$this->eve->DBPref}certification_model`.`text_lineheight` = ?,
					`{$this->eve->DBPref}certification_model`.`text_fontsize` = ?,
					`{$this->eve->DBPref}certification_model`.`text_font` = ?,
					`{$this->eve->DBPref}certification_model`.`text_alignment` = ?,
					`{$this->eve->DBPref}certification_model`.`hasopenermsg` = ?,
					`{$this->eve->DBPref}certification_model`.`openermsg` = ?
			where	`{$this->eve->DBPref}certification_model`.`id` = ?
		");
		if ($stmt1 === false)
		{
			return self::CERTIFICATION_MODEL_SAVE_ERROR_SQL;
		}
		$stmt1->bind_param('ssssssiiiiissisi', 
			$certification_model['type'], $certification_model['name'],
			$certification_model['pagesize'], $certification_model['pageorientation'],
			$certification_model['backgroundimage'], $certification_model['text'],
			$certification_model['topmargin'], $certification_model['leftmargin'],
			$certification_model['rightmargin'], $certification_model['text_lineheight'],
			$certification_model['text_fontsize'], $certification_model['text_font'],
			$certification_model['text_alignment'], $certification_model['hasopenermsg'],
			$certification_model['openermsg'], $certification_model['id']
			);
		$stmt1->execute();
		if (!empty($stmt1->error))
		{
			$stmt1->close();
			return self::CERTIFICATION_MODEL_SAVE_ERROR_SQL;
		}
		else
		{
			$stmt1->close();
			return self:: CERTIFICATION_MODEL_SAVE_SUCCESS;
		}
	}

	/** Returns list of possible values for the field 'text_alignment' of a certification model */
	function certificationmodel_textalignments()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certification_model` WHERE Field = 'text_alignment'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	/** Returns list of possible values for the field 'pageorientation' of a certification model */
	function certificationmodel_pageorientations()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certification_model` WHERE Field = 'pageorientation'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	/** Returns list of possible values for the field 'pagesize' of a certification model */
	function certificationmodel_pagesizes()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certification_model` WHERE Field = 'pagesize'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}
	
	/** Returns list of possible values for the field 'type' of a certification model */
	function certificationmodel_types()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}certification_model` WHERE Field = 'type'";
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
