<?php
session_start();
require_once '../../eve.class.php';
require_once 'lib/config/PagSeguroConfig.php';

$eve = new Eve();

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
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "../../userarea.php", $eve->_('userarea.option.admin.settings'), "../../settings.php", "PagSeguro", null);
	?>
	<div class="section">Variáves da conta PagSeguro. Para editar, modifique o arquivo <strong>/plugins/pagseguro/lib/config/PagSeguroConfig.php</strong> com as respectivas informações.</div>
	<table class="data_table">
	<thead><th>Variável</th><th>Valor</th></thead>
	<tr><td>$PagSeguroConfig['environment']</td><td><?php echo $PagSeguroConfig['environment'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['email']</td><td><?php echo $PagSeguroConfig['credentials']['email'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['token']['production']</td><td><?php echo $PagSeguroConfig['credentials']['token']['production'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['token']['sandbox']</td><td><?php echo $PagSeguroConfig['credentials']['token']['sandbox'];?></td></tr>
	<tr><td>$PagSeguroConfig['application']['charset']</td><td><?php echo $PagSeguroConfig['application']['charset'];?></td></tr>
	<tr><td>$PagSeguroConfig['log']['active']</td><td><?php if (!$PagSeguroConfig['log']['active']) echo "false"; else echo "true";?></td></tr>
	<tr><td>$PagSeguroConfig['log']['fileLocation']</td><td><?php echo $PagSeguroConfig['log']['fileLocation'];?></td></tr>
	</table>
	
	<div class="section">Arquivos de logs. É necessário que a pasta <strong>/plugins/pagseguro/log</strong> tenha permissão de escrita.</div>
	<div class="dialog_panel">Status atual:
	<?php if (is_writable('log/')) echo "Permissão ok"; else echo "Permissão negada";?>
	</div>

	<?php
	// TODO #5 Create an option for configuring a prefix code for items on PagSeguro plugin

	// TODO #6 Create an option for configuring a different payment method name on PagSeguro plugin
	$eve->output_html_footer();
}
?>
