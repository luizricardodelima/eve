<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);
$certification = $eveCertificationService->certification_get($_GET['id']);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Checking whether the id passed is valid. This page can also open deactivated certifications
else if ($certification === null)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// Checking if the current user has acces to this page.
else if (!$eve->is_admin($_SESSION['screenname']) && ($certification['screenname'] != $_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
// If there is a valid session, a valid id passed, and the user has rights to access this
// page, display its contents
else
{
	$certification_model = $eveCertificationService->certificationmodel_get($certification['id']);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php",  $certification_model['name'], null);
	
	?>
	<div class="dialog_panel">
	<?php echo $certification_model['openermsg']; ?>
	<button type="button" class="submit" onclick="window.location.href='certification.php?id=<?php echo $_GET['id'];?>'">
	<?php echo $eve->_('certification.action.view');?>
	</button>
	</div>
	<?php

	$eve->output_html_footer();	
}
?>
