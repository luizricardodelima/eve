<?php
session_start();

require_once '../../eve.class.php';
require_once '../../eveuserservice.class.php';
require_once '../../evepaymentservice.class.php';

$eve = new Eve();

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("../../userarea.php?sessionexpired=1");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "../../userarea.php",
		"Pagamento via PayPal" => null
	]);
	?>
	<div class="section">Pagamento via PayPal</div>
	<div class="dialog_panel">
	<pre><?php /* var_dump($_POST); */ ?></pre> <!--TODO REMOVE -->
	<div id="paypal-button"></div>
	<button id="btn_back" style="display:block;" class="submit" type="button" onclick="document.location.href='../../userarea.php'">Voltar</button>
	</div>	
	<?php

	// Loading system data
	$eveUserService = new EveUserService($eve);
	$evePaymentService = new EvePaymentService($eve);
	$user = $eveUserService->user_get($_SESSION['screenname']);
	$paymentOptions = $evePaymentService->payment_option_list(true, false);
	?>

	<!-- Start of PayPal code ---------------------------------------------------------->
	
	<script src="https://www.paypalobjects.com/api/checkout.js"></script>
	<script>
	paypal.Button.render({
		// Configure environment
		env: '<?php echo $eve->getSetting('plugin_paypal_environment'); ?>',
		client: {
		sandbox: '<?php echo $eve->getSetting('plugin_paypal_sandbox_client_id'); ?>',
		production: '<?php echo $eve->getSetting('plugin_paypal_production_client_id'); ?>'
		},
		// Customize button (optional)
		locale: '<?php echo $eve->getSetting('system_locale'); ?>',
		style: {
		size: 'responsive',
		color: 'gold',
		shape: 'pill',
		},

		// Enable Pay Now checkout flow (optional)
		commit: true,

		// Set up a payment
		payment: function(data, actions) {
		return actions.payment.create({
			transactions: [{
			amount: {
				total: '0.01',
				currency: 'BRL'
			}
			}]
		});
		},
		// Execute the payment
		onAuthorize: function(data, actions) {
		return actions.payment.execute().then(function() {
			// Show a confirmation message to the buyer
			window.alert('Thank you for your purchase!');
		});
		}
	}, '#paypal-button');
	</script>
	<!-- End of PayPal code ------------------------------------------------------------>

	<?php
	$eve->output_html_footer();
}
?>