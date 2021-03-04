<?php
session_start();
require_once '../../eve.class.php';
require_once '../../evesettingsservice.class.php';
require_once 'lib/config/PagSeguroConfig.php';

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
		"PagSeguro" => null
	]);
	$settings = $eveSettingsService->settings_get
	(
		'plugin_pagseguro_active'
	);
	?>
	<div class="section">
	PagSeguro
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>
	
	<form class="dialog_panel_wide" id="settings_form" method="post">
	<div class="dialog_section">Geral</div>
	<label for="plugin_pagseguro_active"><input type="hidden" name="plugin_pagseguro_active" value="0"/>
	<input 	id="plugin_pagseguro_active" type="checkbox" name="plugin_pagseguro_active" value="1" <?php echo ($settings['plugin_pagseguro_active']) ? "checked=\"checked\"" : "";?> />
	Ativo
	</label>

	<div class="dialog_section">Variáves da conta PagSeguro</div>
	<p> Para editar, modifique o arquivo <strong>/plugins/pagseguro/lib/config/PagSeguroConfig.php</strong> com as respectivas informações.</p>
	<table class="data_table">
	<tr><td><code>$PagSeguroConfig['environment']</code></td>
	<td><code><?php echo $PagSeguroConfig['environment'];?></code></td></tr>
	<tr><td><code>$PagSeguroConfig['credentials']['email']</code></td>
	<td><code><?php echo $PagSeguroConfig['credentials']['email'];?></code></td></tr>
	<tr><td><code>$PagSeguroConfig['credentials']['token']['production']</code></td>
	<td><code><?php echo $PagSeguroConfig['credentials']['token']['production'];?></code></td></tr>
	<tr><td><code>$PagSeguroConfig['credentials']['token']['sandbox']</code></td>
	<td><code><?php echo $PagSeguroConfig['credentials']['token']['sandbox'];?></code></td></tr>
	<tr><td><code>$PagSeguroConfig['application']['charset']</code></td>
	<td><code><?php echo $PagSeguroConfig['application']['charset'];?></code></td></tr>
	<tr><td><code>$PagSeguroConfig['log']['active']</code></td>
	<td><code><?php if (!$PagSeguroConfig['log']['active']) echo "false"; else echo "true";?></code></td></tr>
	<tr><td><code>$PagSeguroConfig['log']['fileLocation']</code></td>
	<td><code><?php echo $PagSeguroConfig['log']['fileLocation'];?></code></td></tr>
	</table>
	
	<br/>
	<div class="dialog_section">Escrita de arquivos de logs</div>
	<p>
	<?php echo (is_writable('log/')) ? "Permissão ok": "Permissão negada. É necessário que a pasta <strong>/plugins/pagseguro/log</strong> tenha permissão de escrita.";?>
	</p>
	</form>
	<?php
	// TODO #5 Create an option for configuring a prefix code for items on PagSeguro plugin

	// TODO #6 Create an option for configuring a different payment method name on PagSeguro plugin
	$eve->output_html_footer();
}
?>
