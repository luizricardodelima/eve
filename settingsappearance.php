<?php
session_start();
require_once 'eve.class.php';
require_once 'evesettingsservice.class.php';

//TODO G11N this page
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
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.settings') => "settings.php",
		$eve->_('settings.appearance') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'show_login_image','show_header_image', 'show_header_text', 'show_content_menu_and_pages',
		'show_footer', 
		'custom_border_bg', 'color_border_bg', 'custom_content_bg', 'color_content_bg', 
		'custom_content_fg', 'color_content_fg', 'custom_breadcrumbs_bg', 'color_breadcrumbs_bg', 
		'custom_breadcrumbs_fg', 'color_breadcrumbs_fg', 'custom_section_bg', 'color_section_bg', 
		'custom_section_fg', 'color_section_fg', 'custom_button_bg', 'color_button_bg', 
		'custom_button_fg', 'color_button_fg'
	);

	?>
	<div class="section"><?php echo $eve->_('settings.appearance');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	<div class="dialog_panel">
	<div class="dialog_section">Lay-out</div>
	<label for="show_login_image"><input type="hidden" name="show_login_image" value="0"/>
	<input  id="show_login_image" type="checkbox" name="show_login_image" value="1" <?php if ($settings['show_login_image']) echo "checked=\"checked\"";?> /> Mostrar imagem de login <button type="button" onclick="window.location.href='imageupload.php?type=login';">Alterar</button></label>
	<label for="show_header_image"><input type="hidden" name="show_header_image" value="0"/>
	<input  id="show_header_image" type="checkbox" name="show_header_image" value="1" <?php if ($settings['show_header_image']) echo "checked=\"checked\"";?> /> Mostrar imagem de cabeçalho <button type="button" onclick="window.location.href='imageupload.php?type=header';">Alterar</button></label>
	<label for="show_header_text"><input type="hidden" name="show_header_text" value="0"/>
	<input  id="show_header_text" type="checkbox" name="show_header_text" value="1" <?php if ($settings['show_header_text']) echo "checked=\"checked\"";?> /> Mostrar texto de cabeçalho</label>
	<label for="show_content_menu_and_pages"><input type="hidden" name="show_content_menu_and_pages" value="0"/>
	<input  id="show_content_menu_and_pages" type="checkbox" name="show_content_menu_and_pages" value="1" <?php if ($settings['show_content_menu_and_pages']) echo "checked=\"checked\"";?> /> Mostrar menu e páginas de conteúdo</label>
	<label for="show_footer"><input type="hidden" name="show_footer" value="0"/>
	<input  id="show_footer" type="checkbox" name="show_footer" value="1" <?php if ($settings['show_footer']) echo "checked=\"checked\"";?> /> Mostrar rodapé</label>
	</div>
	
	<div class="dialog_panel">
	<div class="dialog_section">Cores personalizadas</div>
	
		<div>
		<input type="color" name="color_border_bg" value="<?php echo $settings['color_border_bg'];?>"/>
		<label for="custom_border_bg"><input type="hidden" name="custom_border_bg" value="0"/>
		<input  id="custom_border_bg" type="checkbox" name="custom_border_bg" value="1" <?php if ($settings['custom_border_bg']) echo "checked=\"checked\"";?> />Borda</label>
		</div>
		<div>
		<input type="color" name="color_content_bg" value="<?php echo $settings['color_content_bg'];?>"/>
		<label for="custom_content_bg"><input type="hidden" name="custom_content_bg" value="0"/>
		<input  id="custom_content_bg" type="checkbox" name="custom_content_bg" value="1" <?php if ($settings['custom_content_bg']) echo "checked=\"checked\"";?> />Conteúdo - fundo</label>
		</div>
		<div>
		<input type="color" name="color_content_fg" value="<?php echo $settings['color_content_fg'];?>"/>
		<label for="custom_content_fg"><input type="hidden" name="custom_content_fg" value="0"/>
		<input  id="custom_content_fg" type="checkbox" name="custom_content_fg" value="1" <?php if ($settings['custom_content_fg']) echo "checked=\"checked\"";?> />Conteúdo - texto</label>
		</div>
		<div>
		<input type="color" name="color_breadcrumbs_bg" value="<?php echo $settings['color_breadcrumbs_bg'];?>"/>
		<label for="custom_breadcrumbs_bg"><input type="hidden" name="custom_breadcrumbs_bg" value="0"/>
		<input  id="custom_breadcrumbs_bg" type="checkbox" name="custom_breadcrumbs_bg" value="1" <?php if ($settings['custom_breadcrumbs_bg']) echo "checked=\"checked\"";?> />Breadcrumbs - fundo</label>
		</div>
		<div>
		<input type="color" name="color_breadcrumbs_fg" value="<?php echo $settings['color_breadcrumbs_fg'];?>"/>
		<label for="custom_breadcrumbs_fg"><input type="hidden" name="custom_breadcrumbs_fg" value="0"/>
		<input  id="custom_breadcrumbs_fg" type="checkbox" name="custom_breadcrumbs_fg" value="1" <?php if ($settings['custom_breadcrumbs_fg']) echo "checked=\"checked\"";?> />Breadcrumbs - texto</label>
		</div>
		<div>
		<input type="color" name="color_section_bg" value="<?php echo $settings['color_section_bg'];?>"/>
		<label for="custom_section_bg"><input type="hidden" name="custom_section_bg" value="0"/>
		<input  id="custom_section_bg" type="checkbox" name="custom_section_bg"  value="1" <?php if ($settings['custom_section_bg']) echo "checked=\"checked\"";?> />Seção - fundo</label>
		</div>
		<div>
		<input type="color" name="color_section_fg" value="<?php echo $settings['color_section_fg'];?>"/>
		<label for="custom_section_fg"><input type="hidden" name="custom_section_fg" value="0"/>
		<input  id="custom_section_fg" type="checkbox" name="custom_section_fg" value="1" <?php if ($settings['custom_section_fg']) echo "checked=\"checked\"";?> />Seção - texto</label>
		</div>
		<div>
		<input type="color" name="color_button_bg" value="<?php echo $settings['color_button_bg'];?>"/>
		<label for="custom_button_bg"><input type="hidden" name="custom_button_bg" value="0"/>
		<input  id="custom_button_bg" type="checkbox" name="custom_button_bg" value="1" <?php if ($settings['custom_button_bg']) echo "checked=\"checked\"";?> />Botão - fundo</label>
		</div>
		<div>
		<input type="color" name="color_button_fg" value="<?php echo $settings['color_button_fg'];?>"/>
		<label for="custom_button_fg"><input type="hidden" name="custom_button_fg" value="0"/>
		<input  id="custom_button_fg" type="checkbox" name="custom_button_fg" value="1" <?php if ($settings['custom_button_fg']) echo "checked=\"checked\"";?> />Botão - texto</label>
		</div>
	</div>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
