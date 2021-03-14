<?php
require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'eve.class.php';
require_once 'evemail.class.php';


class EveSubmissionService
{
	private $eve;
	private $evemail;

	const SUBMISSION_CREATE_SUCCESS = 'submission.create.success';
	const SUBMISSION_CREATE_ERROR_SQL = 'submission.create.error.sql';
	const SUBMISSION_DELETE_ERROR_INVALID_ID = 'submission.delete.error.invalid.id';
	const SUBMISSION_DELETE_ERROR_SQL = 'submission.delete.error.sql';
	const SUBMISSION_DELETE_ERROR_FORBIDDEN = 'submission.delete.error.forbidden';
	const SUBMISSION_DELETE_SUCCESS = 'submission.delete.success';
	const SUBMISSION_UPDATE_ERROR_INVALID_ID = 'submission.update.error.invalid.id';
	const SUBMISSION_UPDATE_ERROR_FORBIDDEN = 'submission.update.error.forbidden';
	const SUBMISSION_UPDATE_ERROR_SQL = 'submission.update.error.sql';
	const SUBMISSION_UPDATE_SUCCESS = 'submission.update.success';

	const SUBMISSION_SET_REVIEWER_ERROR_INVALID_IDS = 'submission.set.reviewer.error.invalid.ids';
	const SUBMISSION_SET_REVIEWER_ERROR_INVALID_REVIEWER = 'submission.set.reviewer.error.invalid.reviewer';
	const SUBMISSION_SET_REVIEWER_ERROR_SQL = 'submission.set.reviewer.error.sql';
	const SUBMISSION_SET_REVIEWER_SUCCESS = 'submission.set.reviewer.success';

	const SUBMISSION_REVIEW_ERROR_SQL = 'submission.review.error.sql';
	const SUBMISSION_REVIEW_SUCCESS = 'submission.review.success';

	const SUBMISSION_DEFINITION_CREATE_ERROR_SQL = 'submission.definition.create.error.sql';
	const SUBMISSION_DEFINITION_CREATE_SUCCESS = 'submission.definition.create.success';
	const SUBMISSION_DEFINITION_DELETE_ERROR_SQL  = 'submission.definition.delete.error.sql';
	const SUBMISSION_DEFINITION_DELETE_SUCCESS = 'submission.definition.delete.success';

	const SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_INVALID_EMAIL = 'submission.definition.access.create.error.invalid.email';
	const SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_USER_DOES_NOT_EXIST = 'submission.definition.access.create.error.user.does.not.exist';
	const SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_SQL = 'submission.definition.access.create.error.sql';
	const SUBMISSION_DEFINITION_ACCESS_CREATE_SUCCESS = 'submission.definition.access.create.success';
	const SUBMISSION_DEFINITION_ACCESS_DELETE_ERROR_SQL = 'submission.definition.access.delete.error.sql';
	const SUBMISSION_DEFINITION_ACCESS_DELETE_SUCCESS = 'submission.definition.access.delete.success';

    const SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_INVALID_EMAIL = 'submission.definition.reviewer.add.error.invalid.email';
	const SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_USER_DOES_NOT_EXIST = 'submission.definition.reviewer.add.error.user.does.not.exist';
	const SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_INVALID_TYPE = 'submission.definition.reviewer.add.error.invalid.type';
	const SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_REVIEWER_ALREADY_EXISTS = 'submission.definition.reviewer.add.error.reviewer.already.exists';
	const SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_SQL = 'submission.definition.reviewer.add.error.sql';
	const SUBMISSION_DEFINITION_REVIEWER_ADD_SUCCESS = 'submission.definition.reviewer.add.success';
	const SUBMISSION_DEFINITION_REVIEWER_DELETE_ERROR_SQL = 'submission.definition.reviewer.delete.error.sql';
	const SUBMISSION_DEFINITION_REVIEWER_DELETE_SUCCESS = 'submission.definition.reviewer.delete.success';

	const SUBMISSION_DEFINITION_SAVE_ERROR_SQL = 'submission_definition.message.save.error.sql';
	const SUBMISSION_DEFINITION_SAVE_SUCCESS = 'submission_definition.message.save.success';

	/** Check if user with given $email has permission to post new submissions in the given submission definition $id */
	function submission_definition_user_access_permitted($id, $email)
	{
		$submission_definition = $this->submission_definition_get($id);

		if ($submission_definition === null)
		{	
			// There is no submission definition and therefore access is denied
			return false;
		}
		else if ($this->eve->is_admin($email))
		{
			// Submission definition exists. Since user is admin, access is granted
			return true;
		}
		else if ($submission_definition['active'] == 0)
		{	
			// Submission definition exists, user is not admin.
			// Since submission_definition is not active (it has been deleted), access is denied
			return false;
		}
		else if ($submission_definition['access_restricted'] == 1)
		{
			// Submission definition exists and is active, user is not admin
			// submission_definition has restricted access, verify if user meets the restrictions

			$stmt1 = $this->eve->mysqli->prepare
			("
				select	* 
				from	`{$this->eve->DBPref}submission_definition_access`
				where	`{$this->eve->DBPref}submission_definition_access`.`submission_definition_id` = ? and
					`{$this->eve->DBPref}submission_definition_access`.`type` = 'specific_user' and			
					`{$this->eve->DBPref}submission_definition_access`.`content` = ?;
			");
			if ($stmt1 === false)
			{
				trigger_error($this->eve->mysqli->error, E_USER_ERROR);
				return false;
			}
			$stmt1->bind_param('is', $id, $email);
			$stmt1->execute();
			$stmt1->store_result();
			if ($stmt1->num_rows > 0)
			{
				$stmt1->close();
				return true;
			}
			else
			{
				return false;
			}			
		}
		else
		{
			// Submission definition exists, is active, has no restrict access. User is not admin.
			return true;
		}
	}

	/* Returns true if user $email can submit a submission after deadline */
	function submission_after_deadline_allowed($submission_definition_id, $email) 
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select	count(*)
			from	`{$this->eve->DBPref}submission_definition_access`
			where	`type` = 'submission_after_deadline' and
				`content` = ? and
				`submission_definition_id` = ?
		");
		$stmt->bind_param('si', $email, $submission_definition_id);
		$stmt->execute();
		$result = null;
		$stmt->bind_result($result); // Since it is a select count(*) there will be only one column
		$stmt->fetch(); // Since it is a select count(*) there will be only one row (there is no need of a while loop)
		return ($result > 0);
	}

	function submission_create($submission_definition_id, $email, $dynamicform_submission)
	{
		$date_sql = date("Y-m-d H:i:s");
		$structure = $dynamicform_submission->getJSONStructure();
		$content = $dynamicform_submission->getJSONContent();
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}submission`
				(`submission_definition_id`, `structure`, `email`, `date`, `content`)
			values	(?, ?, ?, ?, ?)
		");
		if ($stmt === false)
		{
			return self::SUBMISSION_CREATE_ERROR_SQL;
		}
		$stmt->bind_param('issss', $submission_definition_id, $structure, $email, $date_sql, $content);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::SUBMISSION_CREATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			$submission_definition = $this->submission_definition_get($submission_definition_id);
			if ($submission_definition['send_email_on_create'])
			{
				$placeholders = array
				(
					'$user_email' => $email,
					'$date_time' => $date_sql, //TODO g11n and $user_name
					'$support_email_address' => $this->eve->getSetting('support_email_address'),
					'$system_name' => $this->eve->getSetting('system_name'),
					'$site_url' => $this->eve->sysurl(),
					'$submission_content' => $dynamicform_submission->getHtmlFormattedContent()
				);
				$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('email_sbj_submission_create'), $this->eve->getSetting('email_msg_submission_create'));
			}
			return self::SUBMISSION_CREATE_SUCCESS;
		}
	}
	
	// User $email is passed in order to check permissions. User has to be the owner of submission or admin
	function submission_delete($submission_id, $email)
	{
		$submission = $this->submission_get($submission_id);
		if ($submission === null)
		{
			// Returning error if $id is invalid
			return self::SUBMISSION_DELETE_ERROR_INVALID_ID;
		}
		else if ($submission['email'] != $email && !$this->eve->is_admin($email))
		{
			// Returning error if $email does not have permission to delete
			// (if they are not the owner and are not admin)
			return self::SUBMISSION_DELETE_ERROR_FORBIDDEN;
		}
		else 
		{	
			// All verifications are okay. Proceeding with delete
			$stmt1 = $this->eve->mysqli->prepare
			("
				update	`{$this->eve->DBPref}submission`
				set		`{$this->eve->DBPref}submission`.`active` = 0
				where	`{$this->eve->DBPref}submission`.`id` = ?
			");
			if ($stmt1 === false)
			{
				return self::SUBMISSION_DELETE_ERROR_SQL;
			}
			$stmt1->bind_param('i', $submission_id);
			$stmt1->execute();
			$stmt1->close();
			$submission_definition = $this->submission_definition_get($submission['submission_definition_id']);
			if ($submission_definition['send_email_on_delete'])
			{
				$dynamicform_submission = new DynamicForm($submission['structure'],json_decode($submission['content']));
				$placeholders = array
				(
					'$user_email' => $email,
					'$date_time' => date("Y-m-d H:i:s"), //TODO g11n and $user_name
					'$support_email_address' => $this->eve->getSetting('support_email_address'),
					'$system_name' => $this->eve->getSetting('system_name'),
					'$site_url' => $this->eve->sysurl(),
					'$submission_content' => $dynamicform_submission->getHtmlFormattedContent()
				);
				$this->evemail->send_mail($email, $placeholders, $this->eve->getSetting('email_sbj_submission_delete'), $this->eve->getSetting('email_msg_submission_delete'));
			}
			return self::SUBMISSION_DELETE_SUCCESS;
		}
	}			

	/* Returns null if id is invalid or nonexistent */
	function submission_get($id) 
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}submission`
			where  `id`=?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$submission = array();
		$stmt->bind_result
		(
			$submission['id'],
			$submission['submission_definition_id'],
			$submission['structure'],
			$submission['email'],
			$submission['date'],
			$submission['content'],
			$submission['reviewer_email'],
			$submission['revision_structure'],
			$submission['revision_content'],
			$submission['revision_status'],
			$submission['access'],
			$submission['active']
		);
		// Fetching values
		if ($stmt->fetch())
		{
			$stmt->close();
			return $submission;
		}
		else
		{
			$stmt->close();
			return null;
		}	
	}
	
	/**
	 * List all the submissions for the submission definition referred by
	 * `$submission_definition_id` according to the `$access_mode`. If `$access_mode`
	 * == admin' or 'final_reviewer', this function returns all active submissions.
	 * If 'reviewer', returns all the submissions for the reviewer set in `$email`. If
	 * 'owner', returns all the active submissionsm the user `$email` has sent.
	 * 
	 * @param String $access_mode Should be one of the following values:
	 * 'admin', 'final_reviewer', 'reviewer' and 'owner'
	 * */
	function submission_list($submission_definition_id, $access_mode = 'admin', $email = null)
	{
		$access_constraints = "";
		switch ($access_mode)
		{		
			case 'admin':
			case 'final_reviewer':
				$access_constraints = "";
				break;
			case 'reviewer':
				$access_constraints =  "and `{$this->eve->DBPref}submission`.`reviewer_email`= ?";
				break;
			case 'owner':
				$access_constraints =  "and `{$this->eve->DBPref}submission`.`email`= ?";
				break;
		}

		$query = 
		"
			select 	`{$this->eve->DBPref}submission`.`id`,
				`{$this->eve->DBPref}submission`.`submission_definition_id`,
				`{$this->eve->DBPref}submission`.`date`,
				`{$this->eve->DBPref}submission`.`email`,
				`{$this->eve->DBPref}userdata`.`name`,				
				`{$this->eve->DBPref}submission`.`structure`,				
				`{$this->eve->DBPref}submission`.`content`,
				`{$this->eve->DBPref}submission`.`reviewer_email`,
				`{$this->eve->DBPref}submission`.`revision_structure`,
				`{$this->eve->DBPref}submission`.`revision_content`,
				`{$this->eve->DBPref}submission`.`revision_status`,
				`{$this->eve->DBPref}submission`.`active`				
			from	`{$this->eve->DBPref}submission`, `{$this->eve->DBPref}userdata`
			where 	`{$this->eve->DBPref}submission`.`email` = `{$this->eve->DBPref}userdata`.`email` and
				`{$this->eve->DBPref}submission`.`submission_definition_id`= ? and
				`{$this->eve->DBPref}submission`.`active` = 1
				$access_constraints 
		";		
		$list = array();
		$stmt = $this->eve->mysqli->prepare($query);
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		if (in_array($access_mode, array('admin', 'final_reviewer')))
			$stmt->bind_param('i', $submission_definition_id);
		else
			$stmt->bind_param('is', $submission_definition_id, $email);
		$stmt->execute();
   		$stmt->bind_result
		(
			$id,
			$submission_definition_id,
			$date,
			$email,
			$name,			
			$structure,
			$content,
			$reviewer_email, 
			$revision_structure,
			$revision_content,
			$revision_status,
			$active
		);
		while ($stmt->fetch())
		{
			$list[] = array ('id' => $id, 'submission_definition_id' => $submission_definition_id, 'date' => $date, 'email' => $email, 'name' => $name, 'structure' => $structure, 'content' => $content, 'reviewer_email' => $reviewer_email, 'revision_structure' => $revision_structure, 'revision_content' => $revision_content, 'revision_status' => $revision_status,'active' => $active);
		}
		$stmt->close();
		return $list;		
	}

	/* Revision parameters passed as a DynamicForm */ 
	function submission_review($submission_id, $reviewer, $contentDynamicForm)
	{
		$submission = $this->submission_get($submission_id);
		if 
		(
			($submission['revision_status'] == 0 && $submission['reviewer_email'] == $reviewer) ||
			($submission['revision_status'] == 1 && $this->is_final_reviewer($reviewer, $submission['submission_definition_id']))
		)
		{
			$new_status = ($submission['revision_status'] == 0)? 1 : 2;
			$stmt = $this->eve->mysqli->prepare
			("
				update	`{$this->eve->DBPref}submission`
				set 	`{$this->eve->DBPref}submission`.`revision_structure` = ?,
						`{$this->eve->DBPref}submission`.`revision_content` = ?,
						`{$this->eve->DBPref}submission`.`revision_status` = ?
				where 	`{$this->eve->DBPref}submission`.`id` = ?
			");
			if ($stmt === false)
			{
				return self::SUBMISSION_REVIEW_ERROR_SQL;
			}
			$revision_structure_json = $contentDynamicForm->getJSONStructure();
			$revision_content_json = $contentDynamicForm->getJSONContent();
			$stmt->bind_param('ssii', $revision_structure_json, $revision_content_json, $new_status, $submission_id);
			$stmt->execute();
			if (!empty($stmt->error))
			{
				$stmt->close();
				return self::SUBMISSION_REVIEW_ERROR_SQL;
			}
			$stmt->close();

			if (($new_status == 1 & empty($this->submission_definition_reviewers($submission['submission_definition_id'], 'final_reviewer'))) || ($new_status == 2))
			{
				// Sending e-mail
				if ($this->eve->getSetting('email_snd_revision'))
				{
					$submissionDynamicForm = new DynamicForm($submission['structure'], json_decode($submission['content']));
					$submission_formatted_content = $submissionDynamicForm->getHtmlFormattedContent();
					$revision_formatted_content = $contentDynamicForm->getHtmlFormattedContent();
					$placeholders = array
					(
						'$email' => $submission['email'],
						'$support_email_address' => $this->eve->getSetting('support_email_address'),
						'$system_name' => $this->eve->getSetting('system_name'),
						'$site_url' => $this->eve->sysurl(),
						'$submission_content' => $submission_formatted_content,
						'$revision_content' => $revision_formatted_content,
					);
					$this->evemail->send_mail($submission['email'], $placeholders, $this->eve->getSetting('email_sbj_revision'), $this->eve->getSetting('email_msg_revision'));
				}
				return self::SUBMISSION_REVIEW_SUCCESS;
			}
		}
	}

	function submission_change_revision_status($submission_id, $new_status)
	{
		// Storing changes on database
		$stmt = $this->eve->mysqli->prepare
		("
			update `{$this->eve->DBPref}submission` set `revision_status` = ? where `id`=?;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false; // TODO return errorcode
		}
		$stmt->bind_param('ii', $new_status, $submission_id);
		$stmt->execute();
		if (!empty($stmt->error)) 
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return false; //TODO return errorcode
		}
		$stmt->close();
	}

	/**
	 * Sets the reviewer `$reviewer_screenname` for the submissions given by
	 * `$submission_ids`
	 * 
	 * @param array $submission_ids An array with the ids of submission definitions
	 * @param String $reviewer_screenname The screenname of the reviewer
	 * @return String Success/Error message
	 */
	function submission_set_reviewer($submission_ids, $reviewer_screenname)
	{
		// Validating parameters
		if (!is_array($submission_ids))
			return self::SUBMISSION_SET_REVIEWER_ERROR_INVALID_IDS;
		foreach ($submission_ids as $submission_id)
			if (!is_numeric($submission_id))
				return self::SUBMISSION_SET_REVIEWER_ERROR_INVALID_IDS;
		if (!$this->eve->user_exists($reviewer_screenname))
			return self::SUBMISSION_SET_REVIEWER_ERROR_INVALID_REVIEWER;
		
		// Starting transaction. If something goes wrong will do a rollback and
		// Return an error message. This function will only commit the changes and
		// Return a success message if all updates are successful.
		$this->eve->mysqli->autocommit(FALSE);
		$this->eve->mysqli->begin_transaction();
		// Preparing statement
		$stmt = $this->eve->mysqli->prepare
		("
			update `{$this->eve->DBPref}submission` set `reviewer_email` = ? where `id`=?;
		");
		if ($stmt === false)
		{
			$this->eve->mysqli->rollback();
			$this->eve->mysqli->autocommit(TRUE);
			return self::SUBMISSION_SET_REVIEWER_ERROR_SQL;
		}
		foreach ($submission_ids as $submission_id)
		{
			$stmt->bind_param('si', $reviewer_screenname, $submission_id);
			$stmt->execute();
			if (!empty($stmt->error))
			{
				$this->eve->mysqli->rollback();
				$this->eve->mysqli->autocommit(TRUE);
				$stmt->close();
				return self::SUBMISSION_SET_REVIEWER_ERROR_SQL;
			}
		}
		$this->eve->mysqli->commit();
		$stmt->close();

		// Sending e-mail to reviewer
		if ($this->eve->getSetting('email_snd_reviewer'))
		{
			DynamicFormHelper::$locale = $this->eve->getSetting('system_locale');
			foreach ($submission_ids as $submission_id) {
				$submission = $this->submission_get($submission_id);
				$dynamicForm = new DynamicForm($submission['structure'], json_decode($submission['content']));
				// Removing fields not visible to the reviewer
				foreach ($dynamicForm->structure as $i => $dynamicFormSubmissionItem)
				if ($dynamicFormSubmissionItem->customattribute == 'noreview')
					unset($dynamicForm->structure[$i]);
				$submission_formatted_content = $dynamicForm->getHtmlFormattedContent();
				$placeholders = array
				(
					'$email' => $reviewer_screenname,
					'$support_email_address' => $this->eve->getSetting('support_email_address'),
					'$system_name' => $this->eve->getSetting('system_name'),
					'$site_url' => $this->eve->sysurl(),
					'$submission_content' => $submission_formatted_content
				);
				$this->evemail->send_mail($reviewer_screenname, $placeholders, $this->eve->getSetting('email_sbj_reviewer'), $this->eve->getSetting('email_msg_reviewer'));
			}
		}
		return self::SUBMISSION_SET_REVIEWER_SUCCESS;
	}

	/**
	 * $author_email - who made the update
	 */
	function submission_update($submission_id, $dynamicform_submission, $author_email)
	{
		$submission = $this->submission_get($submission_id);
		$submission_definition = $this->submission_definition_get($submission['submission_definition_id']);
		if ($submission === null || $submission_definition === null)
		{
			// Returning error if $id is invalid
			return self::SUBMISSION_UPDATE_ERROR_INVALID_ID;
		}
		else if
		(
			!$this->eve->is_admin($author_email) &&
			!$this->is_final_reviewer($author_email, $submission['submission_definition_id']) &&
			$submission['reviewer_email'] != $author_email
		)
		{
			// Returning error if $author_email does not have permission to update
			// The ones who can update are system admins, final reviewers of the
			// submission definition and the reviewer of the current submission.
			return self::SUBMISSION_UPDATE_ERROR_FORBIDDEN;
		}
		else 
		{	
			// All verifications are okay. Proceeding with update
			$stmt1 = $this->eve->mysqli->prepare
			("
				update	`{$this->eve->DBPref}submission`
				set		`{$this->eve->DBPref}submission`.`content` = ?
				where	`{$this->eve->DBPref}submission`.`id` = ?
			");
			if ($stmt1 === false)
			{
				return self::SUBMISSION_UPDATE_ERROR_SQL;
			}
			$new_content = $dynamicform_submission->getJSONContent();
			$stmt1->bind_param('si', $new_content, $submission_id);
			$stmt1->execute();
			if (!empty($stmt1->error))
			{
				$stmt1->close();
				return self::SUBMISSION_UPDATE_ERROR_SQL;
			}
			else
			{
				$stmt1->close();
				if ($submission_definition['send_email_on_update'])
				{
					$dynamicform_submission_old = new DynamicForm($submission['structure'],json_decode($submission['content']));
					$placeholders = array
					(
						'$user_email' => $author_email,
						'$date_time' => date("Y-m-d H:i:s"), //TODO g11n and $user_name
						'$support_email_address' => $this->eve->getSetting('support_email_address'),
						'$system_name' => $this->eve->getSetting('system_name'),
						'$site_url' => $this->eve->sysurl(),
						'$submission_content' => $dynamicform_submission->getHtmlFormattedContent(),
						'$submission_old_content' => $dynamicform_submission_old->getHtmlFormattedContent() //TODO display diff instead
					);
					$this->evemail->send_mail($submission['email'], $placeholders, $this->eve->getSetting('email_sbj_submission_update'), $this->eve->getSetting('email_msg_submission_update'));
				}
				return self::SUBMISSION_UPDATE_SUCCESS;
			}
		}
	}

	function submission_definition_create($description)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			insert into `{$this->eve->DBPref}submission_definition` (`description`)
			values (?)
		");
		if ($stmt === false)
		{
			return self::SUBMISSION_DEFINITION_CREATE_ERROR_SQL;
		}
		$stmt->bind_param('s', $description);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_CREATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_CREATE_SUCCESS;
		}
	}

	function submission_definition_delete($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}submission_definition`
			set 	`{$this->eve->DBPref}submission_definition`.`active` = 0
			where 	`{$this->eve->DBPref}submission_definition`.`id` = ?
		");
		if ($stmt === false)
		{
			return self::SUBMISSION_DEFINITION_DELETE_ERROR_SQL;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_DELETE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_DELETE_SUCCESS;
		}
	}

	/* Returns null if id is invalid or nonexistent */
	function submission_definition_get($id) 
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select * 
			from   `{$this->eve->DBPref}submission_definition`
			where  `id`=?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$submission_definition = array();
		$stmt->bind_result
		(
			$submission_definition['id'],
			$submission_definition['description'],
			$submission_definition['information'],
			$submission_definition['requirement'],
			$submission_definition['allow_multiple_submissions'],
			$submission_definition['deadline'],
			$submission_definition['submission_structure'],
			$submission_definition['revision_structure'],
			$submission_definition['access_restricted'],
			$submission_definition['send_email_on_create'],
			$submission_definition['send_email_on_delete'],
			$submission_definition['send_email_on_update'],
			$submission_definition['active']
		);
		// Fetching values
		if ($stmt->fetch())
		{
			$stmt->close();
			return $submission_definition;
		}
		else
		{
			$stmt->close();
			return null;
		}	
	}

	function submission_definition_list()
	{	// TODO prepared statements
		return $this->eve->mysqli->query
		("
			select *
			from `{$this->eve->DBPref}submission_definition`
			where `{$this->eve->DBPref}submission_definition`.`active` = 1
			order by `{$this->eve->DBPref}submission_definition`.`deadline`;
		");
	}

	function submission_definition_list_for_reviewer($screenname, $reviewer_type)
	{	
		// TODO prepared statements
		$submission_definitions = array();
		$submission_definitions_res = $this->eve->mysqli->query
		("
			select distinct	
					`{$this->eve->DBPref}submission_definition_reviewer`.`submission_definition_id`
			from 	`{$this->eve->DBPref}submission_definition_reviewer`,
					`{$this->eve->DBPref}submission_definition`
			where	`{$this->eve->DBPref}submission_definition`.`active` = 1 and
					`{$this->eve->DBPref}submission_definition_reviewer`.`submission_definition_id` = `{$this->eve->DBPref}submission_definition`.`id` and
					`{$this->eve->DBPref}submission_definition_reviewer`.`type` = '$reviewer_type' and
					`{$this->eve->DBPref}submission_definition_reviewer`.`email` = '$screenname'
			order by `{$this->eve->DBPref}submission_definition_reviewer`.`submission_definition_id`;
		");
		while ($submssion_definition = $submission_definitions_res->fetch_assoc()) $submission_definitions[] = $submssion_definition;
		return $submission_definitions;
	}

	/** Returns list of possible values for the field 'requirement' of a submission definition */
	function submission_definition_requirements()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}submission_definition` WHERE Field = 'requirement'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	function submission_definition_access_create($submission_definition_id, $type, $content)
	{
		// Validation			
		switch ($type)
		{
			case 'specific_user':
			case 'submission_after_deadline':
				if (!filter_var($content, FILTER_VALIDATE_EMAIL))
					return self::SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_INVALID_EMAIL;
				else if (!$this->eve->user_exists($content))
					return self::SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_USER_DOES_NOT_EXIST;
				break;
		}
				
		// Data successfully validated - creating new submission_definition_access	
		$stmt = $this->eve->mysqli->prepare
		("
			insert into
				`{$this->eve->DBPref}submission_definition_access`
				(`submission_definition_id`, `type`, `content`)
			values (?, ?, ?)
		");
		if ($stmt === false)
		{
			return self::SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_SQL;
		}
		$stmt->bind_param('iss', $submission_definition_id, $type, $content);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_ACCESS_CREATE_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_ACCESS_CREATE_SUCCESS;
		}
	}

	function submission_definition_access_delete($submission_definition_access_id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			delete from `{$this->eve->DBPref}submission_definition_access` where `id`=?
		");
		if ($stmt === false)
		{
			return self::SUBMISSION_DEFINITION_ACCESS_DELETE_ERROR_SQL;
		}
		$stmt->bind_param('i', $submission_definition_access_id);
		$stmt->execute();
		$stmt->close();
		return self::SUBMISSION_DEFINITION_ACCESS_DELETE_SUCCESS;
	}

	function submission_definition_access_list($submission_definition_id)
	{	
		// TODO PREPARED STATEMENTS
		$result = array();
		$access_res = $this->eve->mysqli->query
		("
			SELECT	`{$this->eve->DBPref}submission_definition_access`.*
			FROM	`{$this->eve->DBPref}submission_definition_access`
			WHERE	`{$this->eve->DBPref}submission_definition_access`.`submission_definition_id` = $submission_definition_id
		");
		while ($access = $access_res->fetch_assoc())
			$result[] = $access;
		return $result;
	}

	/** Returns list of possible values for the field 'type' of a submission_definition_access */
	function submission_definition_access_types()
	{
		$query = "SHOW COLUMNS FROM `{$this->eve->DBPref}submission_definition_access` WHERE Field = 'type'";
		$result = $this->eve->mysqli->query($query);
		$row = $result->fetch_assoc();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		$enum = str_getcsv($matches[1], ",", "'");
		return $enum;
	}

	/** Possible values for $type: 'reviewer' and 'final_reviewer'*/
	function submission_definition_reviewer_add($submission_definition_id, $email, $type)
	{
		// Validating e-mail
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_INVALID_EMAIL;

		// Checking if e-mail represents a valid user
		if (!$this->eve->user_exists($email))
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_USER_DOES_NOT_EXIST;

		// Checking type
		$submission_definition_reviewer_types = array('reviewer', 'final_reviewer'); // TODO: should this be static?
		if (!in_array($type, $submission_definition_reviewer_types))
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_INVALID_TYPE;
		
		// Checking if reviwer already exists
		$stmt1 = $this->eve->mysqli->prepare
		("
			select	count(*)
			from	`{$this->eve->DBPref}submission_definition_reviewer`
			where	`submission_definition_id` = ? and
				`email` = ? and
				`type` = ?
		");
		$stmt1->bind_param('iss', $submission_definition_id, $email, $type);
		$stmt1->execute();
		$result = null;
		$stmt1->bind_result($result); // Since it is a select count(*) there will be only one column
		$stmt1->fetch(); // Since it is a select count(*) there will be only one row (there is no need of a while loop)
		if ($result > 0)
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_REVIEWER_ALREADY_EXISTS;
		$stmt1->close();

		// Adding reviewer		
		$stmt = $this->eve->mysqli->prepare
		("
			insert into
				`{$this->eve->DBPref}submission_definition_reviewer`
				(`submission_definition_id`, `email`, `type`)
			values	(?, ?, ?)
		");
		if ($stmt === false)
		{
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_SQL;
		}
		$stmt->bind_param('iss', $submission_definition_id, $email, $type);
		$stmt->execute();
		if (!empty($stmt->error))
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_SQL;
		}
		else
		{
			$stmt->close();
			return self::SUBMISSION_DEFINITION_REVIEWER_ADD_SUCCESS;
		}
	}

	function submission_definition_reviewer_delete($submission_definition_reviewer_id)
	{
		$stmt1 = $this->eve->mysqli->prepare
		("
			delete from	`{$this->eve->DBPref}submission_definition_reviewer`
			where		`{$this->eve->DBPref}submission_definition_reviewer`.`id` = ?
		");
		if ($stmt1 === false)
		{
			return self::SUBMISSION_DEFINITION_REVIEWER_DELETE_ERROR_SQL;
		}
		$stmt1->bind_param('i', $submission_definition_reviewer_id);
		$stmt1->execute();
		$stmt1->close();
		return self::SUBMISSION_DEFINITION_REVIEWER_DELETE_SUCCESS;
	}

	/** $type can be 'reviewer', 'final_reviewer' or 'all'. If any other string is passed it defaults to 'all' */
	function submission_definition_reviewers($id, $type = 'all')
	{	
		$type_sql_restriction = "";
		if ($type == 'reviewer')
			$type_sql_restriction = "and `{$this->eve->DBPref}submission_definition_reviewer`.`type` = 'reviewer'";
		else if ($type == 'final_reviewer')
			$type_sql_restriction = "and `{$this->eve->DBPref}submission_definition_reviewer`.`type` = 'final_reviewer'";
		$list = array();
		$stmt = $this->eve->mysqli->prepare
		("
			SELECT 	`{$this->eve->DBPref}submission_definition_reviewer`.*, `{$this->eve->DBPref}userdata`.`name`
			FROM 	`{$this->eve->DBPref}submission_definition_reviewer`, `{$this->eve->DBPref}userdata`
			WHERE	`{$this->eve->DBPref}submission_definition_reviewer`.`email` = `{$this->eve->DBPref}userdata`.`email` AND
				`{$this->eve->DBPref}submission_definition_reviewer`.`submission_definition_id`	= ? $type_sql_restriction
			ORDER BY `{$this->eve->DBPref}userdata`.`name`;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
    	$stmt->bind_result
		(
			$id,
			$submission_definition_id,
			$email,
			$type,
			$name
		);
		while ($stmt->fetch())
		{
			$list[] = array ('id' => $id, 'submission_definition_id' => $submission_definition_id, 'email' => $email, 'type' => $type, 'name' => $name);
		}
		$stmt->close();
		return $list;
	}

	function is_final_reviewer($email, $submission_definition_id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select	count(*)
			from	`{$this->eve->DBPref}submission_definition_reviewer`
			where	`type` = 'final_reviewer' and
				`email` = ? and
				`submission_definition_id` = ?
		");
		$stmt->bind_param('si', $email, $submission_definition_id);
		$stmt->execute();
		$result = null;
		$stmt->bind_result($result); // Since it is a select count(*) there will be only one column
		$stmt->fetch(); // Since it is a select count(*) there will be only one row (there is no need of a while loop)
		if (!empty($stmt->error))
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			$stmt->close();
			return false;
		}
		else
		{
			$stmt->close();
			return ($result > 0);
		}
	}

	function is_reviewer($email, $submission_definition_id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select	count(*)
			from	`{$this->eve->DBPref}submission_definition_reviewer`
			where	`type` = 'reviewer' and
				`email` = ? and
				`submission_definition_id` = ?
		");
		$stmt->bind_param('si', $email, $submission_definition_id);
		$stmt->execute();
		$result = null;
		$stmt->bind_result($result); // Since it is a select count(*) there will be only one column
		$stmt->fetch(); // Since it is a select count(*) there will be only one row (there is no need of a while loop)
		if (!empty($stmt->error))
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			$stmt->close();
			return false;
		}
		else
		{
			$stmt->close();
			return ($result > 0);
		}
	}

	/** Updates all submission definition's fields except for id and active */
	function submission_definition_save($submission_definition)
	{	
		// Verifying the consistency of $submission_definition['deadline'] since it is
		// passed as text. Any incorrect value may break the SQL query execution.
		// TODO Use DateTime::createFromFormat
		$submission_definition['deadline'] = (strtotime($submission_definition['deadline'])) ? $submission_definition['deadline'] : null;

		$stmt1 = $this->eve->mysqli->prepare
		("
			update	`{$this->eve->DBPref}submission_definition` 
			set	`description` = ?,
				`information` = ?,
				`requirement` = ?,
				`allow_multiple_submissions` = ?,
				`deadline` = ?,
				`submission_structure` = ?,
				`revision_structure` = ?,
				`access_restricted` = ?,
				`send_email_on_create` = ?,
				`send_email_on_delete` = ?,
				`send_email_on_update` = ?
			where
				`id` = ?
		");
		if ($stmt1 === false)
		{
			return self::SUBMISSION_DEFINITION_SAVE_ERROR_SQL;
		}
		$stmt1->bind_param('sssisssiiiii',
					$submission_definition['description'], $submission_definition['information'],
					$submission_definition['requirement'], $submission_definition['allow_multiple_submissions'],
					$submission_definition['deadline'], $submission_definition['submission_structure'],
					$submission_definition['revision_structure'], $submission_definition['access_restricted'],
					$submission_definition['send_email_on_create'], $submission_definition['send_email_on_delete'],
					$submission_definition['send_email_on_update'], $submission_definition['id']);
		$stmt1->execute();
		if (!empty($stmt1->error))
		{
			$stmt1->close();
			return self::SUBMISSION_DEFINITION_SAVE_ERROR_SQL;
		}
		else
		{
			$stmt1->close();
			return self::SUBMISSION_DEFINITION_SAVE_SUCCESS;
		}
	}


	function submission_definition_list_for_user($screenname, $requirement = 'none')
	{
		$list = array();
		$stmt = $this->eve->mysqli->prepare
		("
			SELECT `{$this->eve->DBPref}submission_definition`.`id`, `{$this->eve->DBPref}submission_definition`.`description`, `{$this->eve->DBPref}submission_definition`.`deadline`
			FROM 	`{$this->eve->DBPref}submission_definition`
			WHERE	`{$this->eve->DBPref}submission_definition`.`active` = 1 AND
				`{$this->eve->DBPref}submission_definition`.`access_restricted` = 0 AND
				`{$this->eve->DBPref}submission_definition`.`requirement` = ?

			UNION

			SELECT 	`{$this->eve->DBPref}submission_definition`.`id`, `{$this->eve->DBPref}submission_definition`.`description`, `{$this->eve->DBPref}submission_definition`.`deadline`
			FROM 	`{$this->eve->DBPref}submission_definition`, `{$this->eve->DBPref}submission_definition_access`
			WHERE	`{$this->eve->DBPref}submission_definition`.`active` = 1 AND
				`{$this->eve->DBPref}submission_definition`.`access_restricted` = 1 AND
				`{$this->eve->DBPref}submission_definition`.`requirement` = ? AND
				`{$this->eve->DBPref}submission_definition_access`.`submission_definition_id` = `{$this->eve->DBPref}submission_definition`.`id` AND
				`{$this->eve->DBPref}submission_definition_access`.`type` = 'specific_user' AND			
				`{$this->eve->DBPref}submission_definition_access`.`content` = ?

			ORDER BY `deadline`;
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
		}
		$stmt->bind_param('sss', $requirement, $requirement, $screenname);
		$stmt->execute();
		$submission_definition = array();
		$stmt->bind_result
		(
			$submission_definition['id'],
			$submission_definition['description'],
			$submission_definition['deadline']
		);
		while ($stmt->fetch())
		{
			$list[] = array ('id' => $submission_definition['id'], 'description' => $submission_definition['description'], 'deadline' => $submission_definition['deadline']);
		}
		$stmt->close();
		return $list;
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		$this->evemail = new EveMail($eve);
	}

}

?>
