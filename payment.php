<?php
session_start();
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
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.payment') => null
	]);

	// Retrieving payment info from user
	$payment = $evePaymentService->payment_get_by_user($_SESSION['screenname']);

	if ($payment === null)
	{
		// No payment found for the user, displaying the payment options
		$paymentOptions = $evePaymentService->payment_option_list();
		$mainOptions = array();
		$accessoryOptions = array();

		// Organizing the current options into main and accessory
		// TODO Create a method in service to do that, including the time verification
		foreach ($paymentOptions as $paymentOption)
		{
			if (!$paymentOption['admin_only'] && $paymentOption['type'] == 'main')
				$mainOptions[] = $paymentOption;
			if (!$paymentOption['admin_only'] && $paymentOption['type'] == 'accessory')
				$accessoryOptions[] = $paymentOption;
		}

		$formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);

		echo "<div class=\"section\">Efetuar pagamento</div>"; // TODO G11n
		echo "<form name=\"payment\" class=\"dialog_panel\" method=\"post\">";
		foreach ($mainOptions as $mainOption)
		{
			?>
			<label for="main<?php echo $mainOption['id']; ?>">
			<input  id="main<?php echo $mainOption['id']; ?>" type="radio" name="payment_main" value="<?php echo $mainOption['id']; ?>">
			<div class="payment_name"><?php echo $mainOption['name']; ?></div>
			<div class="payment_description"><?php echo $mainOption['description']; ?></div>
			<div class="payment_value"><?php echo $formatter->format($mainOption['value']); ?></div>
			</label>
			<?php
		}
		if(!empty($accessoryOptions)) echo "<p>Opcionais</p>"; // TODO G11N
		foreach ($accessoryOptions as $accessoryOption)
		{
			?>
			<label for="accessory<?php echo $accessoryOption['id']; ?>">
			<input  id="accessory<?php echo $accessoryOption['id']; ?>" type="checkbox" name="payment_accessory[]" value="<?php echo $accessoryOption['id']; ?>">
			<div class="payment_name"><?php echo $accessoryOption['name']; ?></div>
			<div class="payment_description"><?php echo $accessoryOption['description']; ?></div>
			<div class="payment_value"><?php echo $formatter->format($accessoryOption['value']); ?></div>
			</label>
			<?php
		}

		$plugins = glob('plugins/*' , GLOB_ONLYDIR);
		if (!empty($plugins))
		{
			// TODO The payments plugins need not only be installed in the system to be
			// listed, but also need to be activated or deactivated by the administrator.
			// This also needs to show an error message if no payment plugins are found.
			foreach ($plugins as $plugin)
			{
				$plugin_info = parse_ini_file("$plugin/plugin.ini");
				if ($plugin_info['type'] == 'payment')
					echo "<button type=\"button\" class=\"submit\" onclick=\"perform_payment('{$plugin}/{$plugin_info['paymentscreen']}')\">Pagar com {$plugin_info['name']}</button>"; //TODO G11N
			}
		}

		echo "</form>";
		?>
		<script>
		function perform_payment(payment_screen_location)
		{
			if ($('input[name=payment_main]:checked').length == 0)
			{
				alert('É necessário selecionar uma das opções de pagamento para prosseguir'); // TODO G11N
			}
			else
			{
				document.forms['payment'].action = payment_screen_location;
				document.forms['payment'].submit();
			}
		}
		</script>
		<?php
	}

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
	<div class="dialog_panel">
		<p><strong><?php echo $paymenttype_name;?></strong> - <?php echo $paymenttype_desc;?></p>
	</div>
	<div class="section">Informações</div>
	<div class="dialog_panel">
	<?php

	if (!$payment && !$eve->getSetting('payment_closed'))
		echo $eve->getSetting('payment_information_unverified');
	else
		echo $eve->getSetting('payment_information_verified');

	?>
	<button type="button" class="submit" onclick="document.location.href='userarea.php';">Voltar</button>
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>
