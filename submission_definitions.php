<?php
session_start();
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
// If there are post actions, do the actions and then reload the page without 
// post actions.
else if (isset($_POST['action'])) switch ($_POST['action'])
{
	case "create":
		$message = $eveSubmissionService->submission_definition_create($_POST['description']);
		$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
	case "delete":
		$message = $eveSubmissionService->submission_definition_delete($_POST['id']);
		$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
}
// If there's a valid session, and the current user is administrator and there are no
// actions, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), null);

	?>
	<div class="section">		
	<button type="button" onclick="create_submission_definition()"><?php echo $eve->_('submission_definitions.button.create'); ?></button>
	</div>
	<?php
	
	if (isset($_GET['message'])) switch($_GET['message'])
	{
		case EveSubmissionService::SUBMISSION_DEFINITION_CREATE_ERROR_SQL:
			$eve->output_error_message('submission_definitions.message.create.error.sql');
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_CREATE_SUCCESS:
			$eve->output_success_message('submission_definitions.message.create.success');
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_DELETE_ERROR_SQL:
			$eve->output_error_message('submission_definitions.message.delete.error.sql');
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_DELETE_SUCCESS:
			$eve->output_success_message('submission_definitions.message.delete.success');
			break;
	}
	
	?>
	<table class="data_table">
	<tr>
	<th style="width:05%"><?php echo $eve->_('submission_definition.id');?></th>
	<th style="width:40%"><?php echo $eve->_('submission_definition.description');?></th>
	<th style="width:25%"><?php echo $eve->_('submission_definition.deadline');?></th>
	<th style="width:10%"><?php echo $eve->_('submission_definition.access_restricted');?></th>
	<th style="width:10%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	$submission_definitions = $eveSubmissionService->submission_definition_list();
	while ($submission_definition = $submission_definitions->fetch_assoc())	
	{	
		// TODO format deadline with eveg11n.class.php
		$deadline = ($submission_definition['deadline'])? $submission_definition['deadline'] : $eve->_('submission_definition.deadline.null');
		$access_restricted = ($submission_definition['access_restricted'])? "&#8226;" : "";
		echo "<tr>";
		echo "<td style=\"text-align:center\">{$submission_definition['id']}</td>";
		echo "<td style=\"text-align:left\">{$submission_definition['description']}</td>";
		echo "<td style=\"text-align:center\">$deadline</td>";
		echo "<td style=\"text-align:center\">$access_restricted</td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='submissions.php?id={$submission_definition['id']}'\"><img src=\"style/icons/view.png\"></button></td>";		
		echo "<td><button type=\"button\" onclick=\"window.location.href='submission_definition.php?id={$submission_definition['id']}'\"><img src=\"style/icons/edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"delete_submission_definition({$submission_definition['id']})\"><img src=\"style/icons/delete.png\"></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
	function delete_submission_definition(submission_definition_id)
	{
		var raw_message = '<?php echo $eve->_("submission_definitions.message.delete")?>';
		var message = raw_message.replace("<ID>", submission_definition_id)	
		if (confirm(message))
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'delete');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'id');
        	var2.setAttribute('value', submission_definition_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();
		}
		return false;
	}
	function create_submission_definition()
	{
		var message = '<?php echo $eve->_("submission_definitions.message.create")?>';
		var submission_definition_description = prompt(message);
		if (submission_definition_description != null)
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'create');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'description');
        	var2.setAttribute('value', submission_definition_description);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();
		}
		return false;
	}	
	</script>
	<?php
	$eve->output_html_footer();
}
?>
