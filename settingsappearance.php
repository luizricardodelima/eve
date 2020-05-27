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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Aparência", null);
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'show_header_image' OR
		`key` = 'show_header_text' OR
		`key` = 'show_content_menu_and_pages' OR
		`key` = 'show_footer' OR
		`key` = 'custom_border_bg' OR
		`key` = 'color_border_bg' OR
		`key` = 'custom_content_bg' OR
		`key` = 'color_content_bg' OR
		`key` = 'custom_content_fg' OR
		`key` = 'color_content_fg' OR
		`key` = 'custom_breadcrumbs_bg' OR
		`key` = 'color_breadcrumbs_bg' OR
		`key` = 'custom_breadcrumbs_fg' OR
		`key` = 'color_breadcrumbs_fg' OR
		`key` = 'custom_section_bg' OR
		`key` = 'color_section_bg' OR
		`key` = 'custom_section_fg' OR
		`key` = 'color_section_fg' OR
		`key` = 'custom_button_bg' OR
		`key` = 'color_button_bg' OR
		`key` = 'custom_button_fg' OR
		`key` = 'color_button_fg'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	
	?>
	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	<div class="section">Lay-out</div>
	<table style="width: 100%">
	<tr><td><input type="hidden" name="show_header_image" value="0"/> <input type="checkbox" name="show_header_image" value="1" <?php if ($settings['show_header_image']) echo "checked=\"checked\"";?> /> Mostrar imagem de cabeçalho <button type="button" onclick="window.location.href='imageupload.php?type=header';">Alterar</button></td></tr>
	<tr><td><input type="hidden" name="show_header_text" value="0"/> <input type="checkbox" name="show_header_text" value="1" <?php if ($settings['show_header_text']) echo "checked=\"checked\"";?> /> Mostrar texto de cabeçalho</td></tr>
	<tr><td><input type="hidden" name="show_content_menu_and_pages" value="0"/> <input type="checkbox" name="show_content_menu_and_pages" value="1" <?php if ($settings['show_content_menu_and_pages']) echo "checked=\"checked\"";?> /> Mostrar menu e páginas de conteúdo</td></tr>
	<tr><td><input type="hidden" name="show_footer" value="0"/> <input type="checkbox" name="show_footer" value="1" <?php if ($settings['show_footer']) echo "checked=\"checked\"";?> /> Mostrar rodapé</td></tr>
	</table>
	
	<div class="section">Cores personalizadas</div>
	<table>
	<tr>
		<td><input type="hidden" name="custom_border_bg" value="0"/> <input type="checkbox" name="custom_border_bg" id="custom_border_bg_cbx" value="1" <?php if ($settings['custom_border_bg']) echo "checked=\"checked\"";?> /><label for="custom_border_bg_cbx">Borda</label></td>
		<td><input type="color" name="color_border_bg" value="<?php echo $settings['color_border_bg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_content_bg" value="0"/> <input type="checkbox" name="custom_content_bg" id="custom_content_bg_cbx" value="1" <?php if ($settings['custom_content_bg']) echo "checked=\"checked\"";?> /><label for="custom_content_bg_cbx">Conteúdo - fundo</label></td>
		<td><input type="color" name="color_content_bg" value="<?php echo $settings['color_content_bg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_content_fg" value="0"/> <input type="checkbox" name="custom_content_fg" id="custom_content_fg_cbx" value="1" <?php if ($settings['custom_content_fg']) echo "checked=\"checked\"";?> /><label for="custom_content_fg_cbx">Conteúdo - texto</label></td>
		<td><input type="color" name="color_content_fg" value="<?php echo $settings['color_content_fg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_breadcrumbs_bg" value="0"/> <input type="checkbox" name="custom_breadcrumbs_bg" id="custom_breadcrumbs_bg_cbx" value="1" <?php if ($settings['custom_breadcrumbs_bg']) echo "checked=\"checked\"";?> /><label for="custom_breadcrumbs_bg_cbx">Breadcrumbs - fundo</label></td>
		<td><input type="color" name="color_breadcrumbs_bg" value="<?php echo $settings['color_breadcrumbs_bg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_breadcrumbs_fg" value="0"/> <input type="checkbox" name="custom_breadcrumbs_fg" id="custom_breadcrumbs_fg_cbx" value="1" <?php if ($settings['custom_breadcrumbs_fg']) echo "checked=\"checked\"";?> /><label for="custom_breadcrumbs_fg_cbx">Breadcrumbs - texto</label></td>
		<td><input type="color" name="color_breadcrumbs_fg" value="<?php echo $settings['color_breadcrumbs_fg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_section_bg" value="0"/> <input type="checkbox" name="custom_section_bg" id="custom_section_bg_cbx" value="1" <?php if ($settings['custom_section_bg']) echo "checked=\"checked\"";?> /><label for="custom_section_bg_cbx">Seção - fundo</label></td>
		<td><input type="color" name="color_section_bg" value="<?php echo $settings['color_section_bg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_section_fg" value="0"/> <input type="checkbox" name="custom_section_fg" id="custom_section_fg_cbx" value="1" <?php if ($settings['custom_section_fg']) echo "checked=\"checked\"";?> /><label for="custom_section_fg_cbx">Seção - texto</label></td>
		<td><input type="color" name="color_section_fg" value="<?php echo $settings['color_section_fg'];?>"/></td>
	</tr>
	<tr>
		<td><input type="hidden" name="custom_button_bg" value="0"/> <input type="checkbox" name="custom_button_bg" id="custom_button_bg_cbx" value="1" <?php if ($settings['custom_button_bg']) echo "checked=\"checked\"";?> /><label for="custom_button_bg_cbx">Botão - fundo</label></td>
		<td><input type="color" name="color_button_bg" value="<?php echo $settings['color_button_bg'];?>"/></td>

	</tr>
	<tr>
		<td><input type="hidden" name="custom_button_fg" value="0"/> <input type="checkbox" name="custom_button_fg" id="custom_button_fg_cbx" value="1" <?php if ($settings['custom_button_fg']) echo "checked=\"checked\"";?> /><label for="custom_button_fg_cbx">Botão - texto</label></td>
		<td><input type="color" name="color_button_fg" value="<?php echo $settings['color_button_fg'];?>"/></td>
	</tr>
	</table>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
