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
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action'])) switch ($_POST['action'])
{
	case "create":
		$message = $evePaymentService->payment_group_create($_POST['payment_group_name']);
		$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
	case "delete":
		$message = $evePaymentService->payment_group_delete($_POST['payment_group_id']);
		$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_("payment_groups") => null
	]);

	?>
	<div class="section"><?php echo $eve->_('payment_groups'); ?>
	<button type="button" onclick="payment_group_create()"><?php echo $eve->_('payment_groups.button.create'); ?></button>
	</div>
	<?php
	
	if (isset($_GET['message'])) 
		$eve->output_service_message($_GET['message']);
	
	?>
	<table class="data_table">
	<tr>
	<th style="width:05%"><?php echo $eve->_('payment.group.id'); ?></th>
	<th style="width:30%"><?php echo $eve->_('payment.group.name'); ?></th>
	<th style="width:30%"><?php echo $eve->_('payment.group.state'); ?></th>
	<th style="width:15%" colspan="2"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	foreach ($evePaymentService->payment_group_list() as $payment_group)
	{	
		?>
		<tr>
		<td style="text-align:center"><?php echo $payment_group['id'];?></td>
		<td style="text-align:left"><?php echo $payment_group['name'];?></td>
		<td style="text-align:left"><?php echo $eve->_('payment.group.state.'.$payment_group['state']);?></td>
		<td><button type="button" onclick="window.location.href='payment_group.php?id=<?php echo $payment_group['id'];?>'"><img src="style/icons/edit.png"></button></td>
		<td><button type="button" onclick="payment_group_delete(<?php echo $payment_group['id'];?>)"><img src="style/icons/delete.png"></button></td>
		</tr>
		<?php
	}
	?>
	</table>
	<script>
	function payment_group_delete(payment_group_id)
	{
		var raw_message = '<?php echo $eve->_("payment_groups.message.delete")?>';
		var message = raw_message.replace("<ID>", payment_group_id)	
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
			var2.setAttribute('name', 'payment_group_id');
        	var2.setAttribute('value', payment_group_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();
		}
	}
	function payment_group_create()
	{
		var message = '<?php echo $eve->_("payment_groups.message.create")?>';
		var payment_group_name = prompt(message);
		if (payment_group_name != null)
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
			var2.setAttribute('name', 'payment_group_name');
        	var2.setAttribute('value', payment_group_name);
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
