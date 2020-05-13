<?php
session_start();
require_once 'eve.class.php';
require_once 'evemail.php';

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
else if (sizeof($_POST) > 0) switch($_POST['action'])
{
	case 'save':
		unset($_POST['action']);
		// There are POST variables.  Saving settings to database.
		foreach ($_POST as $key => $value)
		{
			$value = $eve->mysqli->real_escape_string($value);
			$eve->mysqli->query("UPDATE `{$eve->DBPref}settings` SET `value` = '$value' WHERE `key` = '$key';");
		}
				
		// Reloading this page with the new settngs. Success informations is passed through a simple get parameter
		$eve->output_redirect_page(basename(__FILE__)."?saved=1");
		break;
	case 'mailtest':
		// TODO Improve logs and error outputs
		$output  = "";
		$evemail = new EveMail($eve);
		$output .= "Criando EveMail\n";
		$output .= var_export($evemail, true);
		$output .= "\nEnviando e-mail para {$_POST['emailaddress']}\n";
		$evemail->send_mail($_POST['emailaddress'], null, "EVE TEST - SUBJECT", "EVE TEST - HTML BODY", "EVE TEST - PLAIN TEXT BODY");
		$output .= "\nErros: \n";
		$output .= $evemail->phpmailer_error_info;
		$eve->output_redirect_page(basename(__FILE__)."?output=".urlencode($output));
		break;
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Envio de e-mail", null);
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");
	if (isset($_GET['output']))
		$eve->output_success_message("<textarea rows=\"10\" cols=\"50\">{$_GET['output']}</textarea>");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'phpmailer_host' OR
		`key` = 'phpmailer_username' OR
		`key` = 'phpmailer_password' OR
		`key` = 'phpmailer_fromname' OR
		`key` = 'phpmailer_smtpauth' OR
		`key` = 'phpmailer_smtpsecure' OR
		`key` = 'phpmailer_port' OR
		`key` = 'phpmailer_smtpdebug'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	
	?>

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	<button type="button" onclick="mailtest();"/>Testar</button><br/>
	</div>

	<form id="settings_form" method="post">

	<div class="section">Configurações PHPMailer</div>
	<input type="hidden" name="action" value="save"/>
	<table style="width: 100%">
	<tr><td>Host (host)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_host"><?php echo $settings['phpmailer_host'];?></textarea></td></tr>
	<tr><td>Nome do usuário (username)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_username"><?php echo $settings['phpmailer_username'];?></textarea></td></tr>
	<tr><td>Senha (password)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_password"><?php echo $settings['phpmailer_password'];?></textarea></td></tr>
	<tr><td>Nome do remetente (fromname)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_fromname"><?php echo $settings['phpmailer_fromname'];?></textarea></td></tr>
	<tr><td>Autenticação SMTP (smtpauth) </td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_smtpauth"><?php echo $settings['phpmailer_smtpauth'];?></textarea></td></tr>
	<tr><td>SMTP Seguro (smtpsecure)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_smtpsecure"><?php echo $settings['phpmailer_smtpsecure'];?></textarea></td></tr>
	<tr><td>Porta (port)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_port"><?php echo $settings['phpmailer_port'];?></textarea></td></tr>
	<tr><td>Debug (smtpdebug)</td></tr>
	<tr><td><textarea rows="1" cols="50" name="phpmailer_smtpdebug"><?php echo $settings['phpmailer_smtpdebug'];?></textarea></td></tr>
	</table>
	</form>

	<form id="mailtest_form" method="post">
	<input type="hidden" name="action" value="mailtest"/>
	<input type="hidden" name="emailaddress" id="emailaddress_hidden_value"/>
	</form>

	<script>	
	function mailtest()
	{
		var emailaddress = prompt("Insira o e-mail de destino do teste.");
		if (emailaddress != null)
		{
			document.getElementById('emailaddress_hidden_value').value=emailaddress;
			document.getElementById('mailtest_form').submit();
		}
		return false;
	}
	</script>
	
	<?php

	$eve->output_html_footer();
}
?>
