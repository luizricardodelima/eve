<?php
session_start();
require_once '../eve.class.php';
require_once '../evecertificationservice.class.php';
require_once '../evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
$eveCertificationService = new EveCertificationService($eve);
$certification_model = $eveCertificationService->certificationmodel_get($_GET['certificationmodel_id']);
$submission_definition = $eveSubmissionService->submission_definition_get($_GET['submission_definition_id']);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	header("Content-Type: text/plain");	
	echo "Error: Invalid session";
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	header("Content-Type: text/plain");
	echo "Error: User does not have administrative permissions";
}
// Verifying if id is valid
else if ($certification_model === null || $submission_definition === null)
{
	header("Content-Type: text/plain");
	echo "Error: Invalid parameters";
} 
// All the verifications were successful
else
{
	header("Content-Type: application/json; charset=utf-8");
	$return = $eveCertificationService->certificationmodel_submission_certification_list($_GET['certificationmodel_id'], $_GET['submission_definition_id']);
	echo json_encode($return);
}
?>
