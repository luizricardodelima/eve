<?php
session_start();
require_once 'eve.class.php';
require_once 'lib/filechecker/filechecker.php';

// Defining constants for image upload
$image_filetype = array("image/png");

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
else if (isset($_FILES['file']))
{
	if ($_FILES["file"]["error"] > 0)
	{
		$eve->output_redirect_page(basename(__FILE__)."?fileerrorcode={$_FILES['file']['error']}");
	}
	else if (!validate_filetype($_FILES['file'], $image_filetype))
	{
		$eve->output_redirect_page(basename(__FILE__)."?validationerror=1");
	}
	else
	{
		move_uploaded_file($_FILES['file']['tmp_name'], "upload/certification/{$_FILES['file']['name']}");
		// TODO: The function above may return false - display error message when that happens (usually if the upload directory is not set as 777
		$eve->output_redirect_page(basename(__FILE__)."?uploadsuccess=1");
	}
}
else if (isset($_POST['action'])) switch ($_POST['action'])
{
	case 'delete':
		unlink("upload/certification/{$_POST['imagename']}");
		$eve->output_redirect_page(basename(__FILE__)."?deletesuccess=1");
	break;
}
else
{	
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.certificationtemplates') => "certification_models.php",
		"Imagens de fundo" => null
	]);

	if (isset($_GET['fileerrorcode']))
		$eve->output_error_message("Erro ao enviar o arquivo. Código de erro: {$_GET['fileerrormsg']}.");
	if (isset($_GET['validationerror']))
		$eve->output_error_message("Erro: Tipo de arquivo inválido. Os formatos válidos são ".implode(" ",extensions($image_filetype)).".");
	if (isset($_GET['uploadsuccess']))
		$eve->output_success_message("Imagem inserida com sucesso");
	if (isset($_GET['deletesuccess']))
		$eve->output_success_message("Imagem excluída com sucesso");
	?>
	<div class="section">
	<form method="post" enctype="multipart/form-data"> Carregar imagem: 	
	<input type="file" name="file" id="file" class="inline_button"/>
	<button type="submit">Enviar</button>
	</form>
	</div>
	<table class="data_table">
	<tr>
	<th>Nome do arquivo</th>
	<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
	</tr>
	<?php
	$dir = 'upload/certification/'; // This variable is used later on...
	$files = scandir($dir);
	unset($files[1]); // The first two results are the directories . and ..
	unset($files[0]); // Removing them.
	foreach($files as $file)
	{	
		echo "<tr>";
		echo "<td>{$file}</td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='{$dir}{$file}'\"><img src=\"style/icons/view.png\"/></button></td>";
		echo "<td><button type=\"button\" onclick=\"confim_delete('{$file}')\"><img src=\"style/icons/delete.png\"/></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
	function confim_delete(imagename) 
	{
		if (confirm('Tem certeza que você quer apagar a imagem ' + imagename + '?'))
		{			
			document.getElementById('imagename_id').value = imagename;
			document.getElementById('image_remove_form').submit();
		}
		return false;
	}
	</script>
	<form id="image_remove_form" method="post">
		<input type="hidden" name="action" value="delete"/>
		<input type="hidden" name="imagename" id="imagename_id" value="0"/>
	</form>
	


	<?php
	$eve->output_html_footer();
}
?>
