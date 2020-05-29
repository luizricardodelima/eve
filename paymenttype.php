<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);
$paymenttype = $evePaymentService->paymenttype_get($_GET['id']); //(sql injections handled in this function)

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
// Checking if payment type exists 
else if ($paymenttype == null)
{
	$eve->output_error_page('common.message.invalid.parameter'); 
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Tipos de pagamentos", "paymenttypes.php", "Tipo de pagamento (ID: {$paymenttype['id']})", null);
	
	?>
	<div class="section">
	<button type="button" onclick="document.forms['paymenttype_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>
	<?php

	$data = array();
	if (!empty($_POST))
	{
		// Space for future validation

		// If validation is ok, update $paymenttype with the new values
		foreach ($_POST as $column => $value) {$paymenttype[$column] = $value;}
		
		// Saving data
		$evePaymentService->paymenttype_update($paymenttype);
		$eve->output_success_message("Dados salvos com sucesso.");
	}

	?>
	<form action="<?php echo basename(__FILE__)."?id={$paymenttype['id']}";?>" id="paymenttype_form" method="post" class="dialog_panel">
	<label for="ipt_id">ID:</label>
	<input 	id="ipt_id" type="text" value="<?php echo $paymenttype['id'];?>" maxlength="255" disabled="disabled"/>
	<label for="ipt_name">Nome</label>
	<input 	id="ipt_name" type="text" name="name" value="<?php echo $paymenttype['name'];?>" maxlength="255"/>
	<label for="ipt_description">Descrição</label>
	<input 	id="ipt_description" type="text" name="description" value="<?php echo $paymenttype['description'];?>" maxlength="255"/>
	</form>
	<?php

	$eve->output_html_footer();
}?>
