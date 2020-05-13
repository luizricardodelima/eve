<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.php';

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
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Credenciais", null);

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	// Retrieving settings from database.
	$settings = array();
	$result = $eve->mysqli->query
	("
		SELECT * FROM `{$eve->DBPref}settings` WHERE
		`key` = 'credential_page_size' OR
		`key` = 'credential_page_orientation' OR
		`key` = 'credential_page_top_margin' OR
		`key` = 'credential_page_left_margin' OR
		`key` = 'credential_cell_width' OR
		`key` = 'credential_cell_height' OR
		`key` = 'credential_cells_per_line' OR
		`key` = 'credential_lines_per_page' OR
		`key` = 'credential_border' OR
		`key` = 'credential_border_color' OR
		`key` = 'credential_bgimage' OR
		`key` = 'credential_textbox' OR
		`key` = 'credential_textbox_x' OR
		`key` = 'credential_textbox_y' OR
		`key` = 'credential_textbox_w' OR
		`key` = 'credential_textbox_h' OR
		`key` = 'credential_textbox_fontsize' OR
		`key` = 'credential_textbox_content' OR
		`key` = 'credential_countryflag' OR
		`key` = 'credential_countryflag_x' OR
		`key` = 'credential_countryflag_y' OR
		`key` = 'credential_countryflag_w' OR
		`key` = 'credential_countryflag_h'
		;
	");
	while ($row = $result->fetch_assoc()) $settings[$row['key']] = $row['value'];
	
	?>
	<div class="section">
	<button type="button" onclick="document.forms['settings_form'].submit();"/>Salvar</button>
	<button type="button" onclick="test_credentials();"/>Testar</button>
	</div>

	<form id="settings_form" method="post">
	<div class="section">Página</div>

	<label for="credential_page_size_cbx">Tamanho da página</label>
	<select id="credential_page_size_cbx" name="credential_page_size">
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
	</select><br/>

	<label for="credential_page_orientation_cbx">Orientação</label>
	<select id="credential_page_orientation_cbx" name="credential_page_orientation">
	<?php
	// TODO The properties are borrowed from eve certification service!
	foreach ($eveCertificationService->certificationmodel_pageorientations() as $page_orientation)
	{
		echo "<option value=\"$page_orientation\"";
		if ($settings['credential_page_orientation'] == $page_orientation) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pageorientation.'.$page_orientation)."</option>";
	}
	?>
	</select><br/>
	
	<label for="credential_page_top_margin_lbl">Margem superior (mm)</label>
	<input id="credential_page_top_margin_lbl" name="credential_page_top_margin" value="<?php echo $settings['credential_page_top_margin'];?>" type="number" min="0" step="0.1"/>
	<br/>

	<label for="credential_page_left_margin_lbl">Margem esquerda (mm)</label>
	<input id="credential_page_left_margin_lbl" name="credential_page_left_margin" value="<?php echo $settings['credential_page_left_margin'];?>" type="number" min="0" step="0.1"/>
	<br/>

	<div class="section">Credenciais - disposição</div>

	<label for="credential_cell_width_lbl">Largura da credencial (mm)</label>
	<input id="credential_cell_width_lbl" name="credential_cell_width" value="<?php echo $settings['credential_cell_width'];?>" type="number" min="0.1" step="0.1"/>
	<br/>

	<label for="credential_cell_height_lbl">Altura da credencial (mm)</label>
	<input id="credential_cell_height_lbl" name="credential_cell_height" value="<?php echo $settings['credential_cell_height'];?>" type="number" min="0.1" step="0.1"/>
	<br/>

	<label for="credential_cells_per_line_ipt">Credenciais por linha</label>
	<input id="credential_cells_per_line_ipt" name="credential_cells_per_line" value="<?php echo $settings['credential_cells_per_line'];?>" type="number" min="1" step="1"/>
	<br/>

	<label for="credential_lines_per_page_ipt">Linhas por página</label>
	<input id="credential_lines_per_page_ipt" name="credential_lines_per_page" value="<?php echo $settings['credential_lines_per_page'];?>" type="number" min="1" step="1"/>
	<br/>

	<script>
	function credential_content_help() {
		window.alert('Caixa de texto - conteúdo - variáveis permitidas:'
				+'\n$user[name] - Nome do usuário (mostrado sempre em caixa alta)');
	}
	</script>

	<div class="section">Credenciais - conteúdo <button type="button" onclick="credential_content_help()">?</button></div>

	<label for="credential_border_ipt"><input type="hidden" name="credential_border" value="0"/> <input type="checkbox" name="credential_border" id="credential_border_ipt" value="1" <?php if ($settings['credential_border']) echo "checked=\"checked\"";?> /> Borda</label>
	<input type="color" name="credential_border_color" id="credential_border_color_ipt" value="<?php echo $settings['credential_border_color'];?>"/><br/>

	<label for="credential_bgimage_ipt"><input type="hidden" name="credential_bgimage" value="0"/> <input type="checkbox" name="credential_bgimage" id="credential_bgimage_ipt" value="1" <?php if ($settings['credential_bgimage']) echo "checked=\"checked\"";?> /> Imagem de fundo (<a href="imageupload.php?type=credential">Alterar</a>)</label><br/>

	<label for="credential_textbox_ipt"><input type="hidden" name="credential_textbox" value="0"/> <input type="checkbox" name="credential_textbox" id="credential_textbox_ipt" value="1" <?php if ($settings['credential_textbox']) echo "checked=\"checked\"";?> /> Caixa de texto</label><br/>

	<label for="credential_textbox_x_ipt">Caixa de Texto - pos. x (mm)</label>
	<input id="credential_textbox_x_ipt" name="credential_textbox_x" value="<?php echo $settings['credential_textbox_x'];?>" type="number" min="0" step="0.1"/>
	<br/>

	<label for="credential_textbox_y_ipt">Caixa de Texto - pos. y (mm)</label>
	<input id="credential_textbox_y_ipt" name="credential_textbox_y" value="<?php echo $settings['credential_textbox_y'];?>" type="number" min="0" step="0.1"/>
	<br/>

	<label for="credential_textbox_w_ipt">Caixa de Texto - largura (mm)</label>
	<input id="credential_textbox_w_ipt" name="credential_textbox_w" value="<?php echo $settings['credential_textbox_w'];?>" type="number" min="0.1" step="0.1"/>
	<br/>

	<label for="credential_textbox_h_ipt">Caixa de Texto - altura da linha(mm)</label>
	<input id="credential_textbox_h_ipt" name="credential_textbox_h" value="<?php echo $settings['credential_textbox_h'];?>" type="number" min="0.1" step="0.1"/>
	<br/>
	
	<label for="credential_textbox_fontsize_ipt">Caixa de Texto - tamanho da fonte (pt)</label>
	<input id="credential_textbox_fontsize_ipt" name="credential_textbox_fontsize" value="<?php echo $settings['credential_textbox_fontsize'];?>" type="number" min="1" step="1"/>
	<br/>

	<label for="credential_textbox_content_ipt">Caixa de Texto - conteúdo</label>
	<input id="credential_textbox_content_ipt" name="credential_textbox_content" value="<?php echo $settings['credential_textbox_content'];?>" type="text"/>
	<br/>

	<label for="credential_countryflag_ipt"><input type="hidden" name="credential_countryflag" value="0"/> <input type="checkbox" name="credential_countryflag" id="credential_countryflag_ipt" value="1" <?php if ($settings['credential_countryflag']) echo "checked=\"checked\"";?> /> Bandeira do país</label><br/>

	<label for="credential_countryflag_x_ipt">Bandeira do país- pos. x (mm)</label>
	<input id="credential_countryflag_x_ipt" name="credential_countryflag_x" value="<?php echo $settings['credential_countryflag_x'];?>" type="number" min="0" step="0.1"/>
	<br/>

	<label for="credential_countryflag_y_ipt">Bandeira do país - pos. y (mm)</label>
	<input id="credential_countryflag_y_ipt" name="credential_countryflag_y" value="<?php echo $settings['credential_countryflag_y'];?>" type="number" min="0" step="0.1"/>
	<br/>

	<label for="credential_countryflag_w_ipt">Bandeira do país - largura (mm)</label>
	<input id="credential_countryflag_w_ipt" name="credential_countryflag_w" value="<?php echo $settings['credential_countryflag_w'];?>" type="number" min="0.1" step="0.1"/>
	<br/>

	<label for="credential_countryflag_h_ipt">Bandeira do país - altura (mm)</label>
	<input id="credential_countryflag_h_ipt" name="credential_countryflag_h" value="<?php echo $settings['credential_countryflag_h'];?>" type="number" min="0.1" step="0.1"/>
	<br/>

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
