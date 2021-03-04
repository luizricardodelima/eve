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
		'plugin_paypal_active'
	);
	?>
	<div class="section">PayPal
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form class="dialog_panel_wide" id="settings_form" method="post">
	<div class="dialog_section">Geral</div>
	<label for="plugin_paypal_active"><input type="hidden" name="plugin_paypal_active" value="0"/>
	<input 	id="plugin_paypal_active" type="checkbox" name="plugin_paypal_active" value="1" <?php echo ($settings['plugin_paypal_active']) ? "checked=\"checked\"" : "";?> />
	Ativo
	</label>
	

	<?php

	$eve->output_html_footer();
}
?>
