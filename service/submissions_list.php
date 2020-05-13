<?php
session_start();
require_once '../eve.class.php';
require_once '../evesubmissionservice.class.php';

$eve = new Eve("../");
$eveSubmissionService = new EveSubmissionService($eve);
$submission = $eveSubmissionService->submission_definition_get($_GET['id']);

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
else if ($submission == null)
{
	header("Content-Type: text/plain");
	echo "Error: Invalid parameter";
} 
// All the verifications were successful
else
{
	header("Content-Type: application/json; charset=utf-8");
	$return = $eveSubmissionService->submission_list($_GET['id']);
	echo json_encode($return);
}
?>
