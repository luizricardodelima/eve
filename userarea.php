<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';
require_once 'evepaymentservice.class.php';
require_once 'evesubmissionservice.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();

// Checking if there is a session. If it is not the case, checking username and password
if (!isset($_SESSION['screenname']) && empty($_POST))
{
	$eve->output_html_header();
	$eve->output_navigation([$eve->getSetting('userarea_label') => "userarea.php"]);	

	if (isset($_GET['loginerror'])) $eve->output_error_message('login.error');
	if (isset($_GET['sessionexpired'])) $eve->output_error_message('login.sessionexpired');
	if ($eve->getSetting('show_login_image'))
		echo "<div id=\"login_image\"><img src=\"upload/style/login.png\"/></div>";
	?>
	
	<form method="post" class="dialog_panel_thin">
	<?php
	if($eve->getSetting('system_custom_login_message'))
		echo $eve->getSetting('system_custom_login_message_text');
	else
		echo "<label>{$eve->_('login.intro')}</label>";
	?>
	<label for="login_email_ipt"><?php echo $eve->_('login.email');?></label>
	<input id="login_email_ipt" type="text" name="screenname"/>
	<label for="login_password_ipt"><?php echo $eve->_('login.password');?></label>
	<input id="login_password_ipt" type="password" name="password" autocomplete="off"/>
	<button type="submit" class="submit"><?php echo $eve->_('login.option.login');?></button>
	
	<?php if (!$eve->getSetting('user_signup_closed')) { ?>
	<button type="button" class="altaction" onclick="window.location.href='usersignup.php';"><?php echo $eve->_('login.option.signup');?></button>
	<?php } ?>

	<p>
	<a href="passwordretrieval.php"><?php echo $eve->_('passwordretrieval');?></a> |
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
		$EveUserService = new EveUserService($eve);
		switch ($EveUserService->user_login($screenname, $password))
		{
			case EveUserService::LOGIN_ERROR:
				$eve->output_redirect_page(basename(__FILE__)."?loginerror=1");
				break;
			case EveUserService::LOGIN_SUCCESSFUL:
				$_SESSION['screenname'] = $screenname;
				$eve->output_redirect_page(basename(__FILE__));				
				break;
			case EveUserService::LOGIN_NEW_USER:
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
	$eve->output_navigation([$eve->getSetting('userarea_label') => "userarea.php"]);

	// Displaying messages to user, if any
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	if($eve->getSetting('system_custom_message'))
	{
		echo "<div class=\"section\">{$eve->getSetting('system_custom_message_title')}</div>";
		echo "<div>{$eve->getSetting('system_custom_message_text')}</div>";
	}
	
	echo "<div class=\"section\">{$eve->_('userarea.section.useroptions')} | {$_SESSION['screenname']}</div>";
	echo "<div>";

	// Displaying user form
	$eve->output_big_goto_button("userarea.option.userdata", "&#80;", "user.php");

	// Submissions with requirement == 'none'
	foreach ($eveSubmissionService->submission_definition_list_for_user($_SESSION['screenname'], 'none') as $submission_definition)
		$eve->output_big_goto_button($submission_definition['description'], "&#67;", "submission.php?id={$submission_definition['id']}");

	// Payment
	foreach ($evePaymentService->payment_group_list_for_user() as $payment_group_id)
		if ($payment_group_id === null)
			$eve->output_big_goto_button("userarea.option.payment", "&#91;", "payment.php");
		else 
		{
			$payment_group = $evePaymentService->payment_group_get($payment_group_id);
			$eve->output_big_goto_button("{$eve->_('userarea.option.payment')} - {$payment_group['name']}", "&#91;", "payment.php?group={$payment_group_id}");
		}
	
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
	$eve->output_big_goto_button("user.passwordchange", "&#95;", "passwordchange.php"); // Password change button	
	$eve->output_big_goto_button("userarea.option.logout", "&#87;", "logout.php");  // Logout button
	echo "</div>";

	// Options for final reviewers
	$submissionDefinitionsForFinalReviewer = $eveSubmissionService->submission_definition_list_for_reviewer($_SESSION['screenname'], 'final_reviewer');
	if (!empty($submissionDefinitionsForFinalReviewer))
	{
		echo "<div class=\"section\">{$eve->_('userarea.section.finalrevieweroptions')}</div>";
		echo "<div>";
		foreach ($submissionDefinitionsForFinalReviewer as $submissionDefinitionReviewer)
		{
			$submission_definition = $eveSubmissionService->submission_definition_get($submissionDefinitionReviewer['submission_definition_id']);
			$eve->output_big_goto_button($eve->_('userarea.option.finalreview').$submission_definition['description'], "&#113;", "submissions.php?id={$submission_definition['id']}&access_mode=final_reviewer");
		}		
		echo "</div>";
	}

	// Options for reviewers
	$submissionDefinitionsForFinalReviewer = $eveSubmissionService->submission_definition_list_for_reviewer($_SESSION['screenname'], 'reviewer');
	if (!empty($submissionDefinitionsForFinalReviewer))
	{
		echo "<div class=\"section\">{$eve->_('userarea.section.revieweroptions')}</div>";
		echo "<div>";
		foreach ($submissionDefinitionsForFinalReviewer as $submissionDefinitionReviewer)
		{
			$submission_definition = $eveSubmissionService->submission_definition_get($submissionDefinitionReviewer['submission_definition_id']);
			$eve->output_big_goto_button($eve->_('userarea.option.review').$submission_definition['description'], "&#113;", "submissions.php?id={$submission_definition['id']}&access_mode=reviewer");
		}		
		echo "</div>";
	}

	// Admin options, which will be shown if user has admin privileges
	if ($eve->is_admin($_SESSION['screenname']))
	{
		echo "<div class=\"section\">{$eve->_('userarea.section.adminoptions')}</div>";
		echo "<div>";
		$eve->output_big_goto_button("userarea.option.admin.unverifiedusers", "&#80;", "unverifiedusers.php");
		$eve->output_big_goto_button("userarea.option.admin.users", "&#80;", "users.php");
		$eve->output_big_goto_button('submission_definitions', "&#67;", "submission_definitions.php");
		$eve->output_big_goto_button("userarea.option.admin.payments", "&#91;", "payments.php");
		$eve->output_big_goto_button("payment_options", "&#91;", "payment_options.php");
		$eve->output_big_goto_button("payment_groups", "&#91;", "payment_groups.php");
		$eve->output_big_goto_button("userarea.option.admin.certifications", "&#34;", "certifications.php");
		$eve->output_big_goto_button("userarea.option.admin.certification_models", "&#34;", "certification_models.php");
		$eve->output_big_goto_button("userarea.option.admin.pages", "&#108;", "pages.php");
		$eve->output_big_goto_button("userarea.option.admin.settings", "&#106;", "settings.php");
		echo "</div>";
	}
	$eve->output_html_footer();
}
?>