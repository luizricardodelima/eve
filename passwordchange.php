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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Alterar senha", null);

	?>
	<div class="section">Alterar senha</div>
	<?php

	if ($message !== null) switch ($message)
	{
		case EveUserService::USER_CHANGE_PASSWORD_ERROR:
			$eve->output_error_message("Erro inesperado.");
			break;
		case EveUserService::USER_CHANGE_PASSWORD_ERROR_INCORRECT_PASSWORD:
			$eve->output_error_message("Senha atual incorreta.");
			break;
		case EveUserService::USER_CHANGE_PASSWORD_ERROR_PASSWORDS_DO_NOT_MATCH:
			$eve->output_error_message("As senhas novas não conferem.");
			break;
		case EveUserService::USER_CHANGE_PASSWORD_ERROR_PASSWORD_TOO_SMALL:
			$eve->output_error_message("A nova senha é muito pequena.");
			break;	
	}
	
	if ($message !== EveUserService::USER_CHANGE_PASSWORD_SUCCESS)
	{
		// It shows when password is not changed due to some error or it's a newly loaded page (with no postdata)
		?>
		<form method="post" id="passwordchange_form" class="dialog_panel">
		<p>Alterando a senha para <?php echo $_SESSION['screenname'];?></p>
		<label for="oldpassword">Senha atual</label>
		<input 	id="oldpassword" type="password" name="oldpassword" maxlength="255" />
		<label for="newpassword">Nova senha</label>
		<input 	id="newpassword" type="password" name="newpassword" maxlength="255" />
		<label for="newpasswordrepeat">Confirme a nova senha</label>
		<input 	id="newpasswordrepeat" type="password" name="newpasswordrepeat" maxlength="255" />
		<button type="submit" class="submit">Alterar senha</button>
		</form>
		
		<?php
	}
	else // ($message == EveUserService::USER_CHANGE_PASSWORD_SUCCESS)
	{
		// It shows when password is not changed due to some error or it's a newly loaded page (with no postdata)
		?>
		<div class="dialog_panel">
		<p>A senha do usuário <?php echo $_SESSION['screenname'];?> foi alterada.</p>
		<button type="button" class="submit" onclick="window.location.href='userarea.php'"><?php echo $eve->_('common.action.back');?></button>
		</div>
		
		<?php
	}
	$eve->output_html_footer();
}?>
