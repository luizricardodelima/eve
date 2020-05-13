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
	// TODO this should go to a service, like $eve->get_settings($key1, $key2...) - with prepared statements!
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'system_name' OR
		`key` = 'support_email_address' OR
		`key` = 'userarea_label' OR
		`key` = 'system_locale' OR
		`key` = 'system_custom_login_message' OR
		`key` = 'system_custom_login_message_text'OR
		`key` = 'system_custom_message' OR
		`key` = 'system_custom_message_title' OR
		`key` = 'system_custom_message_text';
	");

	$settings = array();	
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	?>
	
	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	</div>

	<form id="settings_form" method="post">
	<table style="width: 100%">
	<tr><td>Nome do sistema</td></tr>
	<tr><td><textarea rows="1" cols="50" name="system_name"><?php echo $settings['system_name'];?></textarea></td></tr>
	<tr><td>E-mail de suporte</td></tr>
	<tr><td><textarea rows="1" cols="50" name="support_email_address"><?php echo $settings['support_email_address'];?></textarea></td></tr>
	<tr><td>Nome da área restrita</td></tr>
	<tr><td><textarea rows="1" cols="50" name="userarea_label"><?php echo $settings['userarea_label'];?></textarea></td></tr>
	<tr><td>Locale</td></tr><tr><td>
	<?php	
	
	echo "<select name=\"system_locale\">";
	foreach (G11nLocales::$locales as $lcl_code => $lcl_name)
	{	
		echo "<option value=\"$lcl_code\"";
		if ($settings['system_locale'] == $lcl_code) echo " selected=\"selected\"";
		echo "> $lcl_name ($lcl_code)</option>";
	}

	?>
	</td></tr>
	<tr><td><input type="hidden" name="system_custom_login_message" value="0"><input type="checkbox" name="system_custom_login_message" id="system_custom_login_message_cbx" value="1" <?php if($settings['system_custom_login_message']) echo "checked = \"checked\"";?>><label for="system_custom_login_message_cbx">Mensagem Personalizada na tela de login</label></td></tr>
	<tr><td>Mensagem Personalizada na tela de login - Texto</td></tr>
	<tr><td><textarea rows="3" cols="50" name="system_custom_login_message_text" class="htmleditor" ><?php echo $settings['system_custom_login_message_text'];?></textarea></td></tr>

	<tr><td><input type="hidden" name="system_custom_message" value="0"><input type="checkbox" name="system_custom_message" id="system_custom_message_cbx" value="1" <?php if($settings['system_custom_message']) echo "checked = \"checked\"";?>><label for="system_custom_message_cbx">Mensagem Personalizada na área restrita</label></td></tr>
	<tr><td>Mensagem Personalizada na área restrita - Título</td></tr>
	<tr><td><textarea rows="1" cols="50" name="system_custom_message_title"><?php echo $settings['system_custom_message_title'];?></textarea></td></tr>
	<tr><td>Mensagem Personalizada na área restrita - Texto</td></tr>
	<tr><td><textarea rows="3" cols="50" name="system_custom_message_text" class="htmleditor" ><?php echo $settings['system_custom_message_text'];?></textarea></td></tr>
	</table>
	</form>
	<?php

	$eve->output_html_footer();
}
?>
