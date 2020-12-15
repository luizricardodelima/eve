<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);
$payment_group = $evePaymentService->payment_group_get($_GET['id']); //(sql injections handled in this function)

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
else if ($payment_group == null)
{
	$eve->output_error_page('common.message.invalid.parameter'); 
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular page.
else
{
	$eve->output_html_header(['wysiwyg-editor']);
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_("payment_groups") => 'payment_groups.php',
		$eve->_('payment.group') . ' ('. $payment_group['id'] . ')' => null,
	]);	
	?>
	<div class="section"><?php echo $eve->_('payment.group') . ' ('. $payment_group['id'] . ')'; ?>
	<button type="button" onclick="document.forms['payment_group_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>
	<?php

	$data = array();
	if (!empty($_POST))
	{
		// Space for validation

		// If validation is ok, update $payment_group with the new values
		foreach ($_POST as $column => $value) {$payment_group[$column] = $value;}
		// Saving data
		$message = $evePaymentService->payment_group_update($_POST);
		$eve->output_service_message($message);
	}

	?>
	<form action="<?php echo basename(__FILE__)."?id={$payment_group['id']}";?>" id="payment_group_form" method="post" class="dialog_panel">
	<input type="hidden" name="id" value="<?php echo $payment_group['id'];?>"/>
	<label for="id"><?php echo $eve->_('payment.group.id');?></label>
	<input 	id="id" type="text" value="<?php echo $payment_group['id'];?>" disabled="disabled"/>
	<label for="name"><?php echo $eve->_('payment.group.name');?></label>
	<input 	id="name" type="text" name="name" value="<?php echo $payment_group['name'];?>"/>

	<label for="payment_info"><?php echo $eve->_('payment.group.payment.info');?></label>
	<textarea id="payment_info" class="htmleditor" name="payment_info">
	<?php echo $payment_group['payment_info'];?>
	</textarea>
	<label for="unverified_payment_info"><?php echo $eve->_('payment.group.unverified.payment.info');?></label>
	<textarea id="unverified_payment_info" class="htmleditor" name="unverified_payment_info">
	<?php echo $payment_group['unverified_payment_info'];?>
	</textarea>
	<label for="verified_payment_info"><?php echo $eve->_('payment.group.verified.payment.info');?></label>
	<textarea id="verified_payment_info" class="htmleditor" name="verified_payment_info">
	<?php echo $payment_group['verified_payment_info'];?>
	</textarea>

	<label for="state"><?php echo $eve->_('payment.group.state');?></label>
	<select id="state" name="state">
	<?php 
	foreach($evePaymentService->payment_group_states() as $state)
	{	
		echo "<option value=\"$state\"";
		if ($payment_group['state'] == $state) echo " selected=\"selected\"";
		echo ">".$eve->_('payment.group.state.'.$state)."</option>";
	}
	?>
	</select>
	</form>
	<?php

	$eve->output_html_footer();
}?>
