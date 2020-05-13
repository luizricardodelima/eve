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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Certificados", null);
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'certification_send_email_on_update' OR
		`key` = 'certification_email_subject' OR
		`key` = 'certification_email_body_html'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];

	?>
	<script>
	function certification_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$certification_name - Nome do certificado'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema');
	}
	</script>

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	</div>

	<form id="settings_form" method="post">
	<div class="section">Certificado - Email de aviso <button type="button" onclick="certification_email_help()">?</button></div>
	<table style="width: 100%">
	<tr><td><input type="hidden" name="certification_send_email_on_update" value="0"/> <input type="checkbox" name="certification_send_email_on_update" value="1" <?php if ($settings['certification_send_email_on_update']) echo "checked=\"checked\"";?> /> Enviar e-mail ao gerar certificado para usuário</td></tr>
	<tr><td>Assunto</td></tr>
	<tr><td><textarea rows="1" cols="50" name="certification_email_subject"><?php echo $settings['certification_email_subject'];?></textarea></td></tr>
	<tr><td>Mensagem - HTML</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="certification_email_body_html"><?php echo $settings['certification_email_body_html'];?></textarea></td></tr>
	</table>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
