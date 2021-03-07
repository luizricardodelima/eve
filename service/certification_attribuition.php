<?php
session_start();
require_once '../eve.class.php';
require_once '../evecertificationservice.class.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);

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
// All the verifications were successful
else
{
	header("Content-Type: application/json; charset=utf-8");
	$submission_id = (is_numeric($_GET['submission_id'])) ? intval($_GET['submission_id']) : null;
	$msg = $eveCertificationService->certification_model_attribuition($_GET['certificationmodel_id'], $_GET['screenname'], $submission_id);
	echo $msg;
}
?>
