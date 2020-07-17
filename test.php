<?php
session_start();
$_SESSION['screenname'] = "luizricardodelima9@gmail.com";
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);


$create_result = $evePaymentService->payment_register('luizricardodelima9@gmail.com', 'PMT METHOD X', date("Y-m-d"), "ORIGINAL NOTE", 99, 88, [1]);
var_dump($create_result);
$find_result = $evePaymentService->payment_get_id('luizricardodelima9@gmail.com');
var_dump($find_result);
/*
$delete_result = $evePaymentService->payment_delete($find_result);
var_dump($delete_result);

$date = date("Y-m-d");
$date_formatter = new IntlDateFormatter($eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
echo "1. ". $date;
echo "\n";
echo "2. ". $date_formatter->format($date);
echo "\n";
echo "3. ". $date_formatter->format(strtotime($date));
echo "\n";
*/
?>