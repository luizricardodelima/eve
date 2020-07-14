<?php
session_start();
require_once 'eve.class.php';
require_once 'evemail.php';
require_once 'evepaymentservice.php';

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
			$evePaymentService->remove_payment($_POST['pmt_id']);
			$eve->output_redirect_page(basename(__FILE__).""); // TODO ordering and filter
		break;
	}
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.payments'), null);
	setlocale(LC_MONETARY, $eve->getSetting('system_locale'));

	// TODO NOW IT'S GOING TO BE PAYMENT_METHODS. SELECT DISTINCT PAYMENT METHODS
	// Loading payment types information
	$paymenttypes = array();
	$paymenttypes_res = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}paymenttype` where `active` = 1;");
	while ($paymenttype = $paymenttypes_res->fetch_assoc())
	{
		$paymenttypes[$paymenttype['id']]['name'] = $paymenttype['name'];
		$paymenttypes[$paymenttype['id']]['description'] = $paymenttype['description'];
		$paymenttypes[$paymenttype['id']]['user_count'] = 0;
		$paymenttypes[$paymenttype['id']]['value_count'] = 0;
		$paymenttypes[$paymenttype['id']]['value_received_count'] = 0;
	}
	$paymenttypes[NULL]['name'] = $eve->_('paymenttype.name.null');
	$paymenttypes[NULL]['description'] = $eve->_('paymenttype.description.null');
	$paymenttypes[NULL]['user_count'] = 0;
	$paymenttypes[NULL]['value_count'] = 0;
	$paymenttypes[NULL]['value_received_count'] = 0;
	// According to manual, null will be cast to the empty string, i.e. the key null will actually be stored under "".
	// Source: http://php.net/manual/en/language.types.array.php
	
	// Ordering
	$order_by = (isset($_GET["order-by"])) ? $_GET["order-by"] : "name";
	$order_sql = '';
	switch ($order_by)
	{
		
		case 'email':
			$ordering = "`{$eve->DBPref}userdata`.`email`";
			break;
		case 'category':
			$ordering = "`{$eve->DBPref}usercategory`.`description`";
			break;
		case 'payment-type':
			$ordering = "`{$eve->DBPref}payment`.`paymenttype_id`";
			break;
		case 'value-paid':
			$ordering = "`{$eve->DBPref}payment`.`value_paid`";
			break;
		case 'value-received':
			$ordering = "`{$eve->DBPref}payment`.`value_received`";
			break;
		case 'date':
			$ordering = "`{$eve->DBPref}payment`.`date`";
			break;
		case 'note':
			$ordering = "`{$eve->DBPref}payment`.`note`";
			break;
		case 'name':
		default:
			$ordering = "`{$eve->DBPref}userdata`.`name`";
		break;
	}
	$user_res = $eve->mysqli->query
	("	select 
			`{$eve->DBPref}userdata`.`email`,
			`{$eve->DBPref}userdata`.`name`,
			`{$eve->DBPref}usercategory`.`description`,
			`{$eve->DBPref}payment`.`id`,
			`{$eve->DBPref}payment`.`paymenttype_id`,
			`{$eve->DBPref}payment`.`value_paid`,
			`{$eve->DBPref}payment`.`value_received`,
			`{$eve->DBPref}payment`.`date`,
			`{$eve->DBPref}payment`.`note`
		from
			`{$eve->DBPref}userdata`
		left outer join
			`{$eve->DBPref}payment` on (`{$eve->DBPref}userdata`.`email` = `{$eve->DBPref}payment`.`email`)
		left outer join
			`{$eve->DBPref}usercategory` on (`{$eve->DBPref}userdata`.`category_id` = `{$eve->DBPref}usercategory`.`id`)
		order by
			$ordering;
	");
	
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
	</script>	

	<form id="credentials_form" method="post" action="credential.php"></form>
	<form id="export_form" method="post" action="paymentsexport.php"></form>

	<div class="section">		
	<button type="button" onclick="window.location='paymentsexport.php';">Exportar tudo</button>
	<button type="button" onclick="export_selected()">Exportar</button>
	<button type="button" onclick="credentials()">Credencial</button>
	<button type="button" onclick="window.location='settingspaymentslisting.php';">Configurar</button>
	<span style="float: right;">
	<input type="radio" name="view" id="complete_view_option" value="complete" checked="checked" onchange="change_view();"><label for="complete_view_option">Completo</label>
	<input type="radio" name="view" id="short_view_option" value="short" onchange="change_view();"><label for="short_view_option">Resumido</label>
	</span>
	</div>
	<table class="data_table" id="complete_view_table">
	<tr>
	<th style="width: 3%"><input type="checkbox" onClick="toggle(this, 'screenname[]')"/></th>
	<th style="width: 15%"><a href="<?php echo basename(__FILE__);?>">Nome</a></th>
	<th style="width: 15%"><a href="<?php echo basename(__FILE__);?>?order-by=email">E-mail</a></th>
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=category">Categoria</a></th>		
	<th style="width: 10%"><a href="<?php echo basename(__FILE__);?>?order-by=payment-type">Tipo pgt.</a></th>
	<th style="width: 08%"><a href="<?php echo basename(__FILE__);?>?order-by=value-paid">Valor pago</a></th>
	<th style="width: 08%"><a href="<?php echo basename(__FILE__);?>?order-by=value-received">Valor receb.</a></th>
	<th style="width: 09%"><a href="<?php echo basename(__FILE__);?>?order-by=date">Data</a></th>
	<th style="width: 17%"><a href="<?php echo basename(__FILE__);?>?order-by=note">Observação</a></th>
	<th style="width: 05%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	
	while ($user_row = $user_res->fetch_assoc())
	{
		echo "<tr>";
		echo "<td><input type=\"checkbox\" name=\"screenname[]\" value=\"{$user_row['email']}\" onclick=\"toggleRow(this)\"/></td>";
		echo "<td>{$user_row['name']}</td>";
		echo "<td>{$user_row['email']}</td>";
		echo "<td>{$user_row['description']}</td>";
		echo "<td>{$paymenttypes[$user_row['paymenttype_id']]['name']}</td>";
		echo "<td>";
		// TODO money_format is deprecated!
		if (!is_null($user_row['value_paid'])) echo money_format('%n', $user_row['value_paid']);
		echo "</td>";
		echo "<td>";
		// TODO money_format is deprecated!
		if (!is_null($user_row['value_received'])) echo money_format('%n', $user_row['value_received']);
		echo "</td>";
		echo "<td>{$user_row['date']}</td>";
		echo "<td>{$user_row['note']}</td>";
		if (!is_null($user_row['id']))
		{
			echo "<td><button type=\"button\" onclick=\"window.location='paymentverification.php?screenname={$user_row['email']}'\"><img src=\"style/icons/payment_edit.png\"></button></td>";
			echo "<td><button type=\"button\" onclick=\"window.location='user.php?user={$user_row['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
			echo "<td><button type=\"button\" onclick=\"delete_row({$user_row['id']},'{$user_row['email']}')\"><img src=\"style/icons/delete.png\"></button></td>";
		}
		else
		{
			echo "<td></td>";
			echo "<td><button type=\"button\" onclick=\"window.location='user.php?user={$user_row['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
			echo "<td><button type=\"button\" onclick=\"window.location='paymentverification.php?screenname={$user_row['email']}'\"><img src=\"style/icons/payment_verification.png\"></button></td>";
		}
		echo "</tr>";
		$paymenttypes[$user_row['paymenttype_id']]['user_count']++;
		if (!is_null($user_row['value_paid'])) $paymenttypes[$user_row['paymenttype_id']]['value_count'] += $user_row['value_paid'];
		if (!is_null($user_row['value_received'])) $paymenttypes[$user_row['paymenttype_id']]['value_received_count'] += $user_row['value_received'];
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
	
	<table class="data_table" id="short_view_table" style="display:none;">
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
	foreach ($paymenttypes as $paymenttype)
	{
		?>
		<tr>
		<td><?php echo $paymenttype['name'];?></td>
		<td><?php echo $paymenttype['user_count'];?></td>
		<td><?php echo money_format('%n', $paymenttype['value_count']);?></td>
		<td><?php echo money_format('%n', $paymenttype['value_received_count']);?></td>
		</tr>
		<?php
		$total_user_count += $paymenttype['user_count'];
		$total_value_paid_sum += $paymenttype['value_count'];
		$total_value_received_sum += $paymenttype['value_received_count'];
	}
	?>
	<tr>
	<td><strong>Total</strong></td>
	<td><strong><?php echo $total_user_count;?></strong></td>
	<td><strong><?php echo money_format('%n', $total_value_paid_sum);?></strong></td>
	<td><strong><?php echo money_format('%n', $total_value_received_sum);?></strong></td>
	</tr>
	</tbody>
	</table>
		
	<?php
	$eve->output_html_footer();
}
?>
