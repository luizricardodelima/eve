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
else if (!$eve->user_exists($_GET['screenname']))
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
	<div class="section">Pagamento para <?php echo $_GET['screenname'];?></div>
	<?php

	$data = array();
	
	// Perform action if there is one
	if (sizeof($_POST) > 0)
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
			$pmt = $evePaymentService->perform_payment($_GET['screenname'], $_POST['paymenttype_id'], $_POST['date'], $_POST['note'], $_POST['value_paid'], $_POST['value_received']);
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
	}
		
	?>
	<form action="<?php echo basename(__FILE__)."?screenname={$_GET['screenname']}";?>" method="post">
	<div class="user_dialog_panel">
	<p></p>
	<?php 
		$date_value = "";
		if (isset($data['date'])) $date_value = $data['date'];
		else $date_value = date('Y-m-d');
	?>	
	<label for="iptdate">Data</label>
	<input id="iptdate" type="date" name="date" value="<?php echo $date_value;?>"/>

	<label for="selpaymenttype">Tipo de pagamento</label>
	<select id="selpaymenttype" class="user_form" name="paymenttype_id">
	<option value="null"><?php echo $eve->_('common.select.null');?></option>
	<?php
	foreach ($evePaymentService->paymenttype_list() as $paymenttype)
	{	
		echo "<option value=\"{$paymenttype['id']}\"";
		if ($data['paymenttype_id'] == $paymenttype['id']) echo " selected=\"selected\"";
		echo ">{$paymenttype['name']} </option>";
	}
	?>
	</select>

	<label for="value_paid_ipt">Valor pago</label>
	<input id="value_paid_ipt" type="number" name="value_paid" min="0" step="0.01" onkeyup="mirror_values()" onchange="mirror_values()" value="<?php echo $data['value_paid'];?>"/>

	<label for="value_received_ipt">Valor recebido</label>
	<input id="value_received_ipt" type="number" name="value_received" min="0" step="0.01" value="<?php echo $data['value_received'];?>"/>

	<label for="iptnote">Observação</label>
	<input id="iptvaluereceived" type="text" name="note" value="<?php echo $data['note'];?>"/>

	<button type="submit" class="submit">Salvar</button>
	<p></p>
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
