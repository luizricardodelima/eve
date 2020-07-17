<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';

$eve = new Eve();

// TODO #4 2 New variables for certification model: Text alignment and text font (use FPDFs fonts)

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
else if (!isset($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else if (!is_numeric($_GET['id'])) 
{
	$eve->output_error_page('common.message.invalid.parameter'); // Blocking sql injections by accepting numbers only
}
else if (!$eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certificationdef` WHERE `id` = {$_GET['id']};")->num_rows)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else
{
	// In this block we're sure that user has admin privileges and that $_GET['id'] exists and is valid.
	$eveCertificationService = new EveCertificationService($eve);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Modelos de certificado", "certificationdefs.php","Modelo de certificado (ID: {$_GET['id']})", null);
	$eve->output_wysiwig_editor_code();

	$data = array();
	$validation_errors = array();
		
	if (empty($_POST))
	{
		//No POST data. Retrieving data from database.
		//TODO use service!
		$result = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}certificationdef` WHERE `id`={$_GET['id']};");
		$row = $result->fetch_assoc();
		foreach ($row as $column => $value) 
		{
			$data[$column] = $value;
		} 
	}
	else
	{
		// There is POST data. There is no need to retrieve data from database.
		// POST data will be validadated. If valid, they will be saved on db.
		foreach ($_POST as $column => $value) {$data[$column] = $value;}
				
		// Validation
		if (!is_numeric($data['topmargin'])) $validation_errors[] = "Margem superior não é um número válido";
		if (!is_numeric($data['leftmargin'])) $validation_errors[] = "Margem esquerda não é um número válido";
		if (!is_numeric($data['rightmargin'])) $validation_errors[] = "Margem direita não é um número válido";

		if (empty($validation_errors))
		{
			foreach ($data as $column => $value)
			{
				$value = $eve->mysqli->real_escape_string($value);
				$eve->mysqli->query("UPDATE `{$eve->DBPref}certificationdef` SET `$column` = '$value' WHERE `id` = {$_GET['id']};");
			}
			$eve->output_success_message("Dados salvos com sucesso.");
		}
		else
		{
			$eve->output_error_list_message($validation_errors);
		}
	}

	?>
	<script>
	function certification_help() {
		window.alert(	'O conteúdo é codificado como um ARRAY JSON que recebe os seguintes objetos\n\n'+

				'- text: mostra um texto fixo.\n'+
				'Exemplo {"type": "text", "value": "TEXTO A SER INSERIDO"}\n\n'+

				'- variable: recupera um valor de uma variável da submissão ou do usuário '+
				'referente ao certificado. caso o certificado seja de usuário e for tentado '+
				'mostrar uma variavel de submissão, retorna texto vazio.\n'+
				'Ex: {"type": "variable", "entity": "user", "parameter" : "name", "uppercase" : "true"}\n'+
				'Ex: {"type": "variable", "entity": "submission-content", "parameter" : "1"}\n'+
				'Ex: {"type": "variable", "entity": "submission-content", "parameter" : "1-1"}\n'+
				'Parâmetros para "user": admin, locked_form, name, address, city, state, country,\n'+
				'postalcode, birthday, gender, phone1, phone2, instituition, \n'+
				'customtext1, customtext2, customtext3, customtext4, customtext5, customflag1,\n'+
				'customflag2, customflag3, customflag4, customflag5, note.\n'+
				'Parâmetros para "submission-content": o número indicando a posição.\n'+
				'Se for array, separa-se as dimensões com -\n'+
				'Caso haja o parametro uppercase, coloca o texto da variável em maiúsculas.\n\n ' +

				'- list: mostra uma lista de objetos dos tipos acima, separados '+
				'por vírgula (se mais de um) e por "e" antes do último. Só são listados objetos '+
				'não vazios\n'+
				'Ex: {"type": "list", "content": [{"type": "variable", "entity":\n'+
				'"submission-content", "parameter" : "1"},{"type": "variable", "entity":\n'+
				'"submission-content", "parameter" : "2"}]}'
				);
	}
	</script>

	<div class="section">
	<button type="button" onclick="document.forms['cdef_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" onclick="window.location.href = 'certification.php?templateid=<?php echo $_GET['id'];?>';"><?php echo $eve->_('common.action.test');?></button>
	</div>

	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" id="cdef_form">
	<div class="section"><?php echo $eve->_('certificationmodel.section.general');?></div>
	<div class="dialog_panel">
	<label for="certificationtemplate_id_ipt"><?php echo $eve->_('certificationmodel.id');?></label>
	<input id="certificationtemplate_id_ipt" type="text" value="<?php echo $_GET['id'];?>" disabled="disabled"/>
	
	<label for="certificationtemplate_type_ipt"><?php echo $eve->_('certificationmodel.type');?></label>
	<select id="certificationtemplate_type_ipt" name="type">
	<?php 
	foreach($eveCertificationService->certificationmodel_types() as $type)
	{	
		echo "<option value=\"$type\"";
		if ($data['type'] == $type) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.type.'.$type)."</option>";
	}
	?>
	</select>

	<label for="certificationtemplate_name_ipt"><?php echo $eve->_('certificationmodel.name');?></label>
	<input id="certificationtemplate_name_ipt" type="text" name="name" value="<?php echo $data['name'];?>"/>
	</div>	

	<div class="section"><?php echo $eve->_('certificationmodel.section.page');?></div>
	<div class="dialog_panel">
	<label for="certificationtemplate_pagesize_ipt"><?php echo $eve->_('certificationmodel.pagesize');?></label>
	<select id="certificationtemplate_pagesize_ipt" name="pagesize">
	<?php
	foreach($eveCertificationService->certificationmodel_pagesizes() as $pagesize)
	{
		echo "<option value=\"$pagesize\"";
		if ($data['pagesize'] == $pagesize) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pagesize.'.$pagesize)."</option>";
	}
	?>
	</select>

	<label for="certificationtemplate_pageorientation_ipt"><?php echo $eve->_('certificationmodel.pageorientation');?></label>
	<select id="certificationtemplate_pageorientation_ipt" name="pageorientation">
	<?php
	foreach ($eveCertificationService->certificationmodel_pageorientations() as $pageorientation)
	{
		echo "<option value=\"$pageorientation\"";
		if ($data['pageorientation'] == $pageorientation) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pageorientation.'.$pageorientation)."</option>";
	}
	?>
	</select>

	<label for="certificationtemplate_backgroundimage_ipt"><?php echo $eve->_('certificationmodel.backgroundimage');?></label>
	<select id="certificationtemplate_backgroundimage_ipt" name="backgroundimage">
	<?php
	echo "<option value=\"\">{$eve->_('common.select.null')}</option>";
	$files = scandir('upload/certification/');
	unset($files[1]); // The first two results are the directories . and ..
	unset($files[0]); // Removing them.
	foreach($files as $file)
	{	
		echo "<option value=\"$file\"";
		if ($data['backgroundimage'] == $file) echo " selected=\"selected\"";
		echo ">$file</option>";
	}
	?>
	</select>
	</div>

	<div class="section"><?php echo $eve->_('certificationmodel.section.text');?></div>
	<div class="dialog_panel">
	<label for="certificationtemplate_text_ipt"><?php echo $eve->_('certificationmodel.text');?><img src="style/icons/help.png" type="button" onclick="certification_help()"/></label>
	<textarea id="certificationtemplate_text_ipt" name="text" rows="7"><?php echo $data['text'];?></textarea>

	<label for="certificationtemplate_topmargin_ipt"><?php echo $eve->_('certificationmodel.topmargin');?></label>
	<input id="certificationtemplate_topmargin_ipt" type="number" min="0" step="0.1" name="topmargin" value="<?php echo $data['topmargin'];?>"/>

	<label for="certificationtemplate_leftmargin_ipt"><?php echo $eve->_('certificationmodel.leftmargin');?></label>
	<input id="certificationtemplate_leftmargin_ipt" type="number" min="0" step="0.1" name="leftmargin" value="<?php echo $data['leftmargin'];?>"/>

	<label for="certificationtemplate_rightmargin_ipt"><?php echo $eve->_('certificationmodel.rightmargin');?></label>
	<input id="certificationtemplate_rightmargin_ipt" type="number" min="0" step="0.1" name="rightmargin" value="<?php echo $data['rightmargin'];?>"/>

	<label for="certificationtemplate_text_lineheight_ipt"><?php echo $eve->_('certificationmodel.textlineheight');?></label>
	<input id="certificationtemplate_text_lineheight_ipt" type="number" min="0" step="0.1" name="text_lineheight" value="<?php echo $data['text_lineheight'];?>"/>

	<label for="certificationtemplate_text_fontsize_ipt"><?php echo $eve->_('certificationmodel.textfontsize');?></label>
	<input id="certificationtemplate_text_fontsize_ipt" type="number" min="1" step="1" name="text_fontsize" value="<?php echo $data['text_fontsize'];?>"/>
	</div>

	<div class="section"><?php echo $eve->_('certificationmodel.section.openermsg');?></div>
	<div class="dialog_panel">
	<label for="certificationtemplate_hasopenermsg_ipt">
	<input type="hidden" name="hasopenermsg" value="0"/>
	<input type="checkbox" name="hasopenermsg" id="certificationtemplate_hasopenermsg_ipt" value="1" <?php if ($data['hasopenermsg']) echo "checked=\"checked\"";?> />
	<?php echo $eve->_('certificationmodel.openermsg');?>
	</label>
	<textarea class="htmleditor" rows="6" cols="50" name="openermsg"><?php echo $data['openermsg'];?></textarea>
	</div>
	</form>
	
	<?php
	$eve->output_html_footer();
}?>
