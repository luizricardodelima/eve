<?php
session_start();
require_once '../eve.class.php';
require_once '../evepaymentservice.class.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	header("Content-Type: text/plain");	
	echo "Error: Invalid session";
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	header("Content-Type: text/plain");
	echo "Error: User does not have administrative permissions";
}
// Administrative privileges verification.
else if (!isset($_GET['payment_group_id']))
{
	header("Content-Type: text/plain");
	echo "Error: Get parameter 'payment_group_id' is mandatorya. Use blank value for null.";
}
// All the verifications were successful
else
{
	header("Content-Type: application/json; charset=utf-8");
	$payment_group_id = ($_GET['payment_group_id'] === '') ? null : $_GET['payment_group_id'];
	$curr_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
	$payment_options = $evePaymentService->payment_option_list(false, false, true, $payment_group_id);
	for ($i = 0; $i < sizeof($payment_options); $i++) {
		$payment_options[$i]['formatted_value'] = $curr_formatter->format($payment_options[$i]['value']);
	}
	echo json_encode($payment_options);
}
?>
