<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';
require_once 'evesubmissionservice.class.php';
require_once 'eveuserservice.class.php';
require_once 'lib/fpdf/fpdf.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);
$eveSubmissionService = new EveSubmissionService($eve);
$eveUserService = new EveUserService($eve);

$certification = isset($_GET['id']) ? $eveCertificationService->certification_get($_GET['id']) : null;
$certification_model = isset($_GET['model_id']) ? $eveCertificationService->certificationmodel_get($_GET['model_id']): null;

// Session verification.
if (!isset($_SESSION['screenname']))
{
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Verifying if a valid certification or certification model was passed as parameter
else if ($certification === null && $certification_model === null)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// Verifying permissions. To access this page, user needs to be admin (to view any model
// or any certification) or he needs to be the owner of the certification. Only admins can
// view models.
else if
(
	!$eve->is_admin($_SESSION['screenname']) && 
	($certification_model !== null || $certification['screenname'] != $_SESSION['screenname'])
)
{
	$eve->output_error_page('common.message.no.permission');
}
// At this point it's assumed that either a non null $certification or a $certitication_model 
// exists and the current user is allowed to view it.
else
{
	$user = null;
	$submission = null;
	if ($certification !== null)
	{
		$certification_model = $eveCertificationService->certificationmodel_get($certification['certification_model_id']);			
		$user = $eveUserService->user_get($certification['screenname']);			
		$submission = $eveSubmissionService->submission_get($certification['submissionid']);
	}
	
	// Generating certification text
	$certification_text = $eveCertificationService->certification_text(json_decode($certification_model['text']), $user, $submission);

	// Only Reporting Errors. If less critical messages (such as warnings) are displayed, the pdf cannot be output.
	error_reporting(E_ERROR);
		
	// Creating PDF
	$pdf = new FPDF($certification_model['pageorientation'], 'mm', $certification_model['pagesize']);
	$pdf->SetTopMargin($certification_model['topmargin']); // Top Margin has to be called before the page is created
	$pdf->AddPage();

	// Background Image
	$bg_image_filename = "upload/certification/{$certification_model['backgroundimage']}";
	$bg_image_file = fopen($bg_image_filename, "r");
	if ($bg_image_file && is_file($bg_image_filename))
		$pdf->Image("upload/certification/{$certification_model['backgroundimage']}",0,0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
	
	// FPDF uses windows-1252 encoding. Using //IGNORE to ignore untranslatable chars
	$certification_text = iconv('UTF-8', 'windows-1252//IGNORE', $certification_text);
		
	// Setting text alignment
	$text_alignent = null;
	switch($certification_model['text_alignment'])
	{
		case 'left': $text_alignent = 'L'; break;
		case 'right': $text_alignent = 'R'; break;
		case 'center': $text_alignent = 'C'; break;
		case 'justified': $text_alignent = 'J'; break;
		default: $text_alignent = 'L'; break;
	}

	// Setting text font
	$text_font = $certification_model['text_font'];
	if (!in_array($text_font, $eveCertificationService->certificationmodel_textfonts()))
		$text_font = $eveCertificationService->certificationmodel_textfont_default();

	// Setting margins and writing text
	$pdf->SetLeftMargin($certification_model['leftmargin']);
	$pdf->SetRightMargin($certification_model['rightmargin']);
	$pdf->SetFont($text_font,'',$certification_model['text_fontsize']);
	$pdf->MultiCell
	(
		0,	// until right margin
		$certification_model['text_lineheight'],
		$certification_text,
		0,	// no border
		$text_alignent
	);
	
	// Increasing view count if the certification is being viewed by its owner
	if ($certification !== null && ($certification['screenname'] == $_SESSION['screenname']))
		$eveCertificationService->certification_increase_view_count($certification['id']);
	
	// Displaying PDF
	$pdf->Output();
}
?>