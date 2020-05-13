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
	$eve->output_wysiwig_editor_code();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Inscrições", null);	

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'user_signup_closed' OR
		`key` = 'user_signup_closed_message' OR
		`key` = 'verification_email_subject' OR
		`key` = 'verification_email_body_html' OR
		`key` = 'welcome_email_subject' OR
		`key` = 'welcome_email_body_html' OR
		`key` = 'password_retrieval_email_subject' OR
		`key` = 'password_retrieval_email_body_html'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	?>
	<script>
	function verification_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email cadastrado'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema'
				+'\n$verification_code - Código de verificação'
				+'\n$verification_url - Endereço de verificação automático');
	}

	function welcome_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema');
	}

	function password_retrieval_email_help() {
		window.alert('Variáveis permitidas:'
				+'\n$email - Email do usuário'
				+'\n$password - Nova senha'
				+'\n$support_email_address - E-mail de suporte'
				+'\n$system_name - Nome do sistema'
				+'\n$site_url - Endereço de acesso ao sistema');
	}
	</script>

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	</div>

	<form id="settings_form" method="post">
	
	<div class="section">Novas inscri&ccedil;&otilde;es</div>
	<table style="width: 100%">
	<tr><td><input type="hidden" name="user_signup_closed" value="0"/><input type="checkbox" name="user_signup_closed" value="1" <?php if ($settings['user_signup_closed']) echo "checked=\"checked\"";?> /> Sistema fechado para novas inscri&ccedil;&otilde;es</td></tr>
	<tr><td>Mensagem para sistema fechado para novas inscri&ccedil;&otilde;es</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="user_signup_closed_message"><?php echo $settings['user_signup_closed_message'];?></textarea></td></tr>
	</table>
	<div class="section">E-mail de verifica&ccedil;&atilde;o <button type="button" onclick="verification_email_help()">?</button></div>
	<table style="width: 100%">
	<tr><td>Assunto</td></tr>
	<tr><td><textarea rows="1" cols="50" name="verification_email_subject"><?php echo $settings['verification_email_subject'];?></textarea></td></tr>
	<tr><td>Mensagem - HTML</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="verification_email_body_html"><?php echo $settings['verification_email_body_html'];?></textarea></td></tr>
	</table>
	<div class="section">E-mail de boas vindas <button type="button" onclick="welcome_email_help()">?</button></div>
	<table style="width: 100%">
	<tr><td>Assunto</td></tr>
	<tr><td><textarea rows="1" cols="50" name="welcome_email_subject"><?php echo $settings['welcome_email_subject'];?></textarea></td></tr>
	<tr><td>Mensagem - HTML</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="welcome_email_body_html"><?php echo $settings['welcome_email_body_html'];?></textarea></td></tr>
	</table>
	<div class="section">E-mail de recupera&ccedil;&atilde;o de senha <button type="button" onclick="password_retrieval_email_help()">?</button></div>
	<table style="width: 100%">
	<tr><td>Assunto</td></tr>
	<tr><td><textarea rows="1" cols="50" name="password_retrieval_email_subject"><?php echo $settings['password_retrieval_email_subject'];?></textarea></td></tr>
	<tr><td>Mensagem - HTML</td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="password_retrieval_email_body_html"><?php echo $settings['password_retrieval_email_body_html'];?></textarea></td></tr>
	</table>

	</form>
	<?php
	$eve->output_html_footer();
}
?>
