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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Submissões", null);	
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'email_snd_submission' OR
		`key` = 'email_sbj_submission' OR
		`key` = 'email_msg_submission'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	?>
	<script>
	function submission_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do avaliador'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'
				+'\n$submission_content - Conteúdo da submissão');
	}
	</script>

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	</div>

	<form id="settings_form" method="post">

	<div class="section">E-mail de recebimento de submissão <button type="button" onclick="submission_email_help()">?</button></div>
	<div class="dialog_panel">
	<label for="email_snd_submission">
	<input  id="email_snd_submission" type="checkbox" name="email_snd_submission" value="1" <?php if ($settings['email_snd_submission']) echo "checked=\"checked\"";?> /><input type="hidden" name="email_snd_submission" value="0"/>Enviar e-mail de confirmação de recebimento de submissão</label>
	<label for="email_sbj_submission">Assunto</label>
	<input  id="email_sbj_submission" type="text" name="email_sbj_submission" value="<?php echo $settings['email_sbj_submission'];?>"/>
	<label for="email_msg_submission">Mensagem</label>
	<textarea id="email_msg_submission" class="htmleditor" rows="6" name="email_msg_submission"><?php echo $settings['email_msg_submission'];?></textarea>
	</div>

	</form>
	<?php

	$eve->output_html_footer();
}
?>
