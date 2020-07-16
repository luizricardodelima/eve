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
	$formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);

	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.payments'), null);

	?>
	<script>
	function toggle(source, elementname)
	{
		checkboxes = document.getElementsByName(elementname);
		for(var i=0, n=checkboxes.length;i<n;i++)
		{
			checkboxes[i].checked = source.checked;
			toggleRow(checkboxes[i]);
		}
	}

	function toggleRow(source)
	{
		if (source.checked)
			source.parentNode.parentNode.classList.add('selected');
		else
			source.parentNode.parentNode.classList.remove('selected');
	}

	function credentials() {
		var container = document.getElementById("credentials_form");
		checkboxes = document.getElementsByName("screenname[]");
		for(var i=0; i < checkboxes.length; i++)
		{
			if (checkboxes[i].checked == true)
			{
				var input = document.createElement("input");
			        input.type = "hidden";
			        input.name = "screenname[" + i + "]";
				input.value = checkboxes[i].value;
				container.appendChild(input);
			}
		}
		document.forms['credentials_form'].submit();
	}

	function export_selected() {
		var container = document.getElementById("export_form");
		checkboxes = document.getElementsByName("screenname[]");
		for(var i=0; i < checkboxes.length; i++)
		{
			if (checkboxes[i].checked == true)
			{
				var input = document.createElement("input");
			        input.type = "hidden";
			        input.name = "screenname[" + i + "]";
				input.value = checkboxes[i].value;
				container.appendChild(input);
			}
		}
		document.forms['export_form'].submit();
	}
	/*
	TODO: REIMPLEMENT Summary View
	function change_view()
	{
		switch (document.querySelector('input[name="view"]:checked').value)
		{
			case 'complete':
				document.getElementById('complete_view_table').style.display = 'table';
				document.getElementById('short_view_table').style.display = 'none';
				break;
			case 'short':
				document.getElementById('short_view_table').style.display = 'table';
				document.getElementById('complete_view_table').style.display = 'none';
				break;
		}
	}
	*/
	</script>	

	<form id="credentials_form" method="post" action="credential.php"></form>
	<form id="export_form" method="post" action="paymentsexport.php"></form>

	<div class="section">		
	<button type="button" onclick="window.location='paymentsexport.php';">Exportar tudo</button>
	<button type="button" onclick="export_selected()">Exportar</button>
	<button type="button" onclick="credentials()">Credencial</button>
	<button type="button" onclick="window.location='settingspaymentslisting.php';">Configurar</button>
	<!-- TODO Reimplement Summary View
	<span style="float: right;">
	<input type="radio" name="view" id="complete_view_option" value="complete" checked="checked" onchange="change_view();"><label for="complete_view_option">Completo</label>
	<input type="radio" name="view" id="short_view_option" value="short" onchange="change_view();"><label for="short_view_option">Resumido</label>
	</span>
	-->
	</div>
	<table class="data_table" id="complete_view_table">
	<tr>
	<th style="width: 5%"><input type="checkbox" onClick="toggle(this, 'screenname[]')"/></th>
	<th style="width: 20%"><a href="<?php echo basename(__FILE__);?>">Nome</a></th>
	<th style="width: 15%"><a href="<?php echo basename(__FILE__);?>?order-by=email">E-mail</a></th>
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=payment-method">Tipo pgt.</a></th>
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=value-paid">Valor pago</a></th>
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=value-received">Valor receb.</a></th>
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=date">Data</a></th>
	<th style="width: 15%"><a href="<?php echo basename(__FILE__);?>?order-by=note">Observação</a></th>
	<th style="width: 05%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	foreach ($evePaymentService->payment_list() as $payment)
	{
		echo "<tr>";
		echo "<td><input type=\"checkbox\" name=\"screenname[]\" value=\"{$payment['email']}\" onclick=\"toggleRow(this)\"/></td>";
		echo "<td>{$payment['name']}</td>";
		echo "<td>{$payment['email']}</td>";
		echo "<td>{$payment['payment_method']}</td>";
		echo "<td>".$formatter->format($payment['value_paid'])."</td>";
		echo "<td>".$formatter->format($payment['value_received'])."</td>";
		echo "<td>{$payment['date']}</td>";
		echo "<td>{$payment['note']}</td>";
		if (!is_null($payment['id']))
		{
			echo "<td><button type=\"button\" onclick=\"window.location='paymentverification.php?screenname={$payment['email']}'\"><img src=\"style/icons/payment_edit.png\"></button></td>";
			echo "<td><button type=\"button\" onclick=\"window.location='user.php?user={$payment['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
			echo "<td><button type=\"button\" onclick=\"delete_row({$payment['id']},'{$payment['email']}')\"><img src=\"style/icons/delete.png\"></button></td>";
		}
		else
		{
			echo "<td></td>";
			echo "<td><button type=\"button\" onclick=\"window.location='user.php?user={$payment['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
			echo "<td><button type=\"button\" onclick=\"window.location='paymentverification.php?screenname={$payment['email']}'\"><img src=\"style/icons/payment_verification.png\"></button></td>";
		}
		echo "</tr>";
	}
	?>
	</table>
	<form method="post" id="delete_form">
		<input type="hidden" name="action" value="delete"/>
		<input type="hidden" name="pmt_id" id="pmt_id_hidden_value"/>
		<input type="hidden" name="pmt_email" id="pmt_email_hidden_value"/>
	</form>
	<script>
	function delete_row(pmt_id, pmt_email)
	{
		if (confirm("Confirma a exclusão do pagamento de " + pmt_email + "?"))
		{
			document.getElementById('pmt_id_hidden_value').value=pmt_id;
			document.getElementById('pmt_email_hidden_value').value=pmt_email;
			document.forms['delete_form'].submit();
		}
		return false;
	}
	</script>
	
	<!-- TODO Reimplement Summary View
	<table class="data_table" id="short_view_table" style="display:none;">
	<thead>	
	<th style="width: 40%">Tipo de pagamento</th>
	<th style="width: 20%">Quantidade</th>
	<th style="width: 20%">Valor pago</th>
	<th style="width: 20%">Valor recebido</th>
	</thead>
	<tbody>
	<tr>
	<td><strong>Total</strong></td>
	<td><strong><?php echo 0;?></strong></td>
	<td><strong><?php echo $formatter->format(0);?></strong></td>
	<td><strong><?php echo $formatter->format(0);?></strong></td>
	</tr>
	</tbody>
	</table>
	-->
	<?php
	$eve->output_html_footer();
}
?>
