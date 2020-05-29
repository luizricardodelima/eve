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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), null);

	?>
	<div class="section"><?php echo $eve->_('userarea.option.admin.settings');?></div>
	<div class="dialog_panel_thin">
	<div class="dialog_section">Geral</div>
		<button type="button" class="submit" onclick="window.location.href='settingsgeneralinfo.php'">Informações gerais</button>
		<button type="button" class="submit" onclick="window.location.href='settingsadmins.php'">Administradores do sistema</button>
		<button type="button" class="submit" onclick="window.location.href='settingsphpmailer.php'">Envio de e-mail</button>
		<button type="button" class="submit" onclick="window.location.href='settingsappearance.php'">Aparência</button>
	</div>

	<div class="dialog_panel_thin">
	<div class="dialog_section">Inscrições, usuários e pagamentos</div>
	<button type="button" class="submit" onclick="window.location.href='settingsusersignup.php'">Inscrições</button>
	<button type="button" class="submit" onclick="window.location.href='settingsuserdata.php'">Dados do usuário</button>
	<button type="button" class="submit" onclick="window.location.href='settingscredential.php'">Credenciais</button>
	</div>

	<?php $eve->output_medium_goto_button("payments", "Pagamentos", "settingspayments.php");?>
	<?php $eve->output_medium_goto_button("paymentslisting", "Listagem dos Pagamentos", "settingspaymentslisting.php");?>
	
	<div class="dialog_panel_thin">
	<div class="dialog_section">Submissões e certificados</div>
	<button type="button" class="submit" onclick="window.location.href='settingssubmissions.php'">Submissões</button>
	<button type="button" class="submit" onclick="window.location.href='settingsreviewers.php'">Revisores e revisões</button>
	<button type="button" class="submit" onclick="window.location.href='settingscertification.php'">Certificados</button>
	</div>

	<div class="dialog_panel_thin">
	<div class="dialog_section"><?php echo $eve->_('userarea.option.admin.settings.plugins');?></div>
	<?php
	// Showing plugins configs
	$plugins = glob('plugins/*' , GLOB_ONLYDIR);
	if (!empty($plugins))
	{
		foreach ($plugins as $plugin)
		{
			$plugin_info = parse_ini_file("$plugin/plugin.ini");
			echo "<button type=\"button\" class=\"submit\" onclick=\"window.location.href='$plugin/{$plugin_info['settingsscreen']}'\">{$plugin_info['name']}</button>";
		}
	}
	?>
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>

