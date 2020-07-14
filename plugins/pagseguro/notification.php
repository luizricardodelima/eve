<?php
require_once '../../eve.class.php';
require_once '../../evepaymentservice.php';
require_once 'lib/PagSeguroLibrary.php';

$eve = new Eve();
$date = date("Y_m_d");
$date_time = date("c");

var_dump($_POST);

$log_line_1 = "$date_time Received notification - {$_POST['notificationCode']}\n";
file_put_contents("log/$date.txt", $log_line_1, FILE_APPEND);
echo $log_line_1;

try
{  
	$credentials = PagSeguroConfig::getAccountCredentials();  
	$response = PagSeguroNotificationService::checkTransaction($credentials, $_POST['notificationCode']);

	$log_line_2 = ">>> PAGSEGURO Reference: {$response->getReference()} | Status: {$response->getStatus()->getValue()} {$response->getStatus()->getTypeFromValue()}\n";
	file_put_contents("log/$date.txt", $log_line_2, FILE_APPEND);
	echo $log_line_2;

	if ($response->getStatus()->getTypeFromValue() == 'PAID')
	{
		$value_paid = 0;
		$value_received = $response->getNetAmount();
		$items = array();

		foreach($response->getItems() as $item)
		{
			 $value_paid += ($item->getQuantity() * $item->getAmount());
			 $items[] = $item->getId();
		}

		$evePaymentService = new EvePaymentService($eve);
		// TODO #6 Create an option for configuring a different payment method name on PagSeguro plugin
		$pmt = $evePaymentService->perform_payment($response->getReference(), "PagSeguro", date("Y_m_d"), $response->getCode(), $value_paid, $value_received);

		$log_line_3 = ">>> EVE Result: $pmt\n";
		file_put_contents("log/$date.txt", $log_line_3, FILE_APPEND);
		echo $log_line_3;
		
		$log_line_4 = ">>> VALUE RECEIVED: $value_received\n";
		file_put_contents("log/$date.txt", $log_line_4, FILE_APPEND);
		echo $log_line_4;

		$log_line_5 = "\n";
		file_put_contents("log/$date.txt", $log_line_5, FILE_APPEND);
		echo $log_line_5;
	}
}
catch (PagSeguroServiceException $e)
{  
	die($e->getMessage());  
}

?>
