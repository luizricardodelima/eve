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
		$eve->_('settings.certifications') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'email_snd_certification', 'email_sbj_certification', 'email_msg_certification'
	);

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

	<div class="section"><?php echo $eve->_('settings.certifications');?> 
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	<div class="dialog_panel">

	<div class="dialog_section">
	<label for="email_snd_certification"><input type="hidden" name="email_snd_certification" value="0"/>
	<input  id="email_snd_certification" type="checkbox" name="email_snd_certification" value="1" <?php if ($settings['email_snd_certification']) echo "checked=\"checked\"";?> />
	Enviar e-mail ao gerar certificado para o usuário</label>
	<button type="button" onclick="certification_email_help()">?</button>
	</div>
	
	<label for="email_sbj_certification">Assunto</label>
	<input  id="email_sbj_certification" type="text" name="email_sbj_certification" value="<?php echo $settings['email_sbj_certification'];?>"/>
	<label for="email_msg_certification">Mensagem</label>
	<textarea id="email_msg_certification" class="htmleditor" rows="6" name="email_msg_certification"><?php echo $settings['email_msg_certification'];?></textarea>
	</div>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
