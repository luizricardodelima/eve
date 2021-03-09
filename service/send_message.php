<?php
session_start();
require_once '../eve.class.php';
require_once '../evemail.class.php';

$eve = new Eve();
$eveMail = new EveMail($eve);

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
// All the verifications were successful
else
{
	// TODO: Verify if address is correct, and if the three parameters are given
	header("Content-Type: text/plain; charset=utf-8");
	if ($eveMail->send_mail($_POST['address'], null, $_POST['subject'], $_POST['message']))
		echo "ok";
	else
	{
		echo "error";
	}
}
?>
