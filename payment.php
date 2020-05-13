<?php
session_start();
require_once 'lib/filechecker/filechecker.php';
require_once 'eve.class.php';
require_once 'evepaymentservice.php';

$eve = new Eve();

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if($eve->getSetting('payment_closed'))
{
	$eve->output_error_page('common.message.no.permission');
}
else
{
	$evePaymentService = new EvePaymentService($eve);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Pagamento", null);

	// Retrieving payment info from user
	$payment = $evePaymentService->payment_get_by_user($_SESSION['screenname']);
	
	// Default information - Unverified payment
	$paymenttype_name = $eve->_('paymenttype.name.null');
	$paymenttype_desc = $eve->_('paymenttype.description.null');
	
	// Loading payment information if there is payment information for the current user
	if ($payment)
	{
		$paymenttype = $evePaymentService->paymenttype_get($payment['paymenttype_id']);
		$paymenttype_name = $paymenttype['name'];
		$paymenttype_desc = $paymenttype['description'];
	}

	?>
	<div class="section">Status atual do pagamento</div>
	<div class="user_dialog_panel">
		<p><strong><?php echo $paymenttype_name;?></strong> - <?php echo $paymenttype_desc;?></p>
	</div>
	<div class="section">Informações</div>
	<div class="user_dialog_panel">
	<?php

	if (!$payment && !$eve->getSetting('payment_closed'))
		echo $eve->getSetting('payment_information_unverified');
	else
		echo $eve->getSetting('payment_information_verified');

	?>
	<button type="button" class="submit" onclick="document.location.href='userarea.php';">Voltar</button>	
	<p></p>	
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>
