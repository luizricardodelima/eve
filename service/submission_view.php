<?php
session_start();
require_once '../eve.class.php';
require_once '../evesubmissionservice.class.php';
require_once '../lib/dynamicform/dynamicform.class.php';
require_once '../lib/dynamicform/dynamicformhelper.class.php';
//require_once '../evecustominputservice.class.php';

// This service returns the contents of a submission for a user if
// - they are its owner
// - they are an reviewer for it (it wont show the protected fields)
// - they are the system administrator

$eve = new Eve("../");
$eveSubmissionService = new EveSubmissionService($eve);
//$eveCustomInputService = new EveCustomInputService($eve);

// Verifying session
if (!isset($_SESSION['screenname']))
{	
	header("Content-Type: text/plain");	
	echo "Error: Invalid session";
}
// Verifying if id was passed
else if (!isset($_GET['id']))
{
	header("Content-Type: text/plain");
	echo "Error: Invalid parameter";
	exit();
}
// Verifying if id is valid
else if (!$eveSubmissionService->submission_get($_GET['id']))
{
	header("Content-Type: text/plain");
	echo "Error: Invalid parameter";
} 
// Verifying user can access the submission
// User needs to be admin, a final reviewer for the submission, a reviewer for the submission
// or the owner of the submission
else if (
	$eve->is_admin($_SESSION['screenname']) ||
	$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) ||
	$eveSubmissionService->is_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) || 
	$submission['email'] == $_SESSION['screenname']
	)
{
	$submission = $eveSubmissionService->submission_get($_GET['id']);

	$only_unrestrict_view = false;
	if (
		!$eve->is_admin($_SESSION['screenname']) && /* User is not admin */
		!$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) && /* User is not final reviewer */
		$eveSubmissionService->is_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) && /* User is reviewer */
		$submission['email'] != $_SESSION['screenname']  /* User is not owner */
	)
	{
		$only_unrestrict_view = true;
	}

	DynamicFormHelper::$locale = $eve->getSetting('system_locale');
	$dynamicFormSubmission = new DynamicForm($submission['structure'], json_decode($submission['content']));
	$dynamicFormRevision = new DynamicForm($submission['revision_structure'], json_decode($submission['revision_content']));

	$submission['structure'] = json_decode($submission['structure']);
	$submission['content'] = json_decode($submission['content']);
	$submission['revision_structure'] = json_decode($submission['revision_structure']);
	$submission['revision_content'] = json_decode($submission['revision_content']);

	// Removing all the not-unrestrict items from structure, if $only_unrestrict_view == true
	if ($only_unrestrict_view)
		foreach($submission['structure'] as $i => $submission_structure_item)
			if ($submission_structure_item->unrestrict == false)
			{
				unset($submission['structure'][$i]);
				unset($submisson['content'][$i]);
			}

	$submission['formatted_content'] = $dynamicFormSubmission->getHtmlFormattedContent($only_unrestrict_view, 'data_table');
	$submission['formatted_revision'] = $dynamicFormRevision->getHtmlFormattedContent(false, 'data_table');

	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($submission);
}
else // User does not have access to the submission
{
	header("Content-Type: text/plain");
	echo "Error: Access denied";
}
?>