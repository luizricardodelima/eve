<?php
session_start();
require_once 'eve.class.php';
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
// Checking whether a valid screenname was passed
else if (!isset(($_GET['screenname'])) || !$eve->user_exists($_GET['screenname']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// If there's a valid session, and the current user is administrator, display the regular page.
else
{
	$evePaymentService = new EvePaymentService($eve);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Pagamentos", "payments.php", "Pagamento", null);

	?>
	<div class="section">Pagamento - <?php echo $_GET['screenname'];?></div>
	<?php

	$data = array();
	
	// Perform action if there is one
	if (!empty($_POST))
	{	
		// There is postdata. Validating and saving payment info.
		foreach ($_POST as $key => $value) $data[$key] = $value;

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
				case EvePaymentService::PAYMENT_ERROR :
					$eve->output_error_message("Erro ao salvar o pagamento de {$_GET['screenname']}.");
					break;
				case EvePaymentService::PAYMENT_SUCCESSFUL :
					$eve->output_success_message("Pagamento de {$_GET['screenname']} salvo com sucesso.");
					break;
				case EvePaymentService::PAYMENT_SUCCESSFUL_WITH_EMAIL_ALERT :
					$eve->output_success_message("Pagamento de {$_GET['screenname']} salvo com sucesso. E-mail de aviso enviado.");
					break;
			}
		}
	}
	else
	{
		// There is no postdata. Retrieving payment info, if any.
		$data = $evePaymentService->payment_get_by_user($_GET['screenname']);
		$date_value = date('Y-m-d');
		// TODO Creating new payment object goes to service
		if ($data === null) $data = array ('date' => $date_value, 'payment_method' => null, 'value_paid' => 0, 'value_received' => 0, 'note' => null);
	}
		
	?>
	<form action="<?php echo basename(__FILE__)."?screenname={$_GET['screenname']}";?>" method="post">
	<div class="dialog_panel">
	
	<label for="iptdate">Data</label>
	<input id="iptdate" type="date" name="date" value="<?php echo $data['date'];?>"/>

	<label for="payment_method">Método de pagamento</label>
	<input 	id="payment_method" type="text" name="payment_method" value="<?php echo $data['payment_method'];?>"/>

	<label for="value_paid">Valor pago</label>
	<input 	id="value_paid" name="value_paid" type="number" min="0" step="0.01" onkeyup="mirror_values()" onchange="mirror_values()" value="<?php echo $data['value_paid'];?>"/>

	<label for="value_received">Valor recebido</label>
	<input 	id="value_received" name="value_received" type="number" min="0" step="0.01" value="<?php echo $data['value_received'];?>"/>

	<label for="note">Observação</label>
	<textarea id="note" name="note" rows="5"><?php echo is_null($data['note']) ? '' : $data['note'];?></textarea>

	<table class="data_table">
	<th>Ítens adquiridos</th>
	<?php
	if (isset($data['id'])) foreach ($evePaymentService->payment_item_list($data['id']) as $item)
	{
		echo "<tr>";
		echo "<td>{$item['name']}</td>";
		echo "</tr>";
	}
	?>
	</table>

	<button type="submit" class="submit"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" class="altaction" onclick="window.location.href='payments.php'"><?php echo $eve->_('common.action.back');?></button>
	</div>	

	</form>	
	<script>
	function mirror_values()
	{
		document.getElementById("value_received_ipt").value = document.getElementById("value_paid_ipt").value;
	}
	</script>	
	<?php

	$eve->output_html_footer();
}?>
