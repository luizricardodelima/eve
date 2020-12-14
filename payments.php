<?php
session_start();
require_once 'eve.class.php';
require_once 'evemail.class.php';
require_once 'evepaymentservice.class.php';

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
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case "delete":
			$evePaymentService = new EvePaymentService($eve);
			$evePaymentService->payment_delete($_POST['pmt_id']);
			$eve->output_redirect_page(basename(__FILE__).""); // TODO ordering and filter
		break;
	}
}
else
{
	$evePaymentService = new EvePaymentService($eve);
	$curr_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
	$date_formatter = new IntlDateFormatter($eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.payments'), null);

	?>
	<script>
	function change_view()
	{
		switch (document.querySelector('input[name="view"]:checked').value)
		{
			case 'payment_view':
				document.getElementById('payment_view_table').style.display = 'table';
				document.getElementById('paymentitem_view_table').style.display = 'none';
				document.getElementById('summary_view_table').style.display = 'none';
				var elements = document.getElementsByClassName('payment_view_button');
				for (var i = 0; i < elements.length; i++) elements[i].style.display = "inline";
				break;
			case 'paymentitem_view':
				document.getElementById('payment_view_table').style.display = 'none';
				document.getElementById('paymentitem_view_table').style.display = 'table';
				document.getElementById('summary_view_table').style.display = 'none';
				var elements = document.getElementsByClassName('payment_view_button');
				for (var i = 0; i < elements.length; i++) elements[i].style.display = "none";
				break;
			case 'summary_view':
				document.getElementById('payment_view_table').style.display = 'none';
				document.getElementById('paymentitem_view_table').style.display = 'none';
				document.getElementById('summary_view_table').style.display = 'table';
				var elements = document.getElementsByClassName('payment_view_button');
				for (var i = 0; i < elements.length; i++) elements[i].style.display = "none";
				break;
		}
	}

	function payment_create()
	{
		var message = '<?php echo $eve->_("payments.message.create")?>';
		var payment_screenname = prompt(message);
		if (payment_screenname != null)
		{
			window.location='paymentedit.php?screenname=' + payment_screenname;
		}
	}
	function payment_delete(payment_id)
	{
		var raw_message = '<?php echo $eve->_("payments.message.delete")?>';
		var message = raw_message.replace("<ID>", payment_id)	
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
			var2.setAttribute('name', 'pmt_id');
        	var2.setAttribute('value', payment_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();
		}
	}
	</script>	

	<div class="section">		
	<input type="radio" name="view" id="payment_view_option" value="payment_view" checked="checked" onchange="change_view();">
	<label for="payment_view_option"><?php echo $eve->_('payments.option.paymentview');?></label>
	<input type="radio" name="view" id="paymentitem_view_option" value="paymentitem_view" onchange="change_view();">
	<label for="paymentitem_view_option"><?php echo $eve->_('payments.option.paymentitemview');?></label>
	<input type="radio" name="view" id="summary_view_option" value="summary_view" onchange="change_view();">
	<label for="summary_view_option"><?php echo $eve->_('payments.option.summaryview');?></label>
	<button type="button" class="payment_view_button" onclick="payment_create()">
	Criar pagamento</button>
	<button type="button" class="payment_view_button" onclick="window.location='paymentsexport.php';">
	Exportar</button>
	<button type="button" class="payment_view_button" onclick="window.location='settingspaymentslisting.php';">
	Configurar</button>
	</div>

	<!-- Payment view start ------------------------------------------------------------------->
	<table class="data_table" id="payment_view_table">
	<tr>
	<th style="width: 04%"><a href="<?php echo basename(__FILE__);?>?order-by=id">Id</a></th>
	<th style="width: 09%"><a href="<?php echo basename(__FILE__);?>?order-by=group">Grupo</a></th>
	<th style="width: 20%"><a href="<?php echo basename(__FILE__);?>?order-by=name">Nome</a></th>
	<th style="width: 15%"><a href="<?php echo basename(__FILE__);?>?order-by=email">E-mail</a></th>
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=payment-method">Tipo pgt.</a></th>
	<th style="width: 9%"><a href="<?php echo basename(__FILE__);?>?order-by=value-paid">Valor pago</a></th>
	<th style="width: 9%"><a href="<?php echo basename(__FILE__);?>?order-by=value-received">Valor receb.</a></th>
	<th style="width: 9%"><a href="<?php echo basename(__FILE__);?>?order-by=date">Data</a></th>
	<th style="width: 05%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	$payment_groups = $evePaymentService->payment_group_list(true);
	foreach ($evePaymentService->payment_list() as $payment)
	{
		echo "<tr>";
		echo "<td>{$payment['id']}</td>";
		echo "<td>";
		echo $payment['payment_group_id'] === null ? $eve->_('common.select.none') : $payment_groups[$payment['payment_group_id']]['name'];
		echo "</td>";
		echo "<td>{$payment['name']}</td>";
		echo "<td>{$payment['email']}</td>";
		echo "<td>{$payment['payment_method']}</td>";
		echo "<td>".$curr_formatter->format($payment['value_paid'])."</td>";
		echo "<td>".$curr_formatter->format($payment['value_received'])."</td>";
		echo "<td>".$date_formatter->format(strtotime($payment['date']))."</td>";
		echo "<td><button type=\"button\" onclick=\"window.location='paymentedit.php?id={$payment['id']}'\"><img src=\"style/icons/payment_edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location='user.php?user={$payment['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"payment_delete({$payment['id']})\"><img src=\"style/icons/delete.png\"></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	<!-- Payment view end --------------------------------------------------------------------->

	<!-- Payment item view start -------------------------------------------------------------->
	<table class="data_table" id="paymentitem_view_table" style="display:none;">
	<thead>	
	<th style="width: 40%">A</th>
	<th style="width: 20%">B</th>
	<th style="width: 20%">C</th>
	<th style="width: 20%">D</th>
	</thead>
	<tbody>
	</tbody>
	</table>
	<!-- Payment item view end ---------------------------------------------------------------->

	<!-- Summary view start ------------------------------------------------------------------->
	<table class="data_table" id="summary_view_table" style="display:none;">
	<thead>	
	<th style="width: 40%">Tipo de pagamento</th>
	<th style="width: 20%">Quantidade</th>
	<th style="width: 20%">Valor pago</th>
	<th style="width: 20%">Valor recebido</th>
	</thead>
	<tbody>
	<?php
	$total_user_count = 0;
	$total_value_paid_sum = 0;
	$total_value_received_sum = 0;	
	foreach ($evePaymentService->payment_list_summary() as $summary_item)
	{
		echo "<tr>";
		echo ($summary_item['payment_method'] === null) ? "<td>{$eve->_('payment.null')}</td>" : "<td>{$summary_item['payment_method']}</td>";
		echo "<td>{$summary_item['user_count']}</td>";
		echo "<td>{$curr_formatter->format($summary_item['value_paid_sum'])}</td>";
		echo "<td>{$curr_formatter->format($summary_item['value_received_sum'])}</td>";
		echo "</tr>";
		$total_user_count += $summary_item['user_count'];
		$total_value_paid_sum += $summary_item['value_paid_sum'];
		$total_value_received_sum += $summary_item['value_received_sum'];	
	}
	?>
	<tr>
	<td><strong>Total</strong></td><!-- TODO G11N -->
	<td><strong><?php echo $total_user_count;?></strong></td>
	<td><strong><?php echo $curr_formatter->format($total_value_paid_sum);?></strong></td>
	<td><strong><?php echo $curr_formatter->format($total_value_received_sum);?></strong></td>
	</tr>
	</tbody>
	</table>
	<!-- Summary view end -------------------------------------------------------------------->
	
	<?php
	$eve->output_html_footer();
}
?>
