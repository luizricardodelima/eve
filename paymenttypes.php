<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);

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
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case "create":
			$evePaymentService->paymenttype_create($_POST['paymenttype_name']);
			break;
		case "delete":
			$evePaymentService->paymenttype_delete($_POST['paymenttype_id']);
			break;
	}
	$eve->output_redirect_page(basename(__FILE__));// TODO Redirect with success/error messages
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Tipos de pagamentos", null);

	?>
	<div class="section">
	<button type="button" onclick="create_paymenttype();"/>Criar tipo de pagamento</button>
	</div>
	<table class="data_table">
	<tr>
	<th style="width:05%">Id</th>
	<th style="width:20%">Nome</th>
	<th style="width:60%">Descrição</th>
	<th style="width:15%" colspan="2"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	foreach ($evePaymentService->paymenttype_list() as $paymenttype)
	{	
		?>
		<tr>
		<td style="text-align:center"><?php echo $paymenttype['id'];?></td>
		<td style="text-align:left"><?php echo $paymenttype['name'];?></td>
		<td style="text-align:left"><?php echo $paymenttype['description'];?></td>
		<td><button type="button" onclick="window.location.href='paymenttype.php?id=<?php echo $paymenttype['id'];?>'"><img src="style/icons/edit.png"></button></td>
		<td><button type="button" onclick="delete_row(<?php echo $paymenttype['id'];?>)"><img src="style/icons/delete.png"></button></td>
		</tr>
		<?php
	}
	?>
	</table>
	<script>
	function delete_row(paymenttype_id)
	{
		if (confirm("Confirma a exclusão do tipo de pagamento de id " + paymenttype_id + "? Esta ação não poderá ser desfeita."))
		{
			document.getElementById('paymenttype_id_hidden_value').value=paymenttype_id;
			document.getElementById('delete_form').submit();
		}
		return false;
	}
	function create_paymenttype()
	{
		var paymenttype_name = prompt("Digite o nome do tipo de pagamento");
		if (paymenttype_name != null)
		{
			document.getElementById('paymenttype_name_hidden_value').value=paymenttype_name;
			document.getElementById('create_form').submit();

		}
		return false;
	}
	</script>
	<form method="post" id="delete_form">
		<input type="hidden" name="action" value="delete"/>
		<input type="hidden" name="paymenttype_id" id="paymenttype_id_hidden_value"/>
	</form>
	<form method="post" id="create_form">
		<input type="hidden" name="action" value="create"/>
		<input type="hidden" name="paymenttype_name" id="paymenttype_name_hidden_value"/>
	</form>
	<?php

	$eve->output_html_footer();
}
?>
