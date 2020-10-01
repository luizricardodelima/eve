<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$EveUserService = new EveUserService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else
{
	$message = null;
	if (!empty($_POST)) 
	{
		$message = $EveUserService->user_change_password($_SESSION['screenname'], $_POST['oldpassword'], $_POST['newpassword'], $_POST['newpasswordrepeat']);
	}
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('user.passwordchange') => null
	]);

	?>
	<div class="section"><?php echo $eve->_('user.passwordchange');?></div>
	<?php

	if ($message !== EveUserService::USER_PASSWORDCHANGE_SUCCESS)
	{
		// Showed when password is not changed due to some error or it's a newly loaded page
		if ($message !== null) $eve->output_service_message($message);
		?>
		<form method="post" id="passwordchange_form" class="dialog_panel">
		<p><?php echo $eve->_('user.passwordchange.caption');?><?php echo $_SESSION['screenname'];?></p>
		<label for="oldpassword"><?php echo $eve->_('user.passwordchange.oldpassword');?></label>
		<input 	id="oldpassword" type="password" name="oldpassword" maxlength="255" />
		<label for="newpassword"><?php echo $eve->_('user.passwordchange.newpassword');?></label>
		<input 	id="newpassword" type="password" name="newpassword" maxlength="255" />
		<label for="newpasswordrepeat"><?php echo $eve->_('user.passwordchange.newpasswordrepeat');?></label>
		<input 	id="newpasswordrepeat" type="password" name="newpasswordrepeat" maxlength="255" />
		<button type="submit" class="submit"><?php echo $eve->_('user.passwordchange.action.change');?></button>
		</form>
		<?php
	}
	else // ($message == EveUserService::USER_PASSWORDCHANGE_SUCCESS)
	{
		// Showed when password is successfully changed
		?>
		<div class="dialog_panel">
		<p><?php echo $eve->_($message);?></p>
		<button type="button" class="submit" onclick="window.location.href='userarea.php'">
		<?php echo $eve->_('common.action.back');?>
		</button>
		</div>
		<?php
	}
	$eve->output_html_footer();
}?>
