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
		$eve->_('settings.user.signup') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'user_signup_closed', 'user_signup_closed_message', 'email_sbj_user_verification',
		'email_msg_user_verification',  'email_sbj_welcome', 'email_msg_welcome',
		'email_sbj_password_retrieval', 'email_msg_password_retrieval'
	);

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

	<div class="section"><?php echo $eve->_('settings.user.signup');?> 
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	
	<div class="dialog_panel">
	<div class="dialog_section">Novas inscri&ccedil;&otilde;es</div>
	<label for="user_signup_closed"><input type="hidden" name="user_signup_closed" value="0"/> 
	<input  id="user_signup_closed" type="checkbox" name="user_signup_closed" value="1" <?php if ($settings['user_signup_closed']) echo "checked=\"checked\"";?> />Sistema fechado para novas inscri&ccedil;&otilde;es</label>
	<label for="user_signup_closed_message"></label>Mensagem para sistema fechado para novas inscri&ccedil;&otilde;es</label>
	<textarea id="user_signup_closed_message" class="htmleditor" rows="6" name="user_signup_closed_message"><?php echo $settings['user_signup_closed_message'];?></textarea>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">E-mail de verifica&ccedil;&atilde;o <button type="button" onclick="verification_email_help()">?</button></div>
	<label for="email_sbj_user_verification">Assunto</label>
	<input  id="email_sbj_user_verification" type="text" name="email_sbj_user_verification" value="<?php echo $settings['email_sbj_user_verification'];?>"/>
	<label for="email_msg_user_verification">Mensagem</label>
	<textarea id="email_msg_user_verification" class="htmleditor" rows="6" name="email_msg_user_verification"><?php echo $settings['email_msg_user_verification'];?></textarea></td></tr>
	</div>
	
	<div class="dialog_panel">
	<div class="dialog_section">E-mail de boas vindas <button type="button" onclick="welcome_email_help()">?</button></div>
	<label for="email_sbj_welcome">Assunto</label>
	<input  id="email_sbj_welcome" type="text" name="email_sbj_welcome" value="<?php echo $settings['email_sbj_welcome'];?>"/>
	<label for="email_msg_welcome">Mensagem</label>
	<textarea id="email_msg_welcome" class="htmleditor" rows="6" name="email_msg_welcome"><?php echo $settings['email_msg_welcome'];?></textarea>
	</div>
	
	<div class="dialog_panel">
	<div class="dialog_section">E-mail de recupera&ccedil;&atilde;o de senha <button type="button" onclick="password_retrieval_email_help()">?</button></div>
	<label for="email_sbj_password_retrieval">Assunto</label>
	<input  id="email_sbj_password_retrieval" type="text" name="email_sbj_password_retrieval" value="<?php echo $settings['email_sbj_password_retrieval'];?>"/>
	<label for="email_msg_password_retrieval">Mensagem - HTML</label>
	<textarea id="email_msg_password_retrieval" class="htmleditor" rows="6" name="email_msg_password_retrieval"><?php echo $settings['email_msg_password_retrieval'];?></textarea>
	</div>

	</form>
	<?php
	$eve->output_html_footer();
}
?>
