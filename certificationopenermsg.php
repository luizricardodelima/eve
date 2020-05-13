<?php
session_start();
require_once 'eve.class.php';

$eve = new Eve();

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if (!isset($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// Blocking sql injections by accepting numbers only for id
else if (!is_numeric($_GET['id'])) 
{
	$eve->output_error_page('common.message.invalid.parameter'); 
}
// Checking whether the id passed is valid. This code will also open deactivated certifications
else if (!$eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certification` WHERE `id` = {$_GET['id']};")->num_rows)
{
	$eve->output_error_page('common.message.invalid.parameter');
}

else
{
	// At this point it's guaranteed that:
	// - There is a valid session
	// - A valid, numeric ID was passed as a get variable
	// - This ID refers to a valid certificate

	// Loading certification
	$certification = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certification` WHERE `id`={$_GET['id']};")->fetch_assoc();
	// The user who is acessing this certification
	$current_user = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}userdata` WHERE `email`='{$_SESSION['screenname']}'")->fetch_assoc();
	

	// If current user is not the admin nor the owner...
	if (!$current_user['admin'] && ($certification['screenname'] != $current_user['email'])) 
		$eve->output_error_page("Você não pode acessar este certificado.");
	else
	{
		// Loading certification template
		$certificationdef = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certificationdef` WHERE `id`={$certification['certificationdef_id']};")->fetch_assoc();

		// Displaying opener message for certification
		$eve->output_html_header();
		$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php",  $certificationdef['name'], null);
		echo $certificationdef['openermsg'];
		$eve->output_medium_goto_button("certification", "Acessar certificado", "certification.php?id={$_GET['id']}");
		$eve->output_html_footer();	
	}
}
?>
