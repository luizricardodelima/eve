<?php
session_start();
require_once '../../eve.class.php';
require_once '../../evesettingsservice.class.php';

$eve = new Eve();
$eveSettingsService = new EveSettingsService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("../../userarea.php?sessionexpired=1");
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
// There are settings as POST variables to be saved.
else if (!empty($_POST))
{
	$msg = $eveSettingsService->settings_update($_POST);
	$eve->output_redirect_page(basename(__FILE__)."?msg=$msg");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "../../userarea.php",
		$eve->_('userarea.option.admin.settings') => "../../settings.php",
		"PayPal" => null
	]);
	$settings = $eveSettingsService->settings_get
	(
		'plugin_paypal_active', 'plugin_paypal_environment', 
		'plugin_paypal_sandbox_client_id', 'plugin_paypal_production_client_id'
	);
	?>
	<div class="section">PayPal
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form class="dialog_panel_wide" id="settings_form" method="post">
	<div class="dialog_section">Geral</div><!-- TODO G11N-->
	<label for="plugin_paypal_active"><input type="hidden" name="plugin_paypal_active" value="0"/>
	<input 	id="plugin_paypal_active" type="checkbox" name="plugin_paypal_active" value="1" <?php echo ($settings['plugin_paypal_active']) ? "checked=\"checked\"" : "";?> />
	Ativo<!-- TODO G11N-->
	</label>
	
	<div class="dialog_section">Conta Paypal</div><!-- TODO G11N-->
	<label for="plugin_paypal_environment">Ambiente</label><!-- TODO G11N-->
	<select id="plugin_paypal_environment" name="plugin_paypal_environment">
	<option value="sandbox" <?php echo $settings['plugin_paypal_environment'] == 'sandbox' ? "selected=\"selected\"" : ""; ?>>Sandbox</option>
	<option value="production" <?php echo $settings['plugin_paypal_environment'] == 'production' ? "selected=\"selected\"" : ""; ?>>Production</option>
	</select>

	<label for="plugin_paypal_sandbox_client_id">Sandbox client id</label>
	<input  id="plugin_paypal_sandbox_client_id" type="text" name="plugin_paypal_sandbox_client_id" value="<?php echo $settings['plugin_paypal_sandbox_client_id']; ?>"/>

	<label for="plugin_paypal_production_client_id">Production client id</label>
	<input  id="plugin_paypal_production_client_id" type="text" name="plugin_paypal_production_client_id" value="<?php echo $settings['plugin_paypal_production_client_id']; ?>"/>
	</form>
	<?php

	$eve->output_html_footer();
}
?>
