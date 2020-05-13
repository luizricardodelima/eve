<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eveUserServices = new EveUserServices($eve);

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
		$message = $eveUserServices->user_change_password($_SESSION['screenname'], $_POST['oldpassword'], $_POST['newpassword'], $_POST['newpasswordrepeat']);
	}
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Alterar senha", null);

	?>
	<div class="section">Alterar senha</div>
	<?php

	if ($message !== null) switch ($message)
	{
		case EveUserServices::USER_CHANGE_PASSWORD_ERROR:
			$eve->output_error_message("Erro inesperado.");
			break;
		case EveUserServices::USER_CHANGE_PASSWORD_ERROR_INCORRECT_PASSWORD:
			$eve->output_error_message("Senha atual incorreta.");
			break;
		case EveUserServices::USER_CHANGE_PASSWORD_ERROR_PASSWORDS_DO_NOT_MATCH:
			$eve->output_error_message("As senhas novas não conferem.");
			break;
		case EveUserServices::USER_CHANGE_PASSWORD_ERROR_PASSWORD_TOO_SMALL:
			$eve->output_error_message("A nova senha é muito pequena.");
			break;	
	}
	
	if ($message !== EveUserServices::USER_CHANGE_PASSWORD_SUCCESS)
	{
		// It shows when password is not changed due to some error or it's a newly loaded page (with no postdata)
		?>
		<form method="post" id="passwordchange_form" class="user_dialog_panel">
		<p>Alterando a senha para <?php echo $_SESSION['screenname'];?></p>
		<label for="passwordchange_oldpassword_ipt">Senha atual</label>
		<input id="passwordchange_oldpassword_ipt" type="password" name="oldpassword" maxlength="255" />
		<label for="passwordchange_newpassword_ipt">Nova senha</label>
		<input id="passwordchange_newpassword_ipt" type="password" name="newpassword" maxlength="255" />
		<label for="passwordchange_newpasswordconfirm_ipt">Confirme a nova senha</label>
		<input for="passwordchange_newpasswordconfirm_ipt" type="password" name="newpasswordrepeat" maxlength="255" />
		<button type="submit" class="submit">Alterar senha</button>
		<p></p>
		</form>
		
		<?php
	}
	else // ($message == EveUserServices::USER_CHANGE_PASSWORD_SUCCESS)
	{
		// It shows when password is not changed due to some error or it's a newly loaded page (with no postdata)
		?>
		<div class="user_dialog_panel">
		<p>A senha do usuário <?php echo $_SESSION['screenname'];?> foi alterada.</p>
		<button type="button" class="submit" onclick="window.location.href='userarea.php'"><?php echo $eve->_('common.action.back');?></button>
		<p></p>
		</div>
		
		<?php
	}
	$eve->output_html_footer();
}?>
