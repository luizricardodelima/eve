<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';

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
		$paymentOptions = $evePaymentService->payment_option_list(false, true);
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

		if (empty($mainOptions))
		{
			?>
			<div class="section">Efetuar pagamento</div> <!-- TODO G11n -->
			<div class="dialog_panel">
			<p>Não há opções de pagamento disponíveis</p> <!-- TODO G11n -->
			</div>
			<?php
		}
		else
		{
			?>
			<div class="section">Efetuar pagamento</div> <!-- TODO G11n -->
			<form name="payment" class="dialog_panel" method="post">
			<script>
			function changeValue(source)
			{
				if (source.checked)
				{
					if (source.name = 'payment_main')
					{
						var x = document.getElementsByName('payment_main');
						for (var i = 0; i < x.length; i++)
							x[i].parentElement.classList.remove('payment_selected');
						source.parentElement.classList.add('payment_selected');
					}
					else
					{
						source.parentElement.classList.add('payment_selected');
					}
				}
				else
				{
					source.parentElement.classList.remove('payment_selected')
				}
			}
			</script>
			<?php
			$formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
			foreach ($mainOptions as $mainOption)
			{
				?>
				<label for="main<?php echo $mainOption['id']; ?>" class="payment_option">
				<input  id="main<?php echo $mainOption['id']; ?>" type="radio" name="payment_main" value="<?php echo $mainOption['id']; ?>" class="payment_radio" onchange="changeValue(this)">
				<div class="payment_container">
				<div class="payment_name"><?php echo $mainOption['name']; ?></div>
				<div class="payment_description"><?php echo $mainOption['description']; ?></div>
				<div class="payment_value"><?php echo $formatter->format($mainOption['value']); ?></div>
				</div>
				</label>
				<?php
			}
			if(!empty($accessoryOptions)) echo "<div class=\"dialog_section\">Opcionais</div>"; // TODO G11N
			foreach ($accessoryOptions as $accessoryOption)
			{
				?>
				<label for="accessory<?php echo $accessoryOption['id']; ?>" class="payment_option">
				<input  id="accessory<?php echo $accessoryOption['id']; ?>" type="checkbox" name="payment_accessory[]" value="<?php echo $accessoryOption['id']; ?>" class="payment_radio">
				<div class="payment_optional_name"><?php echo $accessoryOption['name']; ?></div>
				<div class="payment_optional_description"><?php echo "&nbsp;&nbsp;".$accessoryOption['description']."&nbsp;&nbsp;"; ?></div>
				<div class="payment_optional_value"><?php echo $formatter->format($accessoryOption['value']); ?></div>
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
						echo "<button type=\"button\" class=\"submit\" onclick=\"payment_go('{$plugin}/{$plugin_info['paymentscreen']}')\">Pagar com {$plugin_info['name']}</button>"; //TODO G11N
				}
			}

			?>
			</form>
			<script>
			function payment_go(payment_screen_location)
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
	}

	?>
	<div class="section">Status do pagamento</div>
	<div class="dialog_panel">
	<?php

	if($payment === null)
	{
		echo "<p>{$eve->_('payment.message.payment.unverified')}</p>";
		echo $eve->getSetting('payment_information_unverified');
	}
	else
	{
		$date_formatter = new IntlDateFormatter($eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
		$money_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);

		echo "<p>{$eve->_('payment.message.payment.verified')}</p>";
		echo "<ul>";
		echo "<li>Data: {$date_formatter->format(strtotime($payment['date']))}</li>";
		echo "<li>Método de pagamento: {$payment['payment_method']}</li>";
		echo "<li>Valor pago: {$money_formatter->format($payment['value_paid'])}</li>";
		echo "</ul>";
		echo "<div class=\"dialog_section\">Ítens adquiridos</div>";
		echo "<table class=\"data_table\">";
		if (isset($payment['id'])) foreach ($evePaymentService->payment_item_list($payment['id']) as $item)
		{
			echo "<tr>";
			echo "<td>{$item['name']}</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo $eve->getSetting('payment_information_verified');
		echo "<p></p>";
	}

	?>
	<button type="button" class="submit" onclick="document.location.href='userarea.php';">
	<?php echo $eve->_('common.action.back');?></button>
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>
