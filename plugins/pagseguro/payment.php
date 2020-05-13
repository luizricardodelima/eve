<?php
session_start();

require_once '../../eve.class.php';
require_once '../../eveuserservice.class.php';
require_once 'lib/PagSeguroLibrary.php';

$eve = new Eve("../../");
$eveUserServices = new EveUserServices($eve);

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
	<div class="user_dialog_panel">
	<p id="p_message_content"><br/><br/><br/>Aguarde as instruções do PagSeguro...</p>
	<button id="btn_back" style="display:none;" class="submit" type="button" onclick="document.location.href='../../userarea.php'">Voltar</button>
	<p></p>
	</div>	
	<?php
	$paymentRequest = new PagSeguroPaymentRequest();	

	$user = $eveUserServices->get_user($_SESSION['screenname']);
	$paymentInformation = json_decode($eve->getSetting('plugin_pagseguro_paymentinformation'));
	foreach ($paymentInformation as $paymentInformationItem) 
	{
		if (strtotime($paymentInformationItem->start_date) < time() && time() <= strtotime($paymentInformationItem->end_date))
		{
			// Analyzing payment item definition only if it meets date constraints
			switch ($paymentInformationItem->type)
			{
				case 'user_custom_flag':
					$field = "customflag{$paymentInformationItem->specification}";
					if ($user[$field] == true)
						$paymentRequest->addItem($paymentInformationItem->code, $paymentInformationItem->description, 1, number_format($paymentInformationItem->price, 2));
					break;
				case 'user_category':
					if ($paymentInformationItem->specification == $user['category_id'])
						$paymentRequest->addItem($paymentInformationItem->code, $paymentInformationItem->description, 1, number_format($paymentInformationItem->price, 2));
					break;
			}
		}
	}
	$items = $paymentRequest->getItems();
	if (empty($items))
	{
		// There are no purchase items for this user. Nothing to do but showing an error message.	
		?>
		<script>document.getElementById('p_message_content').innerHTML = "Não há itens selecionados ou disponíveis para este usuário. Retorne à tela de dados do usuário e verifique os dados selecionadas.";</script>
		<?php
	}
	else
	{	
		// There are purchase items for this user, they were added to a PaymentRequest. Proceeding the payment.
		$environment = PagSeguroLibrary::$config->getEnvironment();
		$email = $user['email'];
		if ($environment == "sandbox") {
			$email = str_replace("@","_A_",$user['email']).'@sandbox.pagseguro.com.br';
		}
				
		$shippingType = PagSeguroShippingType::getCodeByType('NOT_SPECIFIED');  
		$paymentRequest->setShippingType($shippingType); 
		$paymentRequest->setSender($user['name'], $email);//, '', '', 'CPF', ''); 
		$paymentRequest->setCurrency("BRL");  
		$paymentRequest->setReference($user['email']);
		$paymentRequest->setRedirectUrl($eve->url().'userarea.php');
		$paymentRequest->addParameter('notificationURL', $eve->url().'notification.php'); 
		try
		{  
			$credentials = PagSeguroConfig::getAccountCredentials();
			$checkoutCode = $paymentRequest->register($credentials, true);  // true makes method return only code
		} catch (PagSeguroServiceException $e)
		{  
			die($e->getMessage());  
		}  

		// Choosing correct script (sandbox / production)
		if ($environment == "sandbox")
			echo '<script type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js"></script>';
		else
			echo '<script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js"></script>';
		?>	
		<script> 
		var isOpenLightbox = PagSeguroLightbox({
			code: <?php echo "'$checkoutCode'";?>
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
	$eve->output_html_footer();
}?>
