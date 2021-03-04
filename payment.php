<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';
require_once 'evesettingsservice.class.php';

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

	$page_title = ($payment_group === null) ? $eve->_('payment') : $eve->_('payment') . ' - ' . $payment_group['name'];
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$page_title => null
	]);

	$payments = $evePaymentService->payment_list_for_user($_SESSION['screenname'], $payment_group_id);
	$paymentOptions = $evePaymentService->payment_option_list(true, true, true, $payment_group_id);

	// Filtering the payment options that were already purchased in previous payments. They cannot be available
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
		// If no payments and no avaliable payment options, it means that the
		// Payment group has no payment options, which means that there wouldn't be
		// a page with such id being displayed. Therefore we don't need to worry with that
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
		<div class="section"><?php echo $page_title;?></div>
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
		if(!empty($accessoryOptions)) 
			echo "<div class=\"dialog_section\">{$eve->_('payment.acessory.options')}</div>";
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
			foreach ($plugins as $plugin)
			{
				$plugin_info = parse_ini_file("$plugin/plugin.ini");
				if ($plugin_info['type'] == 'payment' && $eve->getSetting($plugin_info['activationsetting']))
				{
					$button_label = $eve->_('payment.button.plugin', ['<PLUGINNAME>' => $plugin_info['name']]);
					echo "<button type=\"button\" class=\"submit\" onclick=\"payment_go('{$plugin}/{$plugin_info['paymentscreen']}')\">$button_label</button>";			
				}
			}
		}

		?>
		</form>
		<script>
		function payment_go(payment_screen_location)
		{
			if ($('input[name=payment_main]:checked').length + $('input[type=checkbox]:checked').length  == 0)
			{
				alert('<?php echo $eve->_('payment.message.please.select.one.option');?>');
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
	<div class="section"><?php echo $eve->_('payment.status');?></div>
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
		foreach($payments as $payment)
		{
			echo $evePaymentService->payment_output_details_for_user($payment['id']);
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
