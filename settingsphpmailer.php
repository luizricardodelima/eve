<?php
session_start();
require_once 'eve.class.php';
require_once 'evemail.class.php';
require_once 'evesettingsservice.class.php';

// TODO G11N this page
$eve = new Eve();
$eveSettingsService = new EveSettingsService($eve);

if (!isset($_SESSION['screenname']))
{
	// Session verification.	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if (!$eve->is_admin($_SESSION['screenname']))
{
	// Administrative privileges verification.
	$eve->output_error_page('common.message.no.permission');
}
else 
{
	$saved = null;
	$output = null;

	if (isset($_POST['action'])) switch($_POST['action'])
	{
		case 'save':
			unset($_POST['action']);
			$msg = $eveSettingsService->settings_update($_POST);
			break;
		case 'mailtest':
			$output  = "";
			$output .= "Criando EveMail\n";
			$evemail = new EveMail($eve);
			$output .= "\nEnviando e-mail para {$_POST['emailaddress']}\n";
			$evemail->send_mail($_POST['emailaddress'], null, "EVE TEST - SUBJECT", "EVE TEST - HTML BODY", "EVE TEST - PLAIN TEXT BODY");
			$output .= "\nLog: \n";
			$output .= $evemail->phpmailer_error_info;
			$output .= $evemail->log;
			break;
	}

	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.settings') => "settings.php",
		$eve->_('settings.mail.configuration') => null,
	]);

	$settings = $eveSettingsService->settings_get
	(
		'phpmailer_host', 'phpmailer_username', 'phpmailer_password', 'phpmailer_fromname',
		'phpmailer_smtpauth', 'phpmailer_smtpsecure', 'phpmailer_port', 'phpmailer_smtpdebug'
	);
	
	?>
	<div class="section"><?php echo $eve->_('settings.mail.configuration');?>
	<button type="button" onclick="document.forms['settings_form'].submit()"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" onclick="mailtest()"><?php echo $eve->_('common.action.test');?></button><br/>
	</div>
	<?php

	if (isset($msg)) $eve->output_service_message($msg);
	if ($output)
	{
		?>
		<div class="dialog_panel"><p>Saída</p>
		<textarea rows="10"><?php echo $output;?></textarea>
		<button type="button" onclick="this.parentNode.style.display='none';">Fechar</button>
		</div>
		<?php
	}

	?>
	<form id="settings_form" method="post" class="dialog_panel">
	<input type="hidden" name="action" value="save"/>
	<label for="phpmailer_host">Host (host)</label>
	<input  id="phpmailer_host" name="phpmailer_host" type="text" value="<?php echo $settings['phpmailer_host'];?>"/>
	<label for="phpmailer_username">Nome do usuário (username)</label>
	<input  id="phpmailer_username" name="phpmailer_username" type="text" value="<?php echo $settings['phpmailer_username'];?>"/>
	<label for="phpmailer_password">Senha (password)</label>
	<input  id="phpmailer_password" name="phpmailer_password" type="text" value="<?php echo $settings['phpmailer_password'];?>"/>
	<label for="phpmailer_fromname">Nome do remetente (fromname)</label>
	<input  id="phpmailer_fromname" name="phpmailer_fromname" type="text" value="<?php echo $settings['phpmailer_fromname'];?>"/>
	<label for="phpmailer_smtpauth">Autenticação SMTP (smtpauth)</label>
	<input  id="phpmailer_smtpauth" name="phpmailer_smtpauth" type="text" value="<?php echo $settings['phpmailer_smtpauth'];?>"/>
	<label for="phpmailer_smtpsecure">SMTP Seguro (smtpsecure)</label>
	<input  id="phpmailer_smtpsecure" name="phpmailer_smtpsecure" type="text" value="<?php echo $settings['phpmailer_smtpsecure'];?>"/>
	<label for="phpmailer_port">Porta (port)</label>
	<input  id="phpmailer_port" name="phpmailer_port" type="text" value="<?php echo $settings['phpmailer_port'];?>"/>
	<label for="phpmailer_smtpdebug">Debug (smtpdebug)<br/><small>Aceita números de 0 a 4, onde 4 significa informaões mais detalhadas de debug. As mensagens são capturadas pela classe EveMail e somente mostradas aqui no teste. Para produção, utilize 0.</small></label>
	<input  id="phpmailer_smtpdebug" name="phpmailer_smtpdebug" type="text" value="<?php echo $settings['phpmailer_smtpdebug'];?>"/>
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
