<?php
session_start();
require_once 'eve.class.php';

$eve = new Eve();

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
else if (sizeof($_POST) > 0)
{
	// There are POST variables.  Saving settings to database.
	foreach ($_POST as $key => $value)
	{
		$value = $eve->mysqli->real_escape_string($value);
		$eve->mysqli->query("UPDATE `{$eve->DBPref}settings` SET `value` = '$value' WHERE `key` = '$key';");
	}
			
	// Reloading this page with the new settngs. Success informations is passed through a simple get parameter
	$eve->output_redirect_page(basename(__FILE__)."?saved=1");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Revisores e revisões", null);	
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'email_snd_reviewer' OR
		`key` = 'email_sbj_reviewer' OR
		`key` = 'email_msg_reviewer' OR
		`key` = 'email_snd_revision' OR
		`key` = 'email_sbj_revision' OR
		`key` = 'email_msg_revision'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
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

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">

	<div class="section">E-mail de atribuição para o revisor <button type="button" onclick="reviewer_attribution_help()">?</button></div>
	<div class="dialog_panel">
	<label for="email_snd_reviewer">
	<input  id="email_snd_reviewer" type="checkbox" name="email_snd_reviewer" value="1" <?php if ($settings['email_snd_reviewer']) echo "checked=\"checked\"";?> /><input type="hidden" name="email_snd_reviewer" value="0"/>Enviar e-mail de atribuição para revisor</label>
	<label for="email_sbj_reviewer">Assunto</label>
	<input  id="email_sbj_reviewer" type="text" name="email_sbj_reviewer" value="<?php echo $settings['email_sbj_reviewer'];?>"/>
	<label for="email_msg_reviewer">Mensagem</label>
	<textarea id="email_msg_reviewer" class="htmleditor" rows="6" name="email_msg_reviewer"><?php echo $settings['email_msg_reviewer'];?></textarea>
	</div>

	<div class="section">E-mail de revisão concluída <button type="button" onclick="submission_revision_email_help()">?</button></div>
	<div class="dialog_panel">
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
