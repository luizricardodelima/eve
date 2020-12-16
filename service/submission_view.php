<?php
session_start();
require_once '../eve.class.php';
require_once '../evesubmissionservice.class.php';
require_once '../lib/dynamicform/dynamicform.class.php';
require_once '../lib/dynamicform/dynamicformhelper.class.php';

// This service returns the contents of a submission for a user if
// - they are its owner
// - they are an reviewer for it (it wont show the protected fields)
// - they are the system administrator

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
$submission = (isset($_GET['id'])) ? $eveSubmissionService->submission_get($_GET['id']) : null;

if (!isset($_SESSION['screenname']))
{
	// If there's no session, return error	
	header("Content-Type: text/plain");	
	echo "Error: Invalid session";
}
else if (is_null($submission))
{
	// If there is no id passed as parameter or it is invalid, return error
	header("Content-Type: text/plain");
	echo "Error: Invalid parameter";
	exit();
}
else if (
	$eve->is_admin($_SESSION['screenname']) ||
	$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) ||
	$eveSubmissionService->is_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) || 
	$submission['email'] == $_SESSION['screenname']
	)
{
	// If user is admin, final reviewer, reviewer or the owner of the submission
	// return submission's contents

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

	// Removing all items from structure that are not supposed to be vieweb by reviewer
	// they are marked with the custom attribute 'noreview'
	if ($only_unrestrict_view)
	{
		foreach($submission['structure'] as $i => $submission_structure_item)
			if ($submission_structure_item->customattribute == 'noreview')
			{
				unset($submission['structure'][$i]);
				unset($submission['content'][$i]);
			}
		foreach ($dynamicFormSubmission->structure as $i => $dynamicFormSubmissionItem)
			if ($dynamicFormSubmissionItem->customattribute == 'noreview')
			{
				unset($dynamicFormSubmission->structure[$i]);
			}
	}
	$submission['formatted_content'] = $dynamicFormSubmission->getHtmlFormattedContent('data_table');
	$submission['formatted_revision'] = $dynamicFormRevision->getHtmlFormattedContent('data_table');

	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($submission);
}
else // User does not have access to the submission
{
	header("Content-Type: text/plain");
	echo "Error: Access denied";
}
?>