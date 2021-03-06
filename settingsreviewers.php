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
		$eve->_('settings.reviewers.and.revisions') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'email_snd_reviewer', 'email_sbj_reviewer', 'email_msg_reviewer', 
		'email_snd_revision', 'email_sbj_revision', 'email_msg_revision'
	);

	?>
	<script>
	function reviewer_attribution_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do avaliador'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'
				+'\n$submission_content - Conteúdo da submissão');
	}

	function submission_revision_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'
				+'\n$submission_content - Conteúdo da submissão'
				+'\n$revision_content - Conteúdo da avaliação');
	}
	</script>

	<div class="section"><?php echo $eve->_('settings.reviewers.and.revisions');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">

	<div class="dialog_panel">
	<div class="dialog_section">E-mail de atribuição para o revisor <button type="button" onclick="reviewer_attribution_help()">?</button></div>
	<label for="email_snd_reviewer">
	<input  id="email_snd_reviewer" type="checkbox" name="email_snd_reviewer" value="1" <?php if ($settings['email_snd_reviewer']) echo "checked=\"checked\"";?> /><input type="hidden" name="email_snd_reviewer" value="0"/>Enviar e-mail de atribuição para revisor</label>
	<label for="email_sbj_reviewer">Assunto</label>
	<input  id="email_sbj_reviewer" type="text" name="email_sbj_reviewer" value="<?php echo $settings['email_sbj_reviewer'];?>"/>
	<label for="email_msg_reviewer">Mensagem</label>
	<textarea id="email_msg_reviewer" class="htmleditor" rows="6" name="email_msg_reviewer"><?php echo $settings['email_msg_reviewer'];?></textarea>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">E-mail de revisão concluída <button type="button" onclick="submission_revision_email_help()">?</button></div>
	<label for="email_snd_revision">
	<input  id="email_snd_revision" type="checkbox" name="email_snd_revision" value="1" <?php if ($settings['email_snd_revision']) echo "checked=\"checked\"";?> /><input type="hidden" name="email_snd_revision" value="0"/>Enviar e-mail de revisão concluída para quem fez submissão</label>
	<label for="email_sbj_revision">Assunto</label>
	<input  id="email_sbj_revision" type="text" name="email_sbj_revision" value="<?php echo $settings['email_sbj_revision'];?>"/>
	<label for="email_msg_revision">Mensagem</label>
	<textarea id="email_msg_revision" class="htmleditor" rows="6" name="email_msg_revision"><?php echo $settings['email_msg_revision'];?></textarea>
	</div>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
