<?php
session_start();
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
$submission_definition = (isset($_GET['id'])) ? $eveSubmissionService->submission_definition_get($_GET['id']) : null;

if (!isset($_SESSION['screenname']))
{	
	// If there is no session, redirect to front page
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if ($submission_definition == null)
{
	// If $_GET['id'] is not passed or does not refer to a valid submission, show error.
	$eve->output_error_page('common.message.invalid.parameter');
}
else if
(
	// This page is only accessible to system admins and final reviewer; If none of these
	// cases apply, an error is shown.
	!$eve->is_admin($_SESSION['screenname']) &&
	!$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id'])
)
{
	$eve->output_error_page('common.message.no.permission');
}
else 
{
	$message = null;

	if (isset($_POST['action'])) switch ($_POST['action'])
	{
		case 'add_reviewer':
			$message = $eveSubmissionService->submission_definition_reviewer_add($_GET['id'], $_POST['email'], 'reviewer');
			break;
		case 'add_final_reviewer':
			$message = $eveSubmissionService->submission_definition_reviewer_add($_GET['id'], $_POST['email'], 'final_reviewer');
			break;
		case 'delete_reviewer':
			$message = $eveSubmissionService->submission_definition_reviewer_delete($_POST['id']);
			break;
	}
	
	$navigation_array = 
	[
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('submission_definitions') => "submission_definitions.php",
		$submission_definition['description'] => "submission_definition.php?id={$_GET['id']}",
		$eve->_('submission_definition.reviewers') => null
	];
	if (isset($_GET['backlink']) && $_GET['backlink'] == 'submissions_final_reviewer')
	{
		unset($navigation_array[$eve->_('submission_definitions')]);
		$navigation_array[$submission_definition['description']] = "submissions.php?id={$_GET['id']}&access_mode=final_reviewer";
	} 
	$eve->output_html_header();
	$eve->output_navigation($navigation_array);

	// Success/error messages
	if (!is_null($message)) $eve->output_service_message($message);

	?>
	<div class="section">		
	<button type="button" onclick="add_reviewer('reviewer');">Adicionar avaliador</button>
	<button type="button" onclick="add_reviewer('final_reviewer');">Adicionar avaliador final</button>
	</div>

	<script>
	function add_reviewer(type)
	{
		// TODO G11n
		var message = (type == 'final_reviewer') ? 'Digite o e-mail do avaliador final' : 'Digite o e-mail do avaliador';
		var action  = (type == 'final_reviewer') ? 'add_final_reviewer' : 'add_reviewer';
		var url = '<?php echo basename(__FILE__)."?id=".$_GET['id']; if (isset($_GET['backlink']) && $_GET['backlink'] == 'submissions_final_reviewer') echo "&backlink=submissions_final_reviewer";?>';
		var reviewer_email = prompt(message);
		if (reviewer_email != null)
		{
			form = document.createElement('form');
			form.setAttribute('method', 'POST');
			form.setAttribute('action', url);
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
			var1.setAttribute('value', action);
			form.appendChild(var1);
			var2 = document.createElement('input');
			var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'email');
			var2.setAttribute('value', reviewer_email);
			form.appendChild(var2);
			document.body.appendChild(form);
			form.submit();
		}
		return false;
	}

	function delete_reviewer(reviewer_id, reviewer_email)
	{
		var url = '<?php echo basename(__FILE__)."?id=".$_GET['id']; if (isset($_GET['backlink']) && $_GET['backlink'] == 'submissions_final_reviewer') echo "&backlink=submissions_final_reviewer";?>';
		// TODO G11n
		if (confirm("Confirma a exclusão do avaliador " + reviewer_email + "?"))
		{
			form = document.createElement('form');
			form.setAttribute('method', 'POST');
			form.setAttribute('action', url);
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
			var1.setAttribute('value', 'delete_reviewer');
			form.appendChild(var1);
			var2 = document.createElement('input');
			var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'id');
			var2.setAttribute('value', reviewer_id);
			form.appendChild(var2);
			document.body.appendChild(form);
			form.submit();
		}
		return false;
	}
	</script>

	<table class="data_table">
	<tr>
	<th style="width:30%"><?php echo $eve->_("submission_definition_reviewer.email"); ?></th>
	<th style="width:30%"><?php echo $eve->_("submission_definition_reviewer.name"); ?></th>
	<th style="width:30%"><?php echo $eve->_("submission_definition_reviewer.type"); ?></th>
	<th style="width:10%"><?php echo $eve->_("common.table.header.options"); ?></th>		
	</tr>
	<?php

	$submission_definition_reviewers = $eveSubmissionService->submission_definition_reviewers($_GET['id'], 'all');
	foreach ($submission_definition_reviewers as $submission_definition_reviewer)
	{	
		?>
		<tr>
		<td style="text-align:center"><?php echo $submission_definition_reviewer['email']; ?></td>
		<td style="text-align:center"><?php echo $submission_definition_reviewer['name']; ?></td>
		<td style="text-align:center"><?php echo $eve->_("submission_definition_reviewer.type.".$submission_definition_reviewer['type']); ?></td>
		<td style="text-align:center"><button type="button" onclick="delete_reviewer(<?php echo $submission_definition_reviewer['id']; ?>, '<?php echo $submission_definition_reviewer['email']; ?>');"><img src="style/icons/delete.png"/></button></td>
		</tr>
		<?php
	}
	?>
	</table>
	
	<?php
	$eve->output_html_footer();
}
?>