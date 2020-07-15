<?php
require_once '../../eve.class.php';
require_once '../../evepaymentservice.php';
require_once 'lib/PagSeguroLibrary.php';

error_reporting(E_ERROR | E_PARSE);
header("Content-Type: text/plain");

function log_message($message)
{
	$date = date("Y_m_d");
	$date_time = date("c");
	$log_line = $date_time. " " . $message ."\n";
	file_put_contents("log/$date.txt", $log_line, FILE_APPEND);
	echo $log_line;
}

try
{  
	log_message("Start of PagSeguro notification - {$_POST['notificationCode']}");
	$credentials = PagSeguroConfig::getAccountCredentials();  
	$response = PagSeguroNotificationService::checkTransaction($credentials, $_POST['notificationCode']);
	log_message(">> Reference: {$response->getReference()} | Status: {$response->getStatus()->getValue()} {$response->getStatus()->getTypeFromValue()}");

	if ($response->getStatus()->getTypeFromValue() == 'PAID')
	{
		$items = array();
		$value_paid = 0;
		$value_received = $response->getNetAmount();
		
		foreach($response->getItems() as $item)
		{
			$items[] = $item->getId(); 
			$value_paid += ($item->getQuantity() * $item->getAmount());
		}

		$eve = new Eve();
		$evePaymentService = new EvePaymentService($eve);
		$pmt = $evePaymentService->payment_register($response->getReference(), "PagSeguro", date("Y-m-d"), $response->getCode(), $value_paid, $value_received, $items);
		// TODO #6 Create an option for configuring a different payment method name on PagSeguro plugin
		
		log_message(">> EVE Result: $pmt");
		log_message(">> VALUE RECEIVED: $value_received");
		log_message("End of PagSeguro notification\n");
	}
}
catch (Exception $e)
{  
	log_message(">> " . $e->getMessage());
	log_message("End of PagSeguro notification\n");
}

?>