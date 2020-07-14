<?php
session_start();
require_once '../../eve.class.php';
require_once '../../evepaymentservice.php';
require_once 'lib/config/PagSeguroConfig.php';

$eve = new Eve("../../");
$evePaymentService = new EvePaymentService($eve);

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
else if (isset($_POST['action']))switch ($_POST['action'])
{
	case 'save_payment_information':
		// Saving information
		// TODO PREPARED STATEMENTS
		// TODO DISPLAY MESSAGE THROWN
		$eve->mysqli->query
		("
			delete from	`{$eve->DBPref}settings`
			where 		`key` = 'plugin_pagseguro_paymentinformation';
		");
		$eve->mysqli->query
		("
			insert into	`{$eve->DBPref}settings` (`key`, `value`)
			values		('plugin_pagseguro_paymentinformation', '{$_POST['structure']}');
		");
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&success=1");
		break;
	case 'save_purchase_paymenttype_id':
		$eve->mysqli->query
		("
			delete from	`{$eve->DBPref}settings`
			where 		`key` = 'plugin_pagseguro_paymenttypeid';
		");
		$eve->mysqli->query
		("
			insert into	`{$eve->DBPref}settings` (`key`, `value`)
			values		('plugin_pagseguro_paymenttypeid', '{$_POST['paymenttype_id']}');
		");
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&success=2");
		break;
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "../../userarea.php", $eve->_('userarea.option.admin.settings'), "../../settings.php", "Pag seguro", null);
	?>
	<div class="section">Passo 1 - Edite o arquivo <strong>/plugins/pagseguro/lib/config/PagSeguroConfig.php</strong> com as informações de sua conta PagSeguro e parâmetros de execução</div>
	<table class="data_table">
	<thead><th>Variável</th><th>Valor</th></thead>
	<tr><td>$PagSeguroConfig['environment']</td><td><?php echo $PagSeguroConfig['environment'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['email']</td><td><?php echo $PagSeguroConfig['credentials']['email'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['token']['production']</td><td><?php echo $PagSeguroConfig['credentials']['token']['production'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['token']['sandbox']</td><td><?php echo $PagSeguroConfig['credentials']['token']['sandbox'];?></td></tr>
	<tr><td>$PagSeguroConfig['application']['charset']</td><td><?php echo $PagSeguroConfig['application']['charset'];?></td></tr>
	<tr><td>$PagSeguroConfig['log']['active']</td><td><?php var_dump($PagSeguroConfig['log']['active']);?></td></tr>
	<tr><td>$PagSeguroConfig['log']['fileLocation']</td><td><?php echo $PagSeguroConfig['log']['fileLocation'];?></td></tr>
	</table>
	
	<div class="section">Passo 2 - Selecione o tipo de pagamento que corresponde ao pagamento com PagSeguro e clique em Salvar
	<button type="button" onclick="document.forms['save_purchase_paymenttype_id_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>
	<form method="post" id="save_purchase_paymenttype_id_form">
	<input type="hidden" name="action" value="save_purchase_paymenttype_id"/>
	<select name="paymenttype_id">
	<option value="" <?php if ($eve->getSetting('plugin_pagseguro_paymenttypeid') == "") echo "selected=\"selected\"";?>><?php echo $eve->_('common.select.null');?></option>
	<?php
	foreach ($evePaymentService->paymenttype_list() as $paymenttype)
	{	
		?>
		<option value="<?php echo $paymenttype['id'];?>" <?php if ($eve->getSetting('plugin_pagseguro_paymenttypeid') == $paymenttype['id']) echo "selected=\"selected\"";?>><?php echo $paymenttype['name'];?></option>
		<?php
	}
	?>
	?>
	</select>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
