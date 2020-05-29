<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.php';
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
	$eve->output_wysiwig_editor_code();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Credenciais", null);

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	$settings = $eveSettingsService->settings_get
	(
		'credential_page_size', 'credential_page_orientation', 'credential_page_top_margin', 
		'credential_page_left_margin', 'credential_cell_width', 'credential_cell_height', 
		'credential_cells_per_line', 'credential_lines_per_page', 'credential_border', 
		'credential_border_color', 'credential_bgimage', 'credential_textbox', 
		'credential_textbox_x', 'credential_textbox_y',	'credential_textbox_w', 
		'credential_textbox_h', 'credential_textbox_fontsize', 'credential_textbox_content', 
		'credential_countryflag', 'credential_countryflag_x', 'credential_countryflag_y', 
		'credential_countryflag_w', 'credential_countryflag_h'
	);
	
	?>
	<div class="section">Credenciais
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" onclick="test_credentials();"><?php echo $eve->_('common.action.test');?></button>
	</div>

	<form id="settings_form" method="post">
	
	<div class="dialog_panel">
	<div class="dialog_section">Página</div>

	<label for="credential_page_size">Tamanho da página</label>
	<select id="credential_page_size" name="credential_page_size">
	<?php
	$eveCertificationService = new EveCertificationService($eve);
	// TODO The properties are borrowed from eve certification service!
	foreach($eveCertificationService->certificationmodel_pagesizes() as $page_size)
	{
		echo "<option value=\"$page_size\"";
		if ($settings['credential_page_size'] == $page_size) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pagesize.'.$page_size)."</option>";
	}
	?>
	</select>

	<label for="credential_page_orientation">Orientação</label>
	<select id="credential_page_orientation" name="credential_page_orientation">
	<?php
	// TODO The properties are borrowed from eve certification service!
	foreach ($eveCertificationService->certificationmodel_pageorientations() as $page_orientation)
	{
		echo "<option value=\"$page_orientation\"";
		if ($settings['credential_page_orientation'] == $page_orientation) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pageorientation.'.$page_orientation)."</option>";
	}
	?>
	</select>
	
	<label for="credential_page_top_margin_lbl">Margem superior (mm)</label>
	<input id="credential_page_top_margin_lbl" name="credential_page_top_margin" value="<?php echo $settings['credential_page_top_margin'];?>" type="number" min="0" step="0.1"/>
	

	<label for="credential_page_left_margin_lbl">Margem esquerda (mm)</label>
	<input id="credential_page_left_margin_lbl" name="credential_page_left_margin" value="<?php echo $settings['credential_page_left_margin'];?>" type="number" min="0" step="0.1"/>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">Disposição</div>

	<label for="credential_cell_width_lbl">Largura da credencial (mm)</label>
	<input id="credential_cell_width_lbl" name="credential_cell_width" value="<?php echo $settings['credential_cell_width'];?>" type="number" min="0.1" step="0.1"/>
	

	<label for="credential_cell_height_lbl">Altura da credencial (mm)</label>
	<input id="credential_cell_height_lbl" name="credential_cell_height" value="<?php echo $settings['credential_cell_height'];?>" type="number" min="0.1" step="0.1"/>
	

	<label for="credential_cells_per_line_ipt">Credenciais por linha</label>
	<input id="credential_cells_per_line_ipt" name="credential_cells_per_line" value="<?php echo $settings['credential_cells_per_line'];?>" type="number" min="1" step="1"/>
	

	<label for="credential_lines_per_page_ipt">Linhas por página</label>
	<input id="credential_lines_per_page_ipt" name="credential_lines_per_page" value="<?php echo $settings['credential_lines_per_page'];?>" type="number" min="1" step="1"/>
	

	<script>
	function credential_content_help() {
		window.alert('Caixa de texto - conteúdo - variáveis permitidas:'
				+'\n$user[name] - Nome do usuário (mostrado sempre em caixa alta)');
	}
	</script>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">Decoração</div>

	<label for="credential_border_ipt"><input type="hidden" name="credential_border" value="0"/>
	<input  id="credential_border_ipt" type="checkbox" name="credential_border" value="1" <?php if ($settings['credential_border']) echo "checked=\"checked\"";?> />
	Borda
	<input type="color" name="credential_border_color" id="credential_border_color_ipt" value="<?php echo $settings['credential_border_color'];?>"/>
	</label>

	<label for="credential_bgimage_ipt"><input type="hidden" name="credential_bgimage" value="0"/>
	<input  id="credential_bgimage_ipt" type="checkbox" name="credential_bgimage" value="1" <?php if ($settings['credential_bgimage']) echo "checked=\"checked\"";?> />
	Imagem de fundo (<a href="imageupload.php?type=credential">Alterar</a>)</label>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">
	<label for="credential_textbox_ipt"><input type="hidden" name="credential_textbox" value="0"/>
	<input  id="credential_textbox_ipt" type="checkbox" name="credential_textbox" value="1" <?php if ($settings['credential_textbox']) echo "checked=\"checked\"";?> />
	Caixa de texto</label>
	</div>

	<label for="credential_textbox_x_ipt">Caixa de Texto - pos. x (mm)</label>
	<input id="credential_textbox_x_ipt" name="credential_textbox_x" value="<?php echo $settings['credential_textbox_x'];?>" type="number" min="0" step="0.1"/>
	

	<label for="credential_textbox_y_ipt">Caixa de Texto - pos. y (mm)</label>
	<input id="credential_textbox_y_ipt" name="credential_textbox_y" value="<?php echo $settings['credential_textbox_y'];?>" type="number" min="0" step="0.1"/>
	

	<label for="credential_textbox_w_ipt">Caixa de Texto - largura (mm)</label>
	<input id="credential_textbox_w_ipt" name="credential_textbox_w" value="<?php echo $settings['credential_textbox_w'];?>" type="number" min="0.1" step="0.1"/>
	

	<label for="credential_textbox_h_ipt">Caixa de Texto - altura da linha(mm)</label>
	<input id="credential_textbox_h_ipt" name="credential_textbox_h" value="<?php echo $settings['credential_textbox_h'];?>" type="number" min="0.1" step="0.1"/>
	
	
	<label for="credential_textbox_fontsize_ipt">Caixa de Texto - tamanho da fonte (pt)</label>
	<input id="credential_textbox_fontsize_ipt" name="credential_textbox_fontsize" value="<?php echo $settings['credential_textbox_fontsize'];?>" type="number" min="1" step="1"/>
	

	<label for="credential_textbox_content_ipt">Caixa de Texto - conteúdo <button type="button" onclick="credential_content_help()">?</button></label>
	<input id="credential_textbox_content_ipt" name="credential_textbox_content" value="<?php echo $settings['credential_textbox_content'];?>" type="text"/>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section">
	<label for="credential_countryflag_ipt"><input type="hidden" name="credential_countryflag" value="0"/>
	<input  id="credential_countryflag_ipt" type="checkbox" name="credential_countryflag" value="1" <?php if ($settings['credential_countryflag']) echo "checked=\"checked\"";?> />
	Bandeira do país</label>
	</div>

	<label for="credential_countryflag_x_ipt">Bandeira do país- pos. x (mm)</label>
	<input id="credential_countryflag_x_ipt" name="credential_countryflag_x" value="<?php echo $settings['credential_countryflag_x'];?>" type="number" min="0" step="0.1"/>
	

	<label for="credential_countryflag_y_ipt">Bandeira do país - pos. y (mm)</label>
	<input id="credential_countryflag_y_ipt" name="credential_countryflag_y" value="<?php echo $settings['credential_countryflag_y'];?>" type="number" min="0" step="0.1"/>
	

	<label for="credential_countryflag_w_ipt">Bandeira do país - largura (mm)</label>
	<input id="credential_countryflag_w_ipt" name="credential_countryflag_w" value="<?php echo $settings['credential_countryflag_w'];?>" type="number" min="0.1" step="0.1"/>
	

	<label for="credential_countryflag_h_ipt">Bandeira do país - altura (mm)</label>
	<input id="credential_countryflag_h_ipt" name="credential_countryflag_h" value="<?php echo $settings['credential_countryflag_h'];?>" type="number" min="0.1" step="0.1"/>
	</div>

	</form>

	<form id="test_form" method="post" action="credential.php"></form>
	<script>
	function test_credentials() {
		var container = document.getElementById("test_form");
		var cells_per_line = document.getElementById("credential_cells_per_line_ipt").value;
		var lines_per_page = document.getElementById("credential_lines_per_page_ipt").value;
		for (i = 0; i < cells_per_line * lines_per_page; i++)
		{
			var input = document.createElement("input");
	                input.type = "hidden";
        	        input.name = "screenname[" + i + "]";
			input.value = "<?php echo $_SESSION['screenname'];?>";
        	        container.appendChild(input);
		}
		document.forms['test_form'].submit();
	}
	</script>
	<?php

	$eve->output_html_footer();
}
?>
