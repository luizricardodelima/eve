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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Revisores", null);	
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'reviewer_attribution_email_send' OR
		`key` = 'reviewer_attribution_email_subject' OR
		`key` = 'reviewer_attribution_email_body' OR
		`key` = 'submission_revision_email_send' OR
		`key` = 'submission_revision_email_subject' OR
		`key` = 'submission_revision_email_body'
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
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	</div>

	<form id="settings_form" method="post">

	<div class="section">E-mail de atribuição para o revisor <button type="button" onclick="reviewer_attribution_help()">?</button></div>
	<table style="width: 100%">
	<tr><td><input type="hidden" name="reviewer_attribution_email_send" value="0"/><input type="checkbox" name="reviewer_attribution_email_send" value="1" <?php if ($settings['reviewer_attribution_email_send']) echo "checked=\"checked\"";?> /> Enviar e-mail de atribuição para revisor</td></tr>
	<tr><td>Assunto</td></tr>
	<tr><td><textarea rows="1" cols="50" name="reviewer_attribution_email_subject"><?php echo $settings['reviewer_attribution_email_subject'];?></textarea></td></tr>
	<tr><td>Mensagem</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="reviewer_attribution_email_body"><?php echo $settings['reviewer_attribution_email_body'];?></textarea></td></tr>
	</table>

	<div class="section">E-mail de revisão concluída <button type="button" onclick="submission_revision_email_help()">?</button></div>
	<table style="width: 100%">
	<tr><td><input type="hidden" name="submission_revision_email_send" value="0"/><input type="checkbox" name="submission_revision_email_send" value="1" <?php if ($settings['submission_revision_email_send']) echo "checked=\"checked\"";?> /> Enviar e-mail de revisão concluída para quem enviou o trabalho</td></tr>
	<tr><td>Assunto</td></tr>
	<tr><td><textarea rows="1" cols="50" name="submission_revision_email_subject"><?php echo $settings['submission_revision_email_subject'];?></textarea></td></tr>
	<tr><td>Mensagem</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="submission_revision_email_body"><?php echo $settings['submission_revision_email_body'];?></textarea></td></tr>
	</table>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
