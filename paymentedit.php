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
	// Converting empty select values to null
	if ($_POST['payment_group_id'] === '') $_POST['payment_group_id'] = null;

	var_dump($_POST);
	/* TODO -------------- MOST OF THIS CHUNK GOES TO SERVICE -------------------------------------

	// Validating input // TODO Validation should go to evepaymentservice
	$validation_errors = array();
	// Checking payment type
	if ($_POST['paymenttype_id'] == "null") $validation_errors[] = $eve->_('payment.validationerror.missing.paymenttype');
	// Checking value paid
	if (!is_numeric($_POST['value_paid'])) $validation_errors[] = $eve->_('payment.validationerror.invalid.valuepaid');
	// Checking value received is not valid
	if (!is_numeric($_POST['value_received'])) $validation_errors[] = $eve->_('payment.validationerror.invalid.valuereceived');
	// Checking date
	list($year, $month, $day) = explode('-', $_POST['date']);
	if ((false === strtotime($_POST['date'])) || (false === checkdate($month, $day, $year)))
		$validation_errors[] = $eve->_('payment.validationerror.invalid.date');
		
	if (count($validation_errors))
	{
		// There are validation errors
		$eve->output_error_list_message($validation_errors);
	}
	else 
	{
		// No errors on validation. Performing payment and displaying outcome
		$pmt = $evePaymentService->payment_register($_GET['screenname'], $_POST['paymenttype_id'], $_POST['date'], $_POST['note'], $_POST['value_paid'], $_POST['value_received']);
		switch ($pmt)
		{
			case EvePaymentService::PAYMENT_ERROR:
				$eve->output_error_message("Erro ao salvar o pagamento.");
				break;
			case EvePaymentService::PAYMENT_SUCCESSFUL :
				$eve->output_success_message("Pagamento salvo com sucesso.");
				break;
			case EvePaymentService::PAYMENT_SUCCESSFUL_WITH_EMAIL_ALERT :
				$eve->output_success_message("Pagamento salvo com sucesso. E-mail de aviso enviado.");
				break;
		}
	}
	*/
}
// If there's a valid session, and the current user is administrator, there is
// valid screenname or a valid payment id and there is no postdata, display the regular page 
else
{
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.payments') => "payments.php",
		"Editar pagamento" => null,
	]);		
	
	?>
	<!-- TODO Melhorar esse título-->
	<div class="section">Editar Pagamento</div>
	<?php
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);


	$payment = isset($_GET['id']) ? $evePaymentService->payment_get($_GET['id']) : null;
	if ($payment === null) 
	{
		// Creating new payment obect
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

	<label for="user_email">Usuário</label>
	<input 	id="user_email" type="text" disabled="disabled" value="<?php echo $payment['user_email'];?>"/>

	<label for="payment_group_id">Grupo de pagamento</label>
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

	<label for="items">Itens adquiridos <button type="button" onclick="alert('Implement')">Adicionar</button></label>
	<table 	id="items" class="data_table">
	<?php
	$curr_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
	if (isset($payment['id'])) foreach ($evePaymentService->payment_item_list($payment['id']) as $item)
	{
		echo "<tr>";
		echo "<td>{$item['name']}</td>";
		echo "<td>".$curr_formatter->format($item['value'])."</td>";
		echo "<td><button type=\"button\" onclick=\"alert('Implement')\"><img src=\"style/icons/delete.png\"></td>";
		echo "<input type=\"hidden\" name=\"options[]\" value=\"{$item['payment_option_id']}\"/>";
		echo "</tr>";
	}
	?>
	</table>

	<label for="date">Data</label>
	<input 	id="date" type="date" name="date" value="<?php echo $payment['date'];?>"/>

	<label for="payment_method">Método de pagamento</label>
	<input 	id="payment_method" type="text" name="payment_method" value="<?php echo $payment['payment_method'];?>"/>

	<label for="value_paid">Valor pago</label>
	<input 	id="value_paid" name="value_paid" type="number" min="0" step="0.01" value="<?php echo $payment['value_paid'];?>"/>

	<label for="value_received">Valor recebido</label>
	<input 	id="value_received" name="value_received" type="number" min="0" step="0.01" value="<?php echo $payment['value_received'];?>"/>

	<label for="note">Observação</label>
	<textarea id="note" name="note" rows="5"><?php echo $payment['note'];?></textarea>

	<button type="submit" class="submit"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" class="altaction" onclick="window.location.href='payments.php'"><?php echo $eve->_('common.action.back');?></button>
	</div>	

	</form>	
	<?php

	$eve->output_html_footer();
}?>
