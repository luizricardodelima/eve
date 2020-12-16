<?php
session_start();

require_once '../../eve.class.php';
require_once '../../eveuserservice.class.php';
require_once '../../evepaymentservice.class.php';
require_once 'lib/PagSeguroLibrary.php';

$eve = new Eve("../../");

// TODO G11N
// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("../../userarea.php?sessionexpired=1");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "../../userarea.php", "Pagamento via PagSeguro", null);
	?>
	<div class="section">Pagamento via PagSeguro</div>
	<div class="dialog_panel">
	<p id="p_message_content">Aguarde as instruções do PagSeguro...</p>
	<button id="btn_back" style="display:none;" class="submit" type="button" onclick="document.location.href='../../userarea.php'">Voltar</button>
	</div>	
	<?php

	$eveUserService = new EveUserService($eve);
	$evePaymentService = new EvePaymentService($eve);
	$user = $eveUserService->user_get($_SESSION['screenname']);
	$paymentOptions = $evePaymentService->payment_option_list(true, false);

	// Before proceeding with PagSeguro Request, checking if there are any items
	// with zero value. These items cannot enter in PagSeguro Payment Request and need
	// to be dealt with separately. A separate payment is created for them
	$exemption_cart = array();
	if (isset($_POST['payment_main']))
	{
		$mainOption = $paymentOptions[$_POST['payment_main']];
		if($mainOption['value'] == 0)
		{
			$exemption_cart[] = $_POST['payment_main'];
			unset($_POST['payment_main']);
		}
	}
	if (isset($_POST['payment_accessory']) && !empty($_POST['payment_accessory']))
	{
		foreach ($_POST['payment_accessory'] as $i => $accessory_option_id)
		{
			$accessoryOption = $paymentOptions[$accessory_option_id];
			if($accessoryOption['value'] == 0)
			{
				$exemption_cart[] = $accessory_option_id;
				unset($_POST['payment_accessory'][$i]);
			}
		}
	}
	if (!empty($exemption_cart))
	{
		// TODO G11N Isento
		$evePaymentService->payment_register($_SESSION['screenname'], "Isento", date("Y-m-d"), "Isento", 0, 0, $exemption_cart);
		?>
		<script>
		document.getElementById('p_message_content').innerHTML = "Isenções processadas com sucesso.";
		document.getElementById('btn_back').style.display = 'block';
		</script>
		<?php
	}

	// Creating PagSeguro PaymentRequest
	$paymentRequest = new PagSeguroPaymentRequest();	
	// Adding main option
	if (isset($_POST['payment_main']))
	{
		$mainOption = $paymentOptions[$_POST['payment_main']];
		// TODO #5 Create an option for configuring a prefix code for items on PagSeguro plugin
		$paymentRequest->addItem($mainOption['id'], $mainOption['name'], 1, number_format($mainOption['value'], 2));
	}

	// Adding accessory options
	if (isset($_POST['payment_accessory']) && !empty($_POST['payment_accessory']))
	{
		foreach ($_POST['payment_accessory'] as $accessory_option_id)
		{
			$accessoryOption = $paymentOptions[$accessory_option_id];
			// TODO #5 Create an option for configuring a prefix code for items on PagSeguro plugin
			$paymentRequest->addItem($accessoryOption['id'], $accessoryOption['name'], 1, number_format($accessoryOption['value'], 2));
		}
	}
	// Beginning of PagSeguro Code ------------------------------------------------------
	if (!empty($paymentRequest->getItems()))
	{
		// PagSeguro methods
		$environment = PagSeguroLibrary::$config->getEnvironment();
		$email = $user['email'];
		if ($environment == "sandbox") $email = str_replace("@","_A_",$user['email']).'@sandbox.pagseguro.com.br';
				
		$shippingType = PagSeguroShippingType::getCodeByType('NOT_SPECIFIED');  
		$paymentRequest->setShippingType($shippingType); 
		$paymentRequest->setSender($user['name'], $email);
		$paymentRequest->setCurrency('BRL');  
		$paymentRequest->setReference($user['email']);
		$paymentRequest->setRedirectUrl($eve->sysurl().'/userarea.php');
		$paymentRequest->addParameter('notificationURL', $eve->sysurl().'/plugins/pagseguro/notification.php'); 
		try
		{  
			$credentials = PagSeguroConfig::getAccountCredentials();
			$checkoutCode = $paymentRequest->register($credentials, true);  // true makes method return only code
			// Loading chosen script (sandbox / production)
			if ($environment == "sandbox")
				echo '<script type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js"></script>';
			else
				echo '<script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js"></script>';
			?>	
			<script> 
			var isOpenLightbox = PagSeguroLightbox({
				code: '<?php echo $checkoutCode;?>'
				}, {
				success : function(transactionCode) {
					document.getElementById('p_message_content').innerHTML = "Transação realizada com sucesso. Aguarde o processamento do pagamento.<br/><br/>Código da transação do PagSeguro: " + transactionCode;
					document.getElementById('btn_back').style.display = 'block';
				},
				abort : function() {
					document.getElementById('p_message_content').innerHTML = "Usuário cancelou a transação.<br/><br/>Para tentar novamente, clique em voltar e selecione Pagamento.";
					document.getElementById('btn_back').style.display = 'block';
				}
			});
			</script>
			<?php
		} 
		catch (Exception $e)
		{  
			?>
			<script>
			document.getElementById('p_message_content').innerHTML = "Ocorreu um erro. Procure o administrador do sistema caso ele persista: <?php echo $e->getMessage();?><br/><br/>Para tentar novamente, clique em voltar e selecione Pagamento.";
			document.getElementById('btn_back').style.display = 'block';
			</script>
			<?php
		}
	}
	// End of PagSeguro Code -------------------------------------------------------------

	$eve->output_html_footer();
}
?>