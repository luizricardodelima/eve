<?php
session_start();
require_once '../eve.class.php';
require_once '../evesubmissionservice.class.php';
require_once '../evecustominputservice.class.php';

// This service returns the contents of a submission for a user if
// - they are its owner
// - they are an reviewer for it (it wont show the protected fields)
// - they are the system administrator

$eve = new Eve("../");
$eveSubmissionService = new EveSubmissionService($eve);
$eveCustomInputService = new EveCustomInputService($eve);
$submission = $eveSubmissionService->submission_get($_GET['id']);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	header("Content-Type: text/plain");	
	echo "Error: Invalid session";
}
// Verifying if id is valid
else if ($submission == null)
{
	header("Content-Type: text/plain");
	echo "Error: Invalid parameter";
} 
else if (
	$eve->is_admin($_SESSION['screenname']) || /* User is admin */
	$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) || /* User is final reviewer */
	$eveSubmissionService->is_reviewer($_SESSION['screenname'], $submission['submission_definition_id']) || /* User is reviewer */
	$submission['email'] == $_SESSION['screenname']  /* User is owner */
	)
{
	$submission['structure'] = json_decode($submission['structure']);
	$submission['content'] = json_decode($submission['content']);

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

	$submission['revision_structure'] = json_decode($submission['revision_structure']);
	$submission['revision_content'] = json_decode($submission['revision_content']);

	$submission['formatted_content'] = $eveCustomInputService->custom_input_format_content($submission['structure'], $submission['content'], $only_unrestrict_view);
	$submission['formatted_revision'] = $eveCustomInputService->custom_input_format_content($submission['revision_structure'],$submission['revision_content']);

	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($submission);
}
// All the verifications were successful
else
{
	header("Content-Type: text/plain");
	echo "Error: Access denied";
}
?>

