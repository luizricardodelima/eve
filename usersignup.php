<?php
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eve->output_html_header();
$eve->output_navigation
([
	$eve->getSetting('userarea_label') => "userarea.php",
	$eve->_('signup.title') => null
]);

// If accepting new sign ups, the user signup page will be shown. Otherwise, the user shouldn't be able 
// to access this page, since the link that leads to this page should be disabled. Anyway, the verification
// is performed here for security reasons. 
if ($eve->getSetting('user_signup_closed'))
{
	echo "<div class=\"dialog_panel\">";
	echo $eve->getSetting('user_signup_closed_message');
	echo "<p><a href=\"userarea.php\">{$eve->_('common.action.back')}</a></p>";
	echo "</div>";
}
else
{
	$msg = null;
	if (!empty($_POST))
	{
		$EveUserService = new EveUserService($eve);
		$msg = $EveUserService->unverified_user_create($_POST['screenname'], $_POST['password'], $_POST['passwordrepeat']);
	}
	if ($msg == EveUserService::UNVERIFIED_USER_CREATE_SUCCESS)
	{
		?>
		<div class="dialog_panel">
		<span>
		<p><?php echo $eve->_('signup.success.1');?></p>
		<p><?php echo $eve->_('signup.success.2');?></p>
		<p><?php echo $eve->_('signup.success.3');?></p>
		<p><a href="userarea.php"><?php echo $eve->_('common.action.back');?></a></p>
		</span>
		</div>
		<?php
	}
	else // there is no message or there were errors on creating an unverified user
	{
		if (isset($msg)) $eve->output_service_message($msg);

		?> 
		<form method="post" autocomplete="off" class="dialog_panel">
		<p><?php echo $eve->_('signup.intro');?></p>
		<label for="signup_email_ipt"><?php echo $eve->_('signup.email');?></label>
		<input id="signup_email_ipt" type="text" name="screenname" value="<?php if (isset($_POST['screenname'])){ echo $_POST['screenname'];}?>"/>
		<label for="signup_password_ipt"><?php echo $eve->_('signup.password');?></label>
		<input id="signup_password_ipt" type="password" name="password" autocomplete="off"/>
		<label for="signup_passwordrepeat_ipt"><?php echo $eve->_('signup.passwordrepeat');?></label>
		<input id="signup_passwordrepeat_ipt" type="password" name="passwordrepeat" autocomplete="off"/>
		<button class="submit" type="submit" id="submit_button" onclick="deactivate_button(this)"><?php echo $eve->_('signup.submit');?></button>
		<p><a href="userarea.php"><?php echo $eve->_('common.action.back');?></a></p>
		</form>
		
		<script>
		function deactivate_button(e)
		{
			var el = document.createElement("p");
			el.style.textAlign = 'center';
			el.innerHTML = '<img src="style/icons/loading.gif" style="height: 2rem; width: 2rem;"/>';
			e.parentNode.insertBefore(el, e);
			e.style.display = 'none';
		}
		</script>
		<?php	
	}
}

$eve->output_html_footer();
?>
