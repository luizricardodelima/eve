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
		$eve->_('settings.submissions') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'email_sbj_submission_create', 'email_msg_submission_create',
		'email_sbj_submission_delete', 'email_msg_submission_delete',
		'email_sbj_submission_update', 'email_msg_submission_update'
	);
	
	?>
	<script>
	function submission_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$user_name - Nome do agente (quem criou, apagou ou alterou a submissão)'
				+'\n$user_email - Email do agente (quem criou, apagou ou alterou a submissão)'
				+'\n$date_time - Data e hora do ocorrido'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'
				+'\n$submission_content - Conteúdo da submissão'
				+'\n$submission_old_content - Antigo conteúdo da submissão (no caso de alteração de submissão)');
	}
	</script>

	<div class="section"><?php echo $eve->_('settings.submissions');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" onclick="submission_email_help()">?</button>
	</div>

	<form id="settings_form" method="post">

	<div class="dialog_panel">
	<div class="dialog_section">Email de confirmação de submissão enviada</div>
	<label for="email_sbj_submission_create">Assunto</label>
	<input  id="email_sbj_submission_create" type="text" name="email_sbj_submission_create" value="<?php echo $settings['email_sbj_submission_create'];?>"/>
	<label for="email_msg_submission_create">Mensagem</label>
	<textarea id="email_msg_submission_create" class="htmleditor" rows="4" name="email_msg_submission_create"><?php echo $settings['email_msg_submission_create'];?></textarea>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">Email de confirmação de submissão apagada</div>
	<label for="email_sbj_submission_delete">Assunto</label>
	<input  id="email_sbj_submission_delete" type="text" name="email_sbj_submission_delete" value="<?php echo $settings['email_sbj_submission_delete'];?>"/>
	<label for="email_msg_submission_delete">Mensagem</label>
	<textarea id="email_msg_submission_delete" class="htmleditor" rows="4" name="email_msg_submission_delete"><?php echo $settings['email_msg_submission_delete'];?></textarea>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">Email de confirmação de submissão alterada</div>
	<label for="email_sbj_submission_update">Assunto</label>
	<input  id="email_sbj_submission_update" type="text" name="email_sbj_submission_update" value="<?php echo $settings['email_sbj_submission_update'];?>"/>
	<label for="email_msg_submission_update">Mensagem</label>
	<textarea id="email_msg_submission_update" class="htmleditor" rows="4" name="email_msg_submission_update"><?php echo $settings['email_msg_submission_update'];?></textarea>
	</div>

	</form>
	<?php

	$eve->output_html_footer();
}
?>
