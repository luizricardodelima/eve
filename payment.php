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
else
{
	$evePaymentService = new EvePaymentService($eve);
	$payment_group = isset($_GET['group']) ? $evePaymentService->payment_group_get($_GET['group']) : null;
	$payment_group_id = ($payment_group === null) ? null : $payment_group['id'];

	$navigation_string = ($payment_group === null) ? $eve->_('userarea.option.payment') : $eve->_('userarea.option.payment') . ' - ' . $payment_group['name'];
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$navigation_string => null
	]);

	$payments = $evePaymentService->payment_list_for_user($_SESSION['screenname'], $payment_group_id);
	$paymentOptions = $evePaymentService->payment_option_list(true, true);
	// TODO: payment_option_list return all the options of all the groups. The filtering of the options for
	// this group is being done in the front_end code, but it should be better handled in the service
	foreach ($paymentOptions as $i => $payment_option)
		if ($payment_option['payment_group_id'] != $payment_group_id) unset($paymentOptions[$i]);

	// Filtering the payment options that were already purchased in previous payments. They are not available
	// for a second payment.
	$main_options_available = true;
	foreach ($payments as $payment)
	{
		foreach ($evePaymentService->payment_item_list($payment['id']) as $payment_item)
			if($payment_item['type'] == 'accessory')
				unset($paymentOptions[$payment_item['payment_option_id']]);
			else if($payment_item['type'] == 'main')
				$main_options_available = false;
	}

	// Organizing the current options into main and accessory
	// TODO Create a method in service to do that, including the time verification
	$mainOptions = array();
	$accessoryOptions = array();
	foreach ($paymentOptions as $paymentOption)
	{
		if (!$paymentOption['admin_only'] && $paymentOption['type'] == 'main' && $main_options_available)
			$mainOptions[] = $paymentOption;
		if (!$paymentOption['admin_only'] && $paymentOption['type'] == 'accessory')
			$accessoryOptions[] = $paymentOption;
	}

	if (empty($payments) && (empty($mainOptions) && empty($accessoryOptions)))
	{
		// If no payments and no avaliable payment options, display a message to user
		?>
		<div class="section">Efetuar pagamento</div> <!-- TODO G11n -->
		<div class="dialog_panel">
		<p>Não há opções de pagamento disponíveis</p> <!-- TODO G11n -->
		</div>
		<?php
	}
	else if (!empty($payments) && (empty($mainOptions) && empty($accessoryOptions)))
	{
		// If there are payments but no available payment options, it means that the user
		// already performed all the possible payments for the group. There is no need to
		// show any message on the screen
	}
	else // !empty($mainOptions) || !empty($accessoryOptions)
	{
		// If there are payment options available, they have to be displayed
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
		if ($payment_group !== null) echo $payment_group['payment_info'];
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
			if ($('input[name=payment_main]:checked').length + $('input[type=checkbox]:checked').length  == 0)
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

	?>
	<div class="section">Status do pagamento</div><!-- TODO G11N -->
	<div class="dialog_panel">
	<?php

	if(empty($payments))
	{
		if ($payment_group !== null) echo $payment_group['unverified_payment_info'];
		else echo "<p>{$eve->_('payment.message.payment.unverified')}</p>";
	}
	else
	{
		if ($payment_group !== null) echo $payment_group['verified_payment_info'];
		else echo "<p>{$eve->_('payment.message.payment.verified')}</p>";

		$date_formatter = new IntlDateFormatter($eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
		$money_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);

		foreach($payments as $payment)
		{
			// TODO G11N
			echo "<div class=\"dialog_section\">Dados do pagamento - ID {$payment['id']}</div>";
			echo "<table class=\"data_table\">";
			echo "<tr><td>Data</td><td>{$date_formatter->format(strtotime($payment['date']))}</td></tr>";
			echo "<tr><td>Método de pagamento</td><td>{$payment['payment_method']}</td></tr>";
			echo "<tr><td>Valor pago</td><td>{$money_formatter->format($payment['value_paid'])}</td></tr>";
			echo "</table>";
			echo "<div class=\"dialog_section\">Ítens adquiridos</div>";
			echo "<table class=\"data_table\">";
			if (isset($payment['id'])) foreach ($evePaymentService->payment_item_list($payment['id']) as $item)
			{
				echo "<tr>";
				echo "<td>{$item['name']}</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<p></p>";
		}
	}

	?>
	<button type="button" class="submit" onclick="document.location.href='userarea.php';">
	<?php echo $eve->_('common.action.back');?></button>
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>
