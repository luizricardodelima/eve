<?php
require_once '../../eve.class.php';
require_once '../../evepaymentservice.php';
require_once 'lib/PagSeguroLibrary.php';

$eve = new Eve();
$date = date("Y_m_d");
$date_time = date("c");

var_dump($_POST);

$log_line_1 = "$date_time Received notification - {$_POST['notificationCode']}\n";
file_put_contents("logs/log_$date.txt", $log_line_1, FILE_APPEND);
echo $log_line_1;

try
{  
	$credentials = PagSeguroConfig::getAccountCredentials();  
	$response = PagSeguroNotificationService::checkTransaction($credentials, $_POST['notificationCode']);

	$log_line_2 = ">>> PAGSEGURO Reference: {$response->getReference()} | Status: {$response->getStatus()->getValue()} {$response->getStatus()->getTypeFromValue()}\n";
	file_put_contents("logs/log_$date.txt", $log_line_2, FILE_APPEND);
	echo $log_line_2;

	if ($response->getStatus()->getTypeFromValue() == 'PAID')
	{
		$transaction_value = 0;
		$value_received = $response->getNetAmount();
		foreach($response->getItems() as $item) $transaction_value += ($item->getQuantity() * $item->getAmount());
		
		$evePaymentService = new EvePaymentService($eve);
		$pmt = $evePaymentService->perform_payment($response->getReference(), $eve->getSetting('plugin_pagseguro_paymenttypeid'), date("Y_m_d"), $response->getCode(), $transaction_value, $value_received);

		$log_line_3 = ">>> EVE Result: $pmt\n";
		file_put_contents("logs/log_$date.txt", $log_line_3, FILE_APPEND);
		echo $log_line_3;
		
		$log_line_4 = ">>> VALUE RECEIVED: $value_received\n";
		file_put_contents("logs/log_$date.txt", $log_line_4, FILE_APPEND);
		echo $log_line_4;

		$log_line_5 = "\n";
		file_put_contents("logs/log_$date.txt", $log_line_5, FILE_APPEND);
		echo $log_line_4;
	}
	// TODO Delete transaction on other statuses?
}
catch (PagSeguroServiceException $e)
{  
	die($e->getMessage());  
}

?>
