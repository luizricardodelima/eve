<?php
session_start();
require_once 'eve.class.php';
require_once 'lib/g11n/g11nlocales.php';
require_once 'lib/g11n/g11ncurrencies.php';

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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Informações gerais", null);
	
	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		select * from `{$eve->DBPref}settings` where `key` in
		('system_name', 'support_email_address', 'userarea_label', 'system_locale',
		'system_custom_login_message', 'system_custom_login_message_text',
		'system_custom_message', 'system_custom_message_title', 'system_custom_message_text')
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	?>
	
	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();">Salvar</button>
	</div>

	<form id="settings_form" method="post" class="dialog_panel">
	
	<label for="system_name">Nome do sistema</label>
	<input  id="system_name" name="system_name" type="text" value="<?php echo $settings['system_name'];?>"/>

	<label for="support_email_address">E-mail de suporte</label>
	<input  id="support_email_address" name="support_email_address" type="text" value="<?php echo $settings['support_email_address'];?>" />

	<label for="userarea_label">Nome da área restrita</label>
	<input  id="userarea_label" name="userarea_label" type="text" value="<?php echo $settings['userarea_label'];?>" />

	<label for="system_locale">Locale</label>
	<select id="system_locale" name="system_locale">
	<?php	
	
	foreach (G11nLocales::$locales as $lcl_code => $lcl_name)
	{	
		echo "<option value=\"$lcl_code\"";
		if ($settings['system_locale'] == $lcl_code) echo " selected=\"selected\"";
		echo "> $lcl_name ($lcl_code)</option>";
	}

	?>
	</select>
	
	<label for="system_custom_login_message"><input type="hidden" name="system_custom_login_message" value="0">
	<input  id="system_custom_login_message" name="system_custom_login_message" type="checkbox" value="1" <?php if($settings['system_custom_login_message']) echo "checked=\"checked\"";?>>Mensagem Personalizada na tela de login</label>
	
	<label for="system_custom_login_message_text">Mensagem Personalizada na tela de login - Texto</label>
	<textarea id="system_custom_login_message_text" name="system_custom_login_message_text" class="htmleditor"><?php echo $settings['system_custom_login_message_text'];?></textarea>

	<label for="system_custom_message"><input type="hidden" name="system_custom_message" value="0">
	<input  id="system_custom_message" name="system_custom_message" type="checkbox" value="1" <?php if($settings['system_custom_message']) echo "checked=\"checked\"";?>>Mensagem Personalizada na área restrita</label>
	
	<label for="system_custom_message_title">Mensagem Personalizada na área restrita - Título</label>
	<input  id="system_custom_message_title" name="system_custom_message_title" type="text" value="<?php echo $settings['system_custom_message_title'];?>"/>
	
	<label for="system_custom_message_text">Mensagem Personalizada na área restrita - Texto</label>
	<textarea id="system_custom_message_text" name="system_custom_message_text" class="htmleditor"><?php echo $settings['system_custom_message_text'];?></textarea>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>