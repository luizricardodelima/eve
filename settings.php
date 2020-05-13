<?php
session_start();
require_once 'eve.class.php';


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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", null);

	?>
	<div class="section">Geral</div>
	<?php $eve->output_medium_goto_button("generalinfo", "Informações gerais", "settingsgeneralinfo.php");?>
	<?php $eve->output_medium_goto_button("admins", "Administradores do sistema", "settingsadmins.php");?>
	<?php $eve->output_medium_goto_button("phpmailer", "Envio de e-mail", "settingsphpmailer.php");?>
	<?php $eve->output_medium_goto_button("appearance", "Aparência", "settingsappearance.php");?>
	<div class="section">Inscrições, usuários e pagamentos</div>
	<?php $eve->output_medium_goto_button("usersignup", "Inscrições", "settingsusersignup.php");?>	
	<?php $eve->output_medium_goto_button("userdata", "Dados do usuário", "settingsuserdata.php");?>
	<?php $eve->output_medium_goto_button("credential", "Credenciais", "settingscredential.php");?>
	<?php $eve->output_medium_goto_button("payments", "Pagamentos", "settingspayments.php");?>
	<?php $eve->output_medium_goto_button("paymentslisting", "Listagem dos Pagamentos", "settingspaymentslisting.php");?>
	<div class="section">Trabalhos e certificados</div>
	<?php $eve->output_medium_goto_button("reviewers", "Revisores de trabalhos", "settingsreviewers.php");?>
	<?php $eve->output_medium_goto_button("certification", "Certificados", "settingscertification.php");?>

	<?php
	// Showing plugins configs
	$plugins = glob('plugins/*' , GLOB_ONLYDIR);
	if (!empty($plugins))
	{
		echo "<div class=\"section\">Plug-ins</div>";
		foreach ($plugins as $plugin)
		{
			$plugin_info = parse_ini_file("$plugin/plugin.ini");
			$eve->output_medium_goto_button(str_replace("/","",$plugin), $plugin_info['name'], "$plugin/{$plugin_info['settingsscreen']}");
		}
	}
	echo "<br/>";
	$eve->output_medium_goto_button("back", "Voltar", "userarea.php");
	$eve->output_html_footer();
}
?>

