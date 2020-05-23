<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.php';
require_once 'evepaymentservice.php';
require_once 'evesubmissionservice.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();

// Checking if there is a session. If it is not the case, checking username and password
if (!isset($_SESSION['screenname']) && empty($_POST))
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php");	

	if (isset($_GET['loginerror'])) $eve->output_error_message('login.error');
	if (isset($_GET['sessionexpired'])) $eve->output_error_message('login.sessionexpired');
	?>
	
	<form method="post" class="user_dialog_panel">
	<?php
	if($eve->getSetting('system_custom_login_message'))
		echo $eve->getSetting('system_custom_login_message_text');
	else
		echo "<p>{$eve->_('login.intro')}</p>";
	?>
	<label for="login_email_ipt"><?php echo $eve->_('login.email');?></label>
	<input id="login_email_ipt" type="text" name="screenname"/>
	<label for="login_password_ipt"><?php echo $eve->_('login.password');?></label>
	<input id="login_password_ipt" type="password" name="password" autocomplete="off"/>
	<button type="submit" class="submit"><?php echo $eve->_('login.option.login');?></button>
	
	<?php if (!$eve->getSetting('user_signup_closed')) { ?>
	<button type="button" onclick="window.location.href='usersignup.php';"><?php echo $eve->_('login.option.signup');?></button>
	<?php } ?>

	<p>
	<a href="passwordretrieval.php"><?php echo $eve->_('login.option.retrievepassword');?></a> |
	<a href="mailto:<?php echo $eve->getSetting('support_email_address'); ?>"><?php echo $eve->_('common.action.support');?></a> 
	</p>	
	
	<?php
	if ($eve->getSetting('user_signup_closed'))
	{
		// Not accepting new users
		echo $eve->getSetting('user_signup_closed_message');
	}
	?>
	</form>
	<?php
	
	$eve->output_html_footer();
}
else if (!isset($_SESSION['screenname']) && !empty($_POST))
{
	$screenname = $_POST['screenname'];
	$password = $_POST['password'];

	if ($screenname === NULL || $password === NULL)
	{	// No session and no POST data: In this case, we assume we have an expired session.
		$eve->output_redirect_page("subsdcription.php?sessionexpired=1");
	}
	else
	{	// No session with POST data: In this case, we assume that user is trying to login.
		$eveUserServices = new EveUserServices($eve);
		switch ($eveUserServices->user_login($screenname, $password))
		{
			case EveUserServices::LOGIN_ERROR:
				$eve->output_redirect_page(basename(__FILE__)."?loginerror=1");
				break;
			case EveUserServices::LOGIN_SUCCESSFUL:
				$_SESSION['screenname'] = $screenname;
				$eve->output_redirect_page(basename(__FILE__));				
				break;
			case EveUserServices::LOGIN_NEW_USER:
				$eve->output_redirect_page("verificationcode.php?screenname=$screenname");
				break;
		}		

	}
}
else
{
	$eveSubmissionService = new EveSubmissionService($eve);
	$evePaymentService = new EvePaymentService($eve);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php");

	if (isset($_GET['systemmessage'])) switch ($_GET['systemmessage'])
	{
		case 'userdata.saved.successfully':
			$eve->output_success_message('userarea.message.userdata.saved.successfully'); 
			break;
		case 'submission.sent':
			$eve->output_success_message('userarea.message.submission.sent');
			break;
	}

	// TODO: Change to the pattern above
	if (isset($_GET['emailverificationsuccess'])) $eve->output_success_message('userarea.message.email.successfully.verified');	

	if($eve->getSetting('system_custom_message'))
	{
		echo "<div class=\"section\">{$eve->getSetting('system_custom_message_title')}</div>";
		echo "<div class=\"default_content_panel\">{$eve->getSetting('system_custom_message_text')}</div>";
	}
	
	echo "<div class=\"section\">{$eve->_('userarea.section.useroptions')} | {$_SESSION['screenname']}</div>";
	echo "<div class=\"default_content_panel\">";

	// Displaying user form
	$eve->output_big_goto_button("userarea.option.userdata", "&#80;", "user.php");

	// Submissions without requirement
	foreach ($eveSubmissionService->submission_definition_list_for_user($_SESSION['screenname'], 'none') as $submission_definition)
		$eve->output_big_goto_button($submission_definition['description'], "&#67;", "submission.php?id={$submission_definition['id']}");

	// Payment
	if (!$eve->getSetting('payment_closed'))
		$eve->output_big_goto_button("userarea.option.payment", "&#91;", "payment.php");

	// Submissions with payment requirement
	if (!$eve->getSetting('payment_closed') && $evePaymentService->payment_get_by_user($_SESSION['screenname']))
	foreach ($eveSubmissionService->submission_definition_list_for_user($_SESSION['screenname'], 'after_payment') as $submission_definition)
		$eve->output_big_goto_button($submission_definition['description'], "&#67;", "submission.php?id={$submission_definition['id']}");

	// Certifications
	$eveCertificationService = new EveCertificationService($eve);
	$certifications = $eveCertificationService->get_certifications_for_user($_SESSION['screenname']);
	foreach ($certifications as $certification)
	{
		if ($certification['hasopenermsg'])
			$eve->output_big_goto_button($certification['name'], "&#34;", "certificationopenermsg.php?id={$certification['id']}");
		else
			$eve->output_big_goto_button($certification['name'], "&#34;", "certification.php?id={$certification['id']}");
	}	
	
	// Basic user options
	$eve->output_big_goto_button("userarea.option.passwordchange", "&#95;", "passwordchange.php"); // Password change button	
	$eve->output_big_goto_button("userarea.option.logout", "&#87;", "logout.php");  // Logout button
	echo "</div>";

	// Options for final reviewers
	$submissionDefinitionsForFinalReviewer = $eveSubmissionService->submission_definition_list_for_reviewer($_SESSION['screenname'], 'final_reviewer');
	if (!empty($submissionDefinitionsForFinalReviewer))
	{
		echo "<div class=\"section\">{$eve->_('userarea.section.finalrevieweroptions')}</div>";
		echo "<div class=\"default_content_panel\">";
		foreach ($submissionDefinitionsForFinalReviewer as $submissionDefinitionReviewer)
		{
			$submission_definition = $eveSubmissionService->submission_definition_get($submissionDefinitionReviewer['submission_definition_id']);
			$eve->output_big_goto_button("Avaliação final: ".$submission_definition['description'], "&#113;", "submissions.php?id={$submission_definition['id']}&access_mode=final_reviewer");
		}		
		echo "</div>";
	}

	// Options for reviewers
	$submissionDefinitionsForFinalReviewer = $eveSubmissionService->submission_definition_list_for_reviewer($_SESSION['screenname'], 'reviewer');
	if (!empty($submissionDefinitionsForFinalReviewer))
	{
		echo "<div class=\"section\">{$eve->_('userarea.section.revieweroptions')}</div>";
		echo "<div class=\"default_content_panel\">";
		foreach ($submissionDefinitionsForFinalReviewer as $submissionDefinitionReviewer)
		{
			$submission_definition = $eveSubmissionService->submission_definition_get($submissionDefinitionReviewer['submission_definition_id']);
			$eve->output_big_goto_button("Avaliação: ".$submission_definition['description'], "&#113;", "submissions.php?id={$submission_definition['id']}&access_mode=reviewer");
		}		
		echo "</div>";
	}

	// Admin options, which will be shown if user has admin privileges
	if ($eve->is_admin($_SESSION['screenname']))
	{
		echo "<div class=\"section\">{$eve->_('userarea.section.adminoptions')}</div>";
		echo "<div class=\"default_content_panel\">";
		$eve->output_big_goto_button("userarea.option.admin.unverifiedusers", "&#80;", "unverifiedusers.php");
		$eve->output_big_goto_button("userarea.option.admin.users", "&#80;", "users.php");
		$eve->output_big_goto_button("userarea.option.admin.usercategories", "&#80;", "usercategories.php");
		$eve->output_big_goto_button('submission_definitions', "&#67;", "submission_definitions.php");
		$eve->output_big_goto_button("userarea.option.admin.payments", "&#91;", "payments.php");
		$eve->output_big_goto_button("userarea.option.admin.paymenttypes", "&#91;", "paymenttypes.php");
		$eve->output_big_goto_button("userarea.option.admin.certifications", "&#34;", "certifications.php");
		$eve->output_big_goto_button("userarea.option.admin.certificationtemplates", "&#34;", "certificationdefs.php");
		$eve->output_big_goto_button("userarea.option.admin.pages", "&#108;", "pages.php");
		$eve->output_big_goto_button("userarea.option.admin.settings", "&#106;", "settings.php");
		echo "</div>";
	}
	$eve->output_html_footer();
}
?>

