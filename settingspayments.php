<?php
session_start();
require_once 'eve.class.php';
require_once 'evesettingsservice.class.php';

$eve = new Eve();
$eveSettingsService = new EveSettingsService($eve);

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
else if (!empty($_POST))
{
	// There are settings as POST variables to be saved.
	$eveSettingsService->settings_update($_POST);
	$eve->output_redirect_page(basename(__FILE__)."?saved=1");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Pagamentos", null);	
	$eve->output_wysiwig_editor_code();
	

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	$settings = $eveSettingsService->settings_get
	(
		'payment_closed', 'payment_information_unverified', 'payment_information_verified', 
		'email_snd_payment', 'email_sbj_payment', 'email_msg_payment'
	);

	?>
	<script>
	function payment_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$paymenttype_name - Tipo de pagamento'
				+'\n$paymenttype_description - Descrição do tipo de pagamento'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'); //TODO: Inserir esse placeholder no código de pagamentos!
	}
	</script>

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	
	<div class="section">Informações para o usuário</div>
	<table style="width: 100%">
	<tr><td><input type="hidden" name="payment_closed" value="0"/> <input type="checkbox" name="payment_closed" value="1" <?php if ($settings['payment_closed']) echo "checked=\"checked\"";?> /> Pagamento desabilitado</td></tr>
	<tr><td>Informa&ccedil;&otilde;es para pagamento não verificado</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="payment_information_unverified"><?php echo $settings['payment_information_unverified'];?></textarea></td></tr>
	<tr><td>Informa&ccedil;&otilde;es para pagamento verificado</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="payment_information_verified"><?php echo $settings['payment_information_verified'];?></textarea></td></tr>
	</table>

	<div class="section">Email de aviso <button type="button" onclick="payment_email_help()">?</button></div>
	<div class="dialog_panel">
	<label for="email_snd_payment">
	<input  id="email_snd_payment" type="checkbox" name="email_snd_payment" value="1" <?php if ($settings['email_snd_payment']) echo "checked=\"checked\"";?> /><input type="hidden" name="email_snd_payment" value="0"/>Enviar e-mail ao atualizar pagamento do usuário</label>
	<label for="email_sbj_payment">Assunto</label>
	<input  id="email_sbj_payment" type="text" name="email_sbj_payment" value="<?php echo $settings['email_sbj_payment'];?>"/>
	<label for="email_msg_payment">Mensagem</label>
	<textarea id="email_msg_payment" class="htmleditor" rows="6" name="email_msg_payment"><?php echo $settings['email_msg_payment'];?></textarea>
	</div>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
