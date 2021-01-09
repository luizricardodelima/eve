<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);
$data = isset($_GET['id']) ? $eveCertificationService->certificationmodel_get($_GET['id']) : null;

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
// Parameter verification
else if ($data == null)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// From now on we assume the id passed is valid. If there is post variables, make the operation
if (!empty($_POST))
{
	// The only currently existing operation is save.
	$msg = $eveCertificationService->certificationmodel_save($_POST);
	$eve->output_redirect_page("certification_model.php?id={$_GET['id']}&msg=$msg");
}
// If there is no post variables, display the regular page.
else
{
	$eve->output_html_header(['wysiwyg-editor']);
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php", 
		$eve->_('userarea.option.admin.certificationtemplates') => "certification_models.php",
		$eve->_('certificationmodel')." ({$_GET['id']})" => null
	]);

	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	?>
	<!-- Help viewer -->
	<div id="viewer" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
	<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('viewer').style.display = 'none';"> X </button>
	<div id="viewer_content" style="padding: 20px; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;">
	<?php echo $eve->_('certificationmodel.text.help'); ?>
	</div></div></div>

	<div class="section">
	<?php echo $eve->_('certificationmodel')." ({$_GET['id']})";?>
	<button type="button" onclick="document.forms['certification_model_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" onclick="window.location.href = 'certification.php?model_id=<?php echo $_GET['id'];?>';"><?php echo $eve->_('common.action.test');?></button>
	</div>

	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" id="certification_model_form">
	
	<div class="dialog_panel">
	<div class="dialog_section"><?php echo $eve->_('certificationmodel.section.general');?></div>
	<input type="hidden" name="id" value="<?php echo $_GET['id'];?>"/>
	
	<label for="type"><?php echo $eve->_('certificationmodel.type');?></label>
	<select id="type" name="type">
	<?php 
	foreach($eveCertificationService->certificationmodel_types() as $type)
	{	
		echo "<option value=\"$type\"";
		if ($data['type'] == $type) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.type.'.$type)."</option>";
	}
	?>
	</select>

	<label for="name"><?php echo $eve->_('certificationmodel.name');?></label>
	<input id="name" type="text" name="name" value="<?php echo $data['name'];?>"/>
	</div>	

	<div class="dialog_panel">
	<div class="dialog_section"><?php echo $eve->_('certificationmodel.section.page');?></div>
	<label for="pagesize"><?php echo $eve->_('certificationmodel.pagesize');?></label>
	<select id="pagesize" name="pagesize">
	<?php
	foreach($eveCertificationService->certificationmodel_pagesizes() as $pagesize)
	{
		echo "<option value=\"$pagesize\"";
		if ($data['pagesize'] == $pagesize) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pagesize.'.$pagesize)."</option>";
	}
	?>
	</select>

	<label for="pageorientation"><?php echo $eve->_('certificationmodel.pageorientation');?></label>
	<select id="pageorientation" name="pageorientation">
	<?php
	foreach ($eveCertificationService->certificationmodel_pageorientations() as $pageorientation)
	{
		echo "<option value=\"$pageorientation\"";
		if ($data['pageorientation'] == $pageorientation) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.pageorientation.'.$pageorientation)."</option>";
	}
	?>
	</select>

	<label for="backgroundimage"><?php echo $eve->_('certificationmodel.backgroundimage');?></label>
	<select id="backgroundimage" name="backgroundimage">
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

	<div class="dialog_panel">
	<div class="dialog_section"><?php echo $eve->_('certificationmodel.section.text');?></div>
	<label for="text"><?php echo $eve->_('certificationmodel.text');?><img src="style/icons/help.png" type="button" onclick="document.getElementById('viewer').style.display = 'block'"/></label>
	<textarea id="text" name="text" rows="7"><?php echo $data['text'];?></textarea>

	<label for="topmargin"><?php echo $eve->_('certificationmodel.topmargin');?></label>
	<input id="topmargin" type="number" min="0" step="0.1" name="topmargin" value="<?php echo $data['topmargin'];?>"/>

	<label for="leftmargin"><?php echo $eve->_('certificationmodel.leftmargin');?></label>
	<input id="leftmargin" type="number" min="0" step="0.1" name="leftmargin" value="<?php echo $data['leftmargin'];?>"/>

	<label for="rightmargin"><?php echo $eve->_('certificationmodel.rightmargin');?></label>
	<input id="rightmargin" type="number" min="0" step="0.1" name="rightmargin" value="<?php echo $data['rightmargin'];?>"/>

	<label for="text_lineheight"><?php echo $eve->_('certificationmodel.textlineheight');?></label>
	<input id="text_lineheight" type="number" min="0" step="0.1" name="text_lineheight" value="<?php echo $data['text_lineheight'];?>"/>

	<label for="text_fontsize"><?php echo $eve->_('certificationmodel.textfontsize');?></label>
	<input id="text_fontsize" type="number" min="1" step="1" name="text_fontsize" value="<?php echo $data['text_fontsize'];?>"/>

	<label for="text_font"><?php echo $eve->_('certificationmodel.text.font');?></label>
	<select id="text_font" name="text_font">
	<?php 
	foreach($eveCertificationService->certificationmodel_textfonts() as $text_font)
	{	
		echo "<option value=\"$text_font\"";
		if ($data['text_font'] === $text_font) echo " selected=\"selected\"";
		echo ">".$text_font."</option>";
	}
	?>
	</select>

	<label for="text_alignment"><?php echo $eve->_('certificationmodel.text.alignment');?></label>
	<select id="text_alignment" name="text_alignment">
	<?php 
	foreach($eveCertificationService->certificationmodel_textalignments() as $text_alignment)
	{	
		echo "<option value=\"$text_alignment\"";
		if ($data['text_alignment'] == $text_alignment) echo " selected=\"selected\"";
		echo ">".$eve->_('certificationmodel.text.alignment.'.$text_alignment)."</option>";
	}
	?>
	</select>
	</div>

	<div class="dialog_panel">
	<div class="dialog_section"><?php echo $eve->_('certificationmodel.section.openermsg');?></div>
	<label for="hasopenermsg">
	<input type="hidden" name="hasopenermsg" value="0"/>
	<input type="checkbox" name="hasopenermsg" id="hasopenermsg" value="1" <?php if ($data['hasopenermsg']) echo "checked=\"checked\"";?> />
	<?php echo $eve->_('certificationmodel.openermsg');?>
	</label>
	<textarea class="htmleditor" rows="6" cols="50" name="openermsg"><?php echo $data['openermsg'];?></textarea>
	</div>
	</form>
	
	<?php
	$eve->output_html_footer();
}?>
