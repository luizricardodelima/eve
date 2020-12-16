<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';

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
// current page without post actions.
else if (isset($_POST['action'])) switch ($_POST['action'])
{
	case "create":
		$message = $evePaymentService->payment_option_create($_POST['payment_option_name']);
		$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
	case "delete":
		$message = $evePaymentService->payment_option_delete($_POST['payment_option_id']);
		$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header(['sort-table']);
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_("payment_options") => null
	]);

	?>
	<div class="section"><?php echo $eve->_('payment_options'); ?>
	<button type="button" onclick="payment_option_create()"><?php echo $eve->_('payment_options.button.create'); ?></button>
	</div>
	<?php
	
	if (isset($_GET['message'])) $eve->output_service_message($_GET['message']);

	$formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
	
	?>
	<table class="data_table" id="payment_options_table">
	<tr>
	<th style="width:05%" onclick="sortColumn('payment_options_table',0,false)"><?php echo $eve->_('payment.option.id'); ?></th>
	<th style="width:20%" onclick="sortColumn('payment_options_table',1,false)"><?php echo $eve->_('payment.option.name'); ?></th>
	<th style="width:20%" onclick="sortColumn('payment_options_table',2,false)"><?php echo $eve->_('payment.option.type'); ?></th>
	<th style="width:20%" onclick="sortColumn('payment_options_table',3,false)"><?php echo $eve->_('payment.group'); ?></th>
	<th style="width:20%"><?php echo $eve->_('payment.option.value'); ?></th>
	<th style="width:15%" colspan="2"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	$payment_groups = $evePaymentService->payment_group_list(true);
	foreach ($evePaymentService->payment_option_list(false, false) as $payment_option)
	{	
		?>
		<tr>
		<td style="text-align:center"><?php echo $payment_option['id'];?></td>
		<td style="text-align:left"><?php echo $payment_option['name'];?></td>
		<td style="text-align:left"><?php echo $eve->_('payment.option.type.'.$payment_option['type']);?></td>
		<td style="text-align:left"><?php echo ($payment_option['payment_group_id'] === null)  ? $eve->_('common.select.none') : $payment_groups[$payment_option['payment_group_id']]['name'];?></td>
		<td style="text-align:right"><?php echo $formatter->format($payment_option['value']);?></td>	
		<td><button type="button" onclick="window.location.href='payment_option.php?id=<?php echo $payment_option['id'];?>'"><img src="style/icons/edit.png"></button></td>
		<td><button type="button" onclick="payment_option_delete(<?php echo $payment_option['id'];?>)"><img src="style/icons/delete.png"></button></td>
		</tr>
		<?php
	}
	?>
	</table>
	<script>
	function payment_option_delete(payment_option_id)
	{
		var raw_message = '<?php echo $eve->_("payment_options.message.delete")?>';
		var message = raw_message.replace("<ID>", payment_option_id)	
		if (confirm(message))
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'delete');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'payment_option_id');
        	var2.setAttribute('value', payment_option_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();
		}
	}
	function payment_option_create()
	{
		var message = '<?php echo $eve->_("payment_options.message.create")?>';
		var payment_option_name = prompt(message);
		if (payment_option_name != null)
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'create');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'payment_option_name');
        	var2.setAttribute('value', payment_option_name);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();
		}
	}
	</script>
	<?php

	$eve->output_html_footer();
}
?>
