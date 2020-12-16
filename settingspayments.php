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
// There are settings as POST variables to be saved.
else if (!empty($_POST))
{
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
		'email_snd_payment_update', 'email_sbj_payment_update', 'email_msg_payment_update',
		'email_sbj_payment_delete', 'email_msg_payment_delete'
	);

	?>
	<div class="section"><?php echo $eve->_('settings.payments');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">

	<!-- Help viewer -->
	<div id="help_viewer" class="viewer"><div class="viewer_container">
	<button class="close_button" type="button" onclick="document.getElementById('help_viewer').style.display = 'none';"> X </button>
	<div class="viewer_content" style=""><?php echo $eve->_('settings.payments.email.help'); ?></div>
	</div></div>

	<div class="dialog_panel">
	<div class="dialog_section">
	<label for="email_snd_payment_update"><input type="hidden" name="email_snd_payment_update" value="0"/>
	<input  id="email_snd_payment_update" type="checkbox" name="email_snd_payment_update" value="1" <?php if ($settings['email_snd_payment_update']) echo "checked=\"checked\"";?> />Enviar e-mail ao atualizar ou remover pagamento do usuário</label>
	<button type="button" onclick="document.getElementById('help_viewer').style.display = 'block';">?</button></div>
	<label for="email_sbj_payment_update">Email de atualização - Assunto</label>
	<input  id="email_sbj_payment_update" type="text" name="email_sbj_payment_update" value="<?php echo $settings['email_sbj_payment_update'];?>"/>
	<label for="email_msg_payment_update">Email de atualização - Mensagem</label>
	<textarea id="email_msg_payment_update" class="htmleditor" name="email_msg_payment_update"><?php echo $settings['email_msg_payment_update'];?></textarea>
	<label for="email_sbj_payment_delete">Email de remoção - Assunto</label>
	<input  id="email_sbj_payment_delete" type="text" name="email_sbj_payment_delete" value="<?php echo $settings['email_sbj_payment_delete'];?>"/>
	<label for="email_msg_payment_delete">Email de remoção - Mensagem</label>
	<textarea id="email_msg_payment_delete" class="htmleditor" name="email_msg_payment_delete"><?php echo $settings['email_msg_payment_delete'];?></textarea>
	</div>
	</form>
	<?php

	$eve->output_html_footer();
}
?>
