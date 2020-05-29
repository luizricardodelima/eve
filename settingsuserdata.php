<?php
session_start();
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
	$eveSettingsService->settings_update($_POST);
	$eve->output_redirect_page(basename(__FILE__)."?saved=1");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Dados do usuário", null);	
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	$settings = $eveSettingsService->settings_get
	(
		'block_user_form',  'user_display_custom_message_on_unlocked_form',
		'user_custom_message_on_unlocked_form', 
		'user_name_visible', 'user_name_mandatory', 'user_address_visible', 'user_address_mandatory', 
		'user_city_visible', 'user_city_mandatory', 'user_state_visible', 'user_state_mandatory', 
		'user_country_visible', 'user_country_mandatory', 'user_postalcode_visible', 'user_postalcode_mandatory', 
		'user_birthday_visible', 'user_birthday_mandatory', 'user_gender_visible', 'user_gender_mandatory', 
		'user_phone1_visible', 'user_phone1_mandatory', 'user_phone2_visible', 'user_phone2_mandatory', 
		'user_institution_visible', 'user_institution_mandatory', 'user_category_visible', 'user_category_mandatory', 
		'user_customtext1_visible', 'user_customtext1_mandatory', 'user_customtext1_label', 'user_customtext1_mask', 
		'user_customtext2_visible', 'user_customtext2_mandatory', 'user_customtext2_label', 'user_customtext2_mask', 
		'user_customtext3_visible', 'user_customtext3_mandatory', 'user_customtext3_label', 'user_customtext3_mask', 
		'user_customtext4_visible', 'user_customtext4_mandatory', 'user_customtext4_label', 'user_customtext4_mask', 
		'user_customtext5_visible', 'user_customtext5_mandatory', 'user_customtext5_label',	'user_customtext5_mask', 
		'user_customflag1_visible', 'user_customflag1_label', 
		'user_customflag2_visible', 'user_customflag2_label', 
		'user_customflag3_visible', 'user_customflag3_label', 
		'user_customflag4_visible', 'user_customflag4_label', 
		'user_customflag5_visible', 'user_customflag5_label'
	);

	?>
	<div class="section">Dados do usuário
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post" class="dialog_panel_wide">
	<div class="dialog_section">Bloqueio da ficha de inscrição</div>	

	<label for="block_user_form">Bloquear edição da ficha de inscrição</label>
	<select id="block_user_form" name="block_user_form">
		<option value="never" <?php if ($settings['block_user_form'] == 'never') echo "selected=\"selected\"";?>>Nunca</option>
		<option value="after_sending" <?php if ($settings['block_user_form'] == 'after_sending') echo "selected=\"selected\"";?>>Depois do envio</option>
		<option value="after_payment" <?php if ($settings['block_user_form'] == 'after_payment') echo "selected=\"selected\"";?>>Depois do pagamento</option>
	</select>
	
	<label for="user_display_custom_message_on_unlocked_form"><input type="hidden" name="user_display_custom_message_on_unlocked_form" value="0"/>
	<input id="user_display_custom_message_on_unlocked_form" type="checkbox" name="user_display_custom_message_on_unlocked_form" value="1" <?php if ($settings['user_display_custom_message_on_unlocked_form']) echo "checked=\"checked\"";?> />Mensagem personalizada em fichas bloqueadas</label>
	<textarea class="htmleditor" rows="6" cols="50" name="user_custom_message_on_unlocked_form">
	<?php echo $settings['user_custom_message_on_unlocked_form'];?>
	</textarea>
	
	<div class="dialog_section">Campos de usuário</div>
	<table class="data_table">
	<thead>
	<th style="width: 5%">Visível</th>		
	<th style="width: 5%">Obrigat.</th>
	<th style="width: 90%">Campo</th>
	</thead>
	<tr>
	<td><input type="hidden" name="user_name_visible" value="0"/><input type="checkbox" name="user_name_visible" value="1" <?php if ($settings['user_name_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_name_mandatory" value="0"/><input type="checkbox" name="user_name_mandatory" value="1" <?php if ($settings['user_name_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.name');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_address_visible" value="0"/><input type="checkbox" name="user_address_visible" value="1" <?php if ($settings['user_address_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_address_mandatory" value="0"/><input type="checkbox" name="user_address_mandatory" value="1" <?php if ($settings['user_address_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.address');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_city_visible" value="0"/><input type="checkbox" name="user_city_visible" value="1" <?php if ($settings['user_city_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_city_mandatory" value="0"/><input type="checkbox" name="user_city_mandatory" value="1" <?php if ($settings['user_city_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.city');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_state_visible" value="0"/><input type="checkbox" name="user_state_visible" value="1" <?php if ($settings['user_state_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_state_mandatory" value="0"/><input type="checkbox" name="user_state_mandatory" value="1" <?php if ($settings['user_state_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.state');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_country_visible" value="0"/><input type="checkbox" name="user_country_visible" value="1" <?php if ($settings['user_country_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_country_mandatory" value="0"/><input type="checkbox" name="user_country_mandatory" value="1" <?php if ($settings['user_country_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.country');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_postalcode_visible" value="0"/><input type="checkbox" name="user_postalcode_visible" value="1" <?php if ($settings['user_postalcode_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_postalcode_mandatory" value="0"/><input type="checkbox" name="user_postalcode_mandatory" value="1" <?php if ($settings['user_postalcode_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.postalcode');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_birthday_visible" value="0"/><input type="checkbox" name="user_birthday_visible" value="1" <?php if ($settings['user_birthday_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_birthday_mandatory" value="0"/><input type="checkbox" name="user_birthday_mandatory" value="1" <?php if ($settings['user_birthday_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.birthday');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_gender_visible" value="0"/><input type="checkbox" name="user_gender_visible" value="1" <?php if ($settings['user_gender_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_gender_mandatory" value="0"/><input type="checkbox" name="user_gender_mandatory" value="1" <?php if ($settings['user_gender_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.gender');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_phone1_visible" value="0"/><input type="checkbox" name="user_phone1_visible" value="1" <?php if ($settings['user_phone1_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_phone1_mandatory" value="0"/><input type="checkbox" name="user_phone1_mandatory" value="1" <?php if ($settings['user_phone1_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.phone1');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_phone2_visible" value="0"/><input type="checkbox" name="user_phone2_visible" value="1" <?php if ($settings['user_phone2_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_phone2_mandatory" value="0"/><input type="checkbox" name="user_phone2_mandatory" value="1" <?php if ($settings['user_phone2_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.phone2');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_institution_visible" value="0"/><input type="checkbox" name="user_institution_visible" value="1" <?php if ($settings['user_institution_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_institution_mandatory" value="0"/><input type="checkbox" name="user_institution_mandatory" value="1" <?php if ($settings['user_institution_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.institution');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_category_visible" value="0"/><input type="checkbox" name="user_category_visible" value="1" <?php if ($settings['user_category_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_category_mandatory" value="0"/><input type="checkbox" name="user_category_mandatory" value="1" <?php if ($settings['user_category_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.category');?></td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext1_visible" value="0"/><input type="checkbox" name="user_customtext1_visible" value="1" <?php if ($settings['user_customtext1_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext1_mandatory" value="0"/><input type="checkbox" name="user_customtext1_mandatory" value="1" <?php if ($settings['user_customtext1_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext1');?><input type="text" name="user_customtext1_label" value="<?php echo $settings['user_customtext1_label'];?>"/>
	&nbsp; Máscara<input type="text" name="user_customtext1_mask" value="<?php echo $settings['user_customtext1_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext2_visible" value="0"/><input type="checkbox" name="user_customtext2_visible" value="1" <?php if ($settings['user_customtext2_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext2_mandatory" value="0"/><input type="checkbox" name="user_customtext2_mandatory" value="1" <?php if ($settings['user_customtext2_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext2');?><input type="text" name="user_customtext2_label" value="<?php echo $settings['user_customtext2_label'];?>"/>
	&nbsp; Máscara<input type="text" name="user_customtext2_mask" value="<?php echo $settings['user_customtext2_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext3_visible" value="0"/><input type="checkbox" name="user_customtext3_visible" value="1" <?php if ($settings['user_customtext3_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext3_mandatory" value="0"/><input type="checkbox" name="user_customtext3_mandatory" value="1" <?php if ($settings['user_customtext3_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext3');?><input type="text" name="user_customtext3_label" value="<?php echo $settings['user_customtext3_label'];?>"/>
	&nbsp; Máscara<input type="text" name="user_customtext3_mask" value="<?php echo $settings['user_customtext3_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext4_visible" value="0"/><input type="checkbox" name="user_customtext4_visible" value="1" <?php if ($settings['user_customtext4_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext4_mandatory" value="0"/><input type="checkbox" name="user_customtext4_mandatory" value="1" <?php if ($settings['user_customtext4_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext4');?><input type="text" name="user_customtext4_label" value="<?php echo $settings['user_customtext4_label'];?>"/>
	&nbsp; Máscara<input type="text" name="user_customtext4_mask" value="<?php echo $settings['user_customtext4_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext5_visible" value="0"/><input type="checkbox" name="user_customtext5_visible" value="1" <?php if ($settings['user_customtext5_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext5_mandatory" value="0"/><input type="checkbox" name="user_customtext5_mandatory" value="1" <?php if ($settings['user_customtext5_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext5');?><input type="text" name="user_customtext5_label" value="<?php echo $settings['user_customtext5_label'];?>"/>
	&nbsp; Máscara<input type="text" name="user_customtext5_mask" value="<?php echo $settings['user_customtext5_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customflag1_visible" value="0"/><input type="checkbox" name="user_customflag1_visible" value="1" <?php if ($settings['user_customflag1_visible']) echo "checked=\"checked\"";?> /></td>
	<td></td>
	<td><?php echo $eve->_('user.data.customflag1');?><input type="text" name="user_customflag1_label" value="<?php echo $settings['user_customflag1_label'];?>"/></td></tr>
	<tr>
	<td><input type="hidden" name="user_customflag2_visible" value="0"/><input type="checkbox" name="user_customflag2_visible" value="1" <?php if ($settings['user_customflag2_visible']) echo "checked=\"checked\"";?> /></td>
	<td></td>
	<td><?php echo $eve->_('user.data.customflag2');?><input type="text" name="user_customflag2_label" value="<?php echo $settings['user_customflag2_label'];?>"/></td></tr>
	<tr>
	<td><input type="hidden" name="user_customflag3_visible" value="0"/><input type="checkbox" name="user_customflag3_visible" value="1" <?php if ($settings['user_customflag3_visible']) echo "checked=\"checked\"";?> /></td>
	<td></td>
	<td><?php echo $eve->_('user.data.customflag3');?><input type="text" name="user_customflag3_label" value="<?php echo $settings['user_customflag3_label'];?>"/></td></tr>
	<tr>
	<td><input type="hidden" name="user_customflag4_visible" value="0"/><input type="checkbox" name="user_customflag4_visible" value="1" <?php if ($settings['user_customflag4_visible']) echo "checked=\"checked\"";?> /></td>
	<td></td>
	<td><?php echo $eve->_('user.data.customflag4');?><input type="text" name="user_customflag4_label" value="<?php echo $settings['user_customflag4_label'];?>"/></td></tr>
	<tr>
	<td><input type="hidden" name="user_customflag5_visible" value="0"/><input type="checkbox" name="user_customflag5_visible" value="1" <?php if ($settings['user_customflag5_visible']) echo "checked=\"checked\"";?> /></td>
	<td></td>
	<td><?php echo $eve->_('user.data.customflag5');?> <input type="text" name="user_customflag5_label" value="<?php echo $settings['user_customflag5_label'];?>"/></td></tr>
	</table>

	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
