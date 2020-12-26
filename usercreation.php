<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$default_password = "12345";
$eve = new Eve();

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
else
{
	$eve->output_html_header();
	// TODO G11N
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.users') => "users.php",
		"Criar usuário" => null
	]);
	
	?>
	<div class="section">Criar usuário</div>
	<?php

	if (!empty($_POST))
	{	
		// There is postdata.
		$EveUserService = new EveUserService($eve);
		$msg = $EveUserService->unverified_user_create($_POST['screenname'], $_POST['password'], $_POST['password'], false);

		if ($msg == EveUserService::UNVERIFIED_USER_CREATE_SUCCESS)
		{
			// Creating user // TODO G11N
		 	if ($EveUserService->unverified_user_transform_to_user($_POST['screenname'], $_POST['sendwelcomeemail']))
			{
				if ($_POST['sendwelcomeemail'])
					$eve->output_success_message("Usuário <a href=\"user.php?user={$_POST['screenname']}\">{$_POST['screenname']}</a> criado com sucesso. E-mail enviado.");
				else
					$eve->output_success_message("Usuário <a href=\"user.php?user={$_POST['screenname']}\">{$_POST['screenname']}</a> criado com sucesso.");
			}	
			else
			{
				$eve->output_error_message("Erro ao criar o usuário {$_POST['screenname']}.");
			}
			// Cleaning variables because they are displayed on textboxs again, when there are validation errors.
			$_POST['screenname'] = "";
		}
		else 
		{
			// there were errors on creating an unverified user
			if (isset($msg)) $eve->output_service_message($msg);
		}
	}
	else
	{
		// There is no postdata.
		$_POST['password'] = $default_password;
		$_POST['sendwelcomeemail'] = true;
	}
	
	?>
	<form method="post" id="newuser_form">
	<div class="dialog_panel">
	<label for="screenname">E-mail</label>
	<input  id="screenname" type="text" name="screenname" value="<?php if(isset($_POST['screenname'])) echo $_POST['screenname'];?>"/></td></tr>
	<label for="password">Senha</label>
	<input  id="password"type="text" name="password" value="<?php if(isset($_POST['password']))  echo $_POST['password'];?>"/></td></tr>
	<label for="sendwelcomeemail"><input type="hidden" name="sendwelcomeemail" value="0"/>
	<input  id="sendwelcomeemail" type="checkbox" name="sendwelcomeemail" <?php if ($_POST['sendwelcomeemail']) echo "checked=\"checked\"";?>/>E-mail de boas vindas</label>
	<button type="button" class="submit" onclick="document.forms['newuser_form'].submit();">Criar</button>
	</div>	
	</form>
		
	<?php
	$eve->output_html_footer();
}
?>
