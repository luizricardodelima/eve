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
// Checking if either a valid id or a valid screenname has been passed
// If invalid parameters were passed or no parameters were passed at all, display an error
else if 
(
	(isset($_GET['id']) && $evePaymentService->payment_get($_GET['id']) === null) || 
	(isset($_GET['screenname']) && !$eve->user_exists($_GET['screenname'])) || 
	(!isset($_GET['id']) && !isset($_GET['screenname']))
)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// Perform action if there is postdata
else if (!empty($_POST))
{	
	// Converting empty/unset values to default values
	if (!isset($_POST['id'])) $_POST['id'] = null;
	if ($_POST['payment_group_id'] === '')$_POST['payment_group_id'] = null;
	if (!isset($_POST['options'])) $_POST['options'] = array();

	$msg = $evePaymentService->payment_save
	(
		$_POST['id'], $_POST['payment_group_id'], $_POST['user_email'], $_POST['date'],
		$_POST['payment_method'], $_POST['note'], $_POST['value_paid'], $_POST['value_received'],
		$_POST['options']
	);

	if (is_numeric($msg)) // Create or update successful
	{
		unset($_GET['screenname']);
		$_GET['id'] = $msg;
		$msg = 'payment.save.success';
	}
	$eve->output_redirect_page(basename(__FILE__).'?'.http_build_query($_GET)."&msg=$msg");
}
// If there's a valid session, and the current user is administrator, there is
// valid screenname or a valid payment id and there is no postdata, display the regular page 
else
{
	$payment = isset($_GET['id']) ? $evePaymentService->payment_get($_GET['id']) : null;
	$page_title = ($payment === null) ? $eve->_('paymentedit.title.newpayment') : $eve->_('paymentedit.title', ['<ID>' => $_GET['id']]);

	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.payments') => "payments.php",
		$page_title => null,
	]);		
	
	?>
	<div class="section"><?php echo $page_title;?></div>
	<?php
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	if ($payment === null) // Creating new payment obect
	{
		$new_date = date('Y-m-d');
		$payment = array
		(
			'payment_group_id' => null,
			'user_email' => $_GET['screenname'],
			'date' => $new_date,
			'payment_method' => '',
			'value_paid' => 0,
			'value_received' => 0,
			'note' => ''
		);
	}

	?>
	<form action="<?php echo basename(__FILE__) . '?'.http_build_query($_GET); ?>" method="post">
	<div class="dialog_panel">

	<?php if (isset($payment['id'])) { ?>
	<input type="hidden" name="id" value="<?php echo $payment['id'];?>"/>
	<?php } ?>
	<input type="hidden" name="user_email" value="<?php echo $payment['user_email'];?>"/>

	<label for="user_email"><?php echo $eve->_('paymentedit.user');?></label>
	<input 	id="user_email" type="text" disabled="disabled" value="<?php echo $payment['user_email'];?>"/>

	<label for="payment_group_id"><?php echo $eve->_('paymentedit.payment.group');?></label>
	<select id="payment_group_id" name="payment_group_id">
	<?php
		echo "<option value=\"\">".$eve->_('common.select.none')."</option>";
		foreach ($evePaymentService->payment_group_list(true) as $payment_group)
		{
			echo "<option value=\"{$payment_group['id']}\"";
			if ($payment_group['id'] == $payment['payment_group_id']) echo " selected=\"selected\"";
			echo ">".$payment_group['name']."</option>";
		}
	?>
	</select>

	<label for="items"><?php echo $eve->_('paymentedit.items');?><button type="button" onclick="show_selector()"><?php echo $eve->_('common.action.add');?></button></label>
	<table 	id="items" class="data_table"><tbody>
	<?php
	$curr_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
	if (isset($payment['id'])) foreach ($evePaymentService->payment_item_list($payment['id']) as $item)
	{
		echo "<tr>";
		echo "<td>{$item['name']}</td>";
		echo "<td>".$curr_formatter->format($item['value'])."</td>";
		echo "<td><input type=\"hidden\" name=\"options[]\" value=\"{$item['payment_option_id']}\"/><button type=\"button\" onclick=\"item_remove(this)\"><img src=\"style/icons/delete.png\"></td>";
		echo "</tr>";
	}
	?>
	</tbody></table>
	<!-- Items selector -->
	<div id="selector" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
	<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('selector').style.display = 'none';"> X </button>
	<div id="selector_content" style="padding: 20px; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;"></div>
	</div></div>
	<script>
	var payment_options = [];
	function show_selector() {
		var payment_group_id = document.getElementById('payment_group_id').value;
		var content = document.getElementById('selector_content');
		while (content.firstChild) content.removeChild(content.firstChild);
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/payment_option_list.php?payment_group_id=' + payment_group_id);
		xhr.onload = function() {
		    if (xhr.status === 200) {
				var select = document.createElement('select');
				select.id = 'payment_option_select';
				select.onchange = function(){item_add();};
				content.appendChild(select);
				// Adding null select
				var option = document.createElement('option');
				option.value = '';
				option.innerHTML = '<?php echo $eve->_("common.select.null")?>';
				option.selected = true;
				select.appendChild(option);
				// Cleaning previously loaded payment options in the global variable payment_options
				while (payment_options.length) { payment_options.pop(); } 
				var data = JSON.parse(xhr.responseText);
				for (var i = 0; i < data.length; ++i) 
				{
					payment_options.push(data[i]);
					var option = document.createElement('option');
					option.value = i;
					option.innerHTML = data[i].name;
					select.appendChild(option);
				}
		    }
		    else {
				// HTTP Error message
				var paragraph = document.createElement('p'); 
				paragraph.textContent = '<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status;
				document.getElementById('selector_content').appendChild(paragraph);
		    }
		};
		xhr.send();
		document.getElementById('selector').style.display = 'block';
	}

	function item_add()
	{
		var selected = document.getElementById("payment_option_select").value;
		var tbodyRef = document.getElementById('items').getElementsByTagName('tbody')[0];
		var row = document.createElement('tr');
		var col1 = document.createElement('td');
		col1.textContent = payment_options[selected].name;
		row.appendChild(col1);
		var col2 = document.createElement('td');
		col2.textContent = payment_options[selected].formatted_value;
		row.appendChild(col2);
		var col3 = document.createElement('td');
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'options[]';
		input.value = payment_options[selected].id;
		col3.appendChild(input);
		var delete_button = document.createElement('button');
		delete_button.type = 'button';
		var delete_img = document.createElement('img');
		delete_img.src = 'style/icons/delete.png';
		delete_button.appendChild(delete_img);
		delete_button.onclick = function(){item_remove(delete_button);};
		col3.appendChild(delete_button);
		row.appendChild(col3);
		tbodyRef.appendChild(row);
		document.getElementById('selector').style.display = 'none';
	}

	function item_remove(action_source)
	{
		var tbodyRef = document.getElementById('items').getElementsByTagName('tbody')[0];
		tbodyRef.removeChild(action_source.parentNode.parentNode);
	}
	</script>

	<label for="date"><?php echo $eve->_('paymentedit.date');?></label>
	<input 	id="date" type="date" name="date" value="<?php echo $payment['date'];?>"/>

	<label for="payment_method"><?php echo $eve->_('paymentedit.payment.method');?></label>
	<input 	id="payment_method" type="text" name="payment_method" value="<?php echo $payment['payment_method'];?>"/>

	<label for="value_paid"><?php echo $eve->_('paymentedit.value.paid');?></label>
	<input 	id="value_paid" name="value_paid" type="number" min="0" step="0.01" value="<?php echo $payment['value_paid'];?>"/>

	<label for="value_received"><?php echo $eve->_('paymentedit.value.received');?></label>
	<input 	id="value_received" name="value_received" type="number" min="0" step="0.01" value="<?php echo $payment['value_received'];?>"/>

	<label for="note"><?php echo $eve->_('paymentedit.note');?></label>
	<textarea id="note" name="note" rows="5"><?php echo $payment['note'];?></textarea>

	<button type="submit" class="submit"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" class="altaction" onclick="window.location.href='payments.php'"><?php echo $eve->_('common.action.back');?></button>
	</div>	

	</form>	
	<?php

	$eve->output_html_footer();
}?>
