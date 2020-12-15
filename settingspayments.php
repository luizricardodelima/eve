<?php
session_start();
require_once 'eve.class.php';
require_once 'evesettingsservice.class.php';

//TODO G11N
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
	$msg = $eveSettingsService->settings_update($_POST);
	$eve->output_redirect_page(basename(__FILE__)."?msg=$msg");
}
else
{
	$eve->output_html_header(['wysiwyg-editor']);
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.settings') => "settings.php",
		$eve->_('settings.payments') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'payment_closed', 'payment_information_unverified', 'payment_information_verified', 
		'email_snd_payment_update', 'email_sbj_payment_update', 'email_msg_payment_update',
		'email_sbj_payment_delete', 'email_msg_payment_delete'
	);

	?>
	<script>
	function payment_delete_email_help(){
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema');
	}

	function payment_update_email_help(){
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'
				+'\n$payment_date - Data do pagamento'
				+'\n$payment_method - Método de pagamento'
				+'\n$payment_value_paid - Valor pago');
	}
	</script>

	<div class="section"><?php echo $eve->_('settings.payments');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">

	<div class="dialog_panel">
	<div class="dialog_section">Informações para o usuário</div>
	<label for="payment_information_unverified">Informa&ccedil;&otilde;es para pagamento não verificado</label>
	<textarea id="payment_information_unverified" class="htmleditor" rows="6" cols="50" name="payment_information_unverified">
	<?php echo $settings['payment_information_unverified'];?>
	</textarea>
	<label for="payment_information_verified">Informa&ccedil;&otilde;es para pagamento verificado</label>
	<textarea id="payment_information_verified" class="htmleditor" rows="6" cols="50" name="payment_information_verified">
	<?php echo $settings['payment_information_verified'];?>
	</textarea>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">E-mail de aviso de atualização <button type="button" onclick="payment_update_email_help()">?</button></div>
	<label for="email_snd_payment_update">
	<input  id="email_snd_payment_update" type="checkbox" name="email_snd_payment_update" value="1" <?php if ($settings['email_snd_payment_update']) echo "checked=\"checked\"";?> /><input type="hidden" name="email_snd_payment_update" value="0"/>Enviar e-mail ao atualizar pagamento do usuário</label>
	<label for="email_sbj_payment_update">Assunto</label>
	<input  id="email_sbj_payment_update" type="text" name="email_sbj_payment_update" value="<?php echo $settings['email_sbj_payment_update'];?>"/>
	<label for="email_msg_payment_update">Mensagem</label>
	<textarea id="email_msg_payment_update" class="htmleditor" rows="6" name="email_msg_payment_update"><?php echo $settings['email_msg_payment_update'];?></textarea>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">E-mail de aviso de remoção <button type="button" onclick="payment_delete_email_help()">?</button></div>
	<label for="email_sbj_payment_delete">Assunto</label>
	<input  id="email_sbj_payment_delete" type="text" name="email_sbj_payment_delete" value="<?php echo $settings['email_sbj_payment_delete'];?>"/>
	<label for="email_msg_payment_delete">Mensagem</label>
	<textarea id="email_msg_payment_delete" class="htmleditor" rows="6" name="email_msg_payment_delete"><?php echo $settings['email_msg_payment_delete'];?></textarea>
	</div>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
