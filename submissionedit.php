<?php
session_start();

require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
$submission = (isset($_GET['id'])) ? $eveSubmissionService->submission_get($_GET['id']) : null;

if (!isset($_SESSION['screenname']))
{	
	// If there is no session, redirect to front page
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if ($submission == null)
{
	// If $_GET['id'] is not passed or does not refer to a valid submission, show error.
	$eve->output_error_page('common.message.invalid.parameter');
}
else if
(
	// This page is meant to be accessed by system admins, the final reviewer of the
	// submission definition or the reviewer of the current submission. If none of these
	// cases apply, the user is not alowed to access this page and an error is shown.
	!$eve->is_admin($_SESSION['screenname']) &&
	!$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) &&
	$submission['reviewer_email'] != $_SESSION['screenname']
)
{
	$eve->output_error_page('common.message.no.permission');
}
else 
{	
	// All security checks are okay, loading the page.
	DynamicFormHelper::$locale = $eve->getSetting('system_locale');
	$submission = $eveSubmissionService->submission_get($_GET['id']);
	$structure = $submission['structure'];
	$content = (isset($_POST) && isset($_POST['content'])) ? $_POST['content'] : json_decode($submission['content']);
	$files = (isset($_FILES) && isset($_FILES['content'])) ? $_FILES['content'] : null;
	$dynamicForm = new DynamicForm($structure, $content, $files, 'upload/submission/');
	$validation_errors = null; // If form is not sent because of validation errors, this page will display them
	$message = null;

	// Performing actions, if there are post data
	if (isset($_POST['action'])) switch ($_POST['action'])
	{
		case 'update':
			$validation_errors = $dynamicForm->validate();
			if(empty($validation_errors)) // validation returns no errors
				$message = $eveSubmissionService->submission_update($_GET['id'], json_encode($content));
			break;
	}

	$submissiondefinition = $eveSubmissionService->submission_definition_get($submission['submission_definition_id']);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submissiondefinition['description'], "submissions.php?id={$submissiondefinition['id']}", "ID {$submission['id']}", null);

	// Success/error messages
	if (!is_null($message)) $eve->output_service_message($_GET['message']);
	// Validation error messages
	if (!empty($validation_errors))	$eve->output_error_list_message($validation_errors);

	?>
	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" enctype="multipart/form-data" class="dialog_panel_wide">
	<input type="hidden" name="action" value="update"/>
	<?php echo $dynamicForm->outputControls('structure', 'content'); ?>
	<button type="submit" class="submit">Salvar</button>
	</form>
	
	<?php
	$eve->output_html_footer();
}
?>