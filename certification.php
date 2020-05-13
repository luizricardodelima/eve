<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.php';
require_once 'evesubmissionservice.class.php';
require_once 'eveuserservice.class.php';
require_once 'lib/fpdf/fpdf.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);
$eveSubmissionService = new EveSubmissionService($eve);
$eveUserService = new EveUserServices($eve);

// TODO View more than one certification
// Session verification.
if (!isset($_SESSION['screenname']))
{
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if
(
	!isset($_GET['id']) &&
	!isset($_GET['templateid'])
)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else if 
(
	(isset($_GET['id']) && !is_numeric($_GET['id'])) ||
	(isset($_GET['templateid']) && !is_numeric($_GET['templateid']))
) 
{
	$eve->output_error_page('common.message.invalid.parameter'); // Blocking sql injections by accepting numbers only
}
else if
(
	(isset($_GET['id']) && !$eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certification` WHERE `id` = {$_GET['id']};")->num_rows) ||
	(isset($_GET['templateid']) && !$eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certificationdef` WHERE `id` = {$_GET['templateid']};")->num_rows)
)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else
{
	// At this point it's guaranteed that:
	// - There is a valid session
	// - A numeric ID, which refers to an existing certification or an existing certificationdef, was passed as a get variable
	
	// Checking user access priviledges to this certification.
	// The user can access this certification if he/she is an admin or if he/she's the owner.
	$admin_access = $eve->is_admin($_SESSION['screenname']);
	if (isset($_GET['id']))
		$owner_access = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certification` WHERE `id` = {$_GET['id']} AND `screenname`='{$_SESSION['screenname']}';")->num_rows;
	else
		$owner_access = $admin_access;

	if (!$admin_access && !$owner_access) 
		$eve->output_error_page("Você não pode acessar esta página");
	else
	{
		// Displaying certification		
		// Loading user data and submission data
		$certification = null;
		$certificationdef = null;
		$user = null;
		$submission = null;
		if (isset($_GET['id']))
		{
			// TODO use method from service
			$certification = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certification` WHERE `id`={$_GET['id']};")->fetch_assoc();
			//$certificationdef = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certificationdef` WHERE `id`={$certification['certificationdef_id']};")->fetch_assoc();
			$certificationdef = $eveCertificationService->certificationmodel_get($certification['certificationdef_id']);			
			//$user = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}userdata` WHERE `email`='{$certification['screenname']}';")->fetch_assoc();
			$user = $eveUserService->get_user($certification['screenname']);			
			$submission = $eveSubmissionService->submission_get($certification['submissionid']);
		}
		else if (isset($_GET['templateid']))
		{
			$certificationdef = $eveCertificationService->certificationmodel_get($_GET['templateid']);
			//$certificationdef = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certificationdef` WHERE `id`={$_GET['templateid']};")->fetch_assoc();
		}
		
		// Replacing the certificate text variables
		if (isset($_GET['id']))
			$certificate_text = $eveCertificationService->certification_text_output(json_decode($certificationdef['text']), $user, $submission);		
		else if (isset($_GET['templateid']))
			$certificate_text = $eveCertificationService->certification_text_output(json_decode($certificationdef['text']), $user, $submission);

		// Only Reporting Errors. If less critical messages (such as warnings) are displayed, the pdf cannot be sent.
		error_reporting(E_ERROR);
		
		// Creating PDF
		$pdf = new FPDF($certificationdef['pageorientation'], 'mm', $certificationdef['pagesize']);
		$pdf->SetTopMargin($certificationdef['topmargin']); // Top Margin has to be called before the page is created
		$pdf->AddPage();

		// Background Image
		$bg_image_filename = "upload/certification/{$certificationdef['backgroundimage']}";
		$bg_image_file = fopen($bg_image_filename, "r");
		if ($bg_image_file && is_file($bg_image_filename))
			$pdf->Image("upload/certification/{$certificationdef['backgroundimage']}",0,0, $pdf->w, $pdf->h);
		
		// Using FPDF's encoding...
		$certificate_text = iconv('UTF-8', 'windows-1252', $certificate_text);
		
		// Setting margins and writing text
		$pdf->SetLeftMargin($certificationdef['leftmargin']);
		$pdf->SetRightMargin($certificationdef['rightmargin']);
		$pdf->SetFont('Arial','',$certificationdef['text_fontsize']);
		$pdf->MultiCell
		(
			0,	// until right margin
			$certificationdef['text_lineheight'],
			$certificate_text,
			0,	// no border
			'C'	// center alignment
 		);
	
		// Before output, increasing view count if it's owner acess
		if ($owner_access && isset($_GET['id'])) // TODO SERVICE
			$eve->mysqli->query("update `{$eve->DBPref}certification` set `views` = `views` + 1 where `id`={$_GET['id']};");
		
		// Displaying PDF
		$pdf->Output();
	}
}
?>
