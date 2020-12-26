<?php
session_start();
require_once 'lib/g11n/g11nlocales.php';
require_once 'eve.class.php';
require_once 'evesettingsservice.class.php';

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
		$eve->_('settings.general') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'system_name', 'support_email_address', 'userarea_label', 'system_locale',
		'system_custom_login_message', 'system_custom_login_message_text',
		'system_custom_message', 'system_custom_message_title', 'system_custom_message_text'
	);
	
	?>
	<div class="section"><?php echo $eve->_('settings.general');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post" class="dialog_panel">
	
	<label for="system_name"><?php echo $eve->_('settings.general.system.name');?></label>
	<input  id="system_name" name="system_name" type="text" value="<?php echo $settings['system_name'];?>"/>

	<label for="support_email_address"><?php echo $eve->_('settings.general.support.email.address');?></label>
	<input  id="support_email_address" name="support_email_address" type="text" value="<?php echo $settings['support_email_address'];?>" />

	<label for="userarea_label"><?php echo $eve->_('settings.general.user.area.label');?></label>
	<input  id="userarea_label" name="userarea_label" type="text" value="<?php echo $settings['userarea_label'];?>" />

	<label for="system_locale"><?php echo $eve->_('settings.general.system.locale');?></label>
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
	<input  id="system_custom_login_message" name="system_custom_login_message" type="checkbox" value="1" <?php if($settings['system_custom_login_message']) echo "checked=\"checked\"";?>>
	<?php echo $eve->_('settings.general.system.custom.login.message');?></label>
	
	<label for="system_custom_login_message_text"><?php echo $eve->_('settings.general.system.custom.login.message.text');?></label>
	<textarea id="system_custom_login_message_text" name="system_custom_login_message_text" class="htmleditor"><?php echo $settings['system_custom_login_message_text'];?></textarea>

	<label for="system_custom_message"><input type="hidden" name="system_custom_message" value="0">
	<input  id="system_custom_message" name="system_custom_message" type="checkbox" value="1" <?php if($settings['system_custom_message']) echo "checked=\"checked\"";?>>
	<?php echo $eve->_('settings.general.system.custom.message');?></label>
	
	<label for="system_custom_message_title"><?php echo $eve->_('settings.general.system.custom.message.title');?></label>
	<input  id="system_custom_message_title" name="system_custom_message_title" type="text" value="<?php echo $settings['system_custom_message_title'];?>"/>
	
	<label for="system_custom_message_text"><?php echo $eve->_('settings.general.system.custom.message.text');?></label>
	<textarea id="system_custom_message_text" name="system_custom_message_text" class="htmleditor"><?php echo $settings['system_custom_message_text'];?></textarea>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>