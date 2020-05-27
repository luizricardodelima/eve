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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Dados do usuário", null);	
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'block_user_form' OR
		`key` = 'user_display_custom_message_on_unlocked_form' OR
		`key` = 'user_custom_message_on_unlocked_form' OR
		`key` = 'user_name_visible' OR
		`key` = 'user_name_mandatory' OR
		`key` = 'user_address_visible' OR
		`key` = 'user_address_mandatory' OR
		`key` = 'user_city_visible' OR
		`key` = 'user_city_mandatory' OR
		`key` = 'user_state_visible' OR
		`key` = 'user_state_mandatory' OR
		`key` = 'user_country_visible' OR
		`key` = 'user_country_mandatory' OR
		`key` = 'user_postalcode_visible' OR
		`key` = 'user_postalcode_mandatory' OR
		`key` = 'user_birthday_visible' OR
		`key` = 'user_birthday_mandatory' OR
		`key` = 'user_gender_visible' OR
		`key` = 'user_gender_mandatory' OR
		`key` = 'user_phone1_visible' OR
		`key` = 'user_phone1_mandatory' OR
		`key` = 'user_phone2_visible' OR
		`key` = 'user_phone2_mandatory' OR
		`key` = 'user_institution_visible' OR
		`key` = 'user_institution_mandatory' OR
		`key` = 'user_category_visible' OR
		`key` = 'user_category_mandatory' OR
		`key` = 'user_customtext1_visible' OR
		`key` = 'user_customtext1_mandatory' OR
		`key` = 'user_customtext1_label' OR
		`key` = 'user_customtext1_mask' OR
		`key` = 'user_customtext2_visible' OR
		`key` = 'user_customtext2_mandatory' OR
		`key` = 'user_customtext2_label' OR
		`key` = 'user_customtext2_mask' OR
		`key` = 'user_customtext3_visible' OR
		`key` = 'user_customtext3_mandatory' OR
		`key` = 'user_customtext3_label' OR
		`key` = 'user_customtext3_mask' OR
		`key` = 'user_customtext4_visible' OR
		`key` = 'user_customtext4_mandatory' OR
		`key` = 'user_customtext4_label' OR
		`key` = 'user_customtext4_mask' OR
		`key` = 'user_customtext5_visible' OR
		`key` = 'user_customtext5_mandatory' OR
		`key` = 'user_customtext5_label' OR
		`key` = 'user_customtext5_mask' OR
		`key` = 'user_customflag1_visible' OR
		`key` = 'user_customflag1_label' OR
		`key` = 'user_customflag2_visible' OR
		`key` = 'user_customflag2_label' OR
		`key` = 'user_customflag3_visible' OR
		`key` = 'user_customflag3_label' OR
		`key` = 'user_customflag4_visible' OR
		`key` = 'user_customflag4_label' OR
		`key` = 'user_customflag5_visible' OR
		`key` = 'user_customflag5_label'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	?>

	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	<div class="section">Bloqueio da ficha de inscrição</div>	
	<table style="width: 100%">
	<tr><td>Bloquear edição da ficha de inscrição</td></tr>
	<tr><td>
		<select name="block_user_form">
			<option value="never" <?php if ($settings['block_user_form'] == 'never') echo "selected=\"selected\"";?>>Nunca</option>
			<option value="after_sending" <?php if ($settings['block_user_form'] == 'after_sending') echo "selected=\"selected\"";?>>Depois do envio</option>
			<option value="after_payment" <?php if ($settings['block_user_form'] == 'after_payment') echo "selected=\"selected\"";?>>Depois do pagamento</option>
		</select>
	</td></tr>
	<tr><td><input type="hidden" name="user_display_custom_message_on_unlocked_form" id="user_display_custom_message_on_unlocked_form" value="0"/> <input type="checkbox" name="user_display_custom_message_on_unlocked_form" value="1" <?php if ($settings['user_display_custom_message_on_unlocked_form']) echo "checked=\"checked\"";?> /><label for="user_display_custom_message_on_unlocked_form">Mensagem personalizada em fichas bloqueadas</label></td></tr>
	<tr><td><textarea class="htmleditor" rows="6" cols="50" name="user_custom_message_on_unlocked_form"><?php echo $settings['user_custom_message_on_unlocked_form'];?></textarea></td></tr>
	</table>

	<div class="section">Campos de usuário</div>
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
	&nbsp; Máscara:<input type="text" name="user_customtext1_mask" value="<?php echo $settings['user_customtext1_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext2_visible" value="0"/><input type="checkbox" name="user_customtext2_visible" value="1" <?php if ($settings['user_customtext2_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext2_mandatory" value="0"/><input type="checkbox" name="user_customtext2_mandatory" value="1" <?php if ($settings['user_customtext2_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext2');?><input type="text" name="user_customtext2_label" value="<?php echo $settings['user_customtext2_label'];?>"/>
	&nbsp; Máscara:<input type="text" name="user_customtext2_mask" value="<?php echo $settings['user_customtext2_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext3_visible" value="0"/><input type="checkbox" name="user_customtext3_visible" value="1" <?php if ($settings['user_customtext3_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext3_mandatory" value="0"/><input type="checkbox" name="user_customtext3_mandatory" value="1" <?php if ($settings['user_customtext3_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext3');?><input type="text" name="user_customtext3_label" value="<?php echo $settings['user_customtext3_label'];?>"/>
	&nbsp; Máscara:<input type="text" name="user_customtext3_mask" value="<?php echo $settings['user_customtext3_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext4_visible" value="0"/><input type="checkbox" name="user_customtext4_visible" value="1" <?php if ($settings['user_customtext4_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext4_mandatory" value="0"/><input type="checkbox" name="user_customtext4_mandatory" value="1" <?php if ($settings['user_customtext4_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext4');?><input type="text" name="user_customtext4_label" value="<?php echo $settings['user_customtext4_label'];?>"/>
	&nbsp; Máscara:<input type="text" name="user_customtext4_mask" value="<?php echo $settings['user_customtext4_mask'];?>"/>
	</td></tr>
	<tr>
	<td><input type="hidden" name="user_customtext5_visible" value="0"/><input type="checkbox" name="user_customtext5_visible" value="1" <?php if ($settings['user_customtext5_visible']) echo "checked=\"checked\"";?> /></td>
	<td><input type="hidden" name="user_customtext5_mandatory" value="0"/><input type="checkbox" name="user_customtext5_mandatory" value="1" <?php if ($settings['user_customtext5_mandatory']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.customtext5');?><input type="text" name="user_customtext5_label" value="<?php echo $settings['user_customtext5_label'];?>"/>
	&nbsp; Máscara:<input type="text" name="user_customtext5_mask" value="<?php echo $settings['user_customtext5_mask'];?>"/>
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
