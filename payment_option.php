<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);
$payment_option = $evePaymentService->payment_option_get($_GET['id']); //(sql injections handled in this function)

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
// Checking if payment type exists 
else if ($payment_option == null)
{
	$eve->output_error_page('common.message.invalid.parameter'); 
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular page.
else
{
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_("payment_options") => 'payment_options.php',
		$eve->_('payment.option') . ' ('. $payment_option['id'] . ')' => null,
	]);	
	?>
	<div class="section"><?php echo $eve->_('payment.option') . ' ('. $payment_option['id'] . ')'; ?>
	<button type="button" onclick="document.forms['payment_option_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>
	<?php

	$data = array();
	if (!empty($_POST))
	{
		// Space for validation

		// If validation is ok, update $payment_option with the new values
		foreach ($_POST as $column => $value) {$payment_option[$column] = $value;}
		// Saving data
		$message = $evePaymentService->payment_option_update($_POST);
		$eve->output_service_message($message);
	}

	?>
	<form action="<?php echo basename(__FILE__)."?id={$payment_option['id']}";?>" id="payment_option_form" method="post" class="dialog_panel">
	<input type="hidden" name="id" value="<?php echo $payment_option['id'];?>""/>
	<label for="id"><?php echo $eve->_('payment.option.id');?></label>
	<input 	id="id" type="text" value="<?php echo $payment_option['id'];?>" disabled="disabled"/>
	<label for="type"><?php echo $eve->_('payment.option.type');?></label>
	<select id="type" name="type">
	<?php 
	foreach($evePaymentService->payment_option_types() as $type)
	{	
		echo "<option value=\"$type\"";
		if ($payment_option['type'] == $type) echo " selected=\"selected\"";
		echo ">".$eve->_('payment.option.type.'.$type)."</option>";
	}
	?>
	</select>
	<label for="name"><?php echo $eve->_('payment.option.name');?></label>
	<input 	id="name" type="text" name="name" value="<?php echo $payment_option['name'];?>"/>
	<label for="description"><?php echo $eve->_('payment.option.description');?></label>
	<input 	id="description" type="text" name="description" value="<?php echo $payment_option['description'];?>"/>
	<label for="reference_code"><?php echo $eve->_('payment.option.reference.code');?></label>
	<input 	id="reference_code" type="text" name="reference_code" value="<?php echo $payment_option['reference_code'];?>"/>
	<label for="value"><?php echo $eve->_('payment.option.value');?></label>
	<input 	id="value" type="number" name="value" value="<?php echo $payment_option['value'];?>" min="0.0" step="0.01"/>
	<label for="available_from"><?php echo $eve->_('payment.option.available.from');?></label>
	<input 	id="available_from" type="date" name="available_from" value="<?php echo $payment_option['available_from'];?>"/>
	<label for="available_to"><?php echo $eve->_('payment.option.available.to');?></label>
	<input 	id="available_to" type="date" name="available_to" value="<?php echo $payment_option['available_to'];?>"/>
	<label for="admin_only">
	<input type="hidden" name="admin_only" value="0"/>
	<input type="checkbox" name="admin_only" id="admin_only" value="1" <?php if ($payment_option['admin_only']) echo "checked=\"checked\"";?> />
	<?php echo $eve->_('payment.option.admin.only');?>
	</label>
	</form>
	<?php

	$eve->output_html_footer();
}?>
