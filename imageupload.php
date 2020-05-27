<?php
session_start();
require_once 'eve.class.php';
require_once 'lib/filechecker/filechecker.php';

// Defining constants for image upload
$image_filetype = array("image/png");
$image_filesize = 5; // Megabytes

$eve = new Eve();

// This is the main content of page imageupload.php. To a better strucuture of this page,
// the main code was put into a separate function, to be used in this page only.
function output_main_content($eve, $image_filetype, $image_filesize)
{
	if (isset($_GET['fileerrorcode']))
		$eve->output_error_message("Erro ao enviar o arquivo. Código de erro: {$_GET['fileerrorcode']}");
	else if (isset($_GET['validationerror'])) switch ($_GET['validationerror'])
	{
		case 1:
			$eve->output_error_message("Erro: Tipo de arquivo inválido. Os formatos válidos são ".implode(" ",extensions($image_filetype)));			
			break;
		case 2:
			$eve->output_error_message("Erro: O arquivo tem que ter tamanho menor que $image_filesize megabytes.");
			break;
	}
	else if (isset($_GET['success']))
		$eve->output_success_message("Imagem atualizada com sucesso. Pode levar um tempo para ela ser atualizada no servidor.");
	?>

	<div class="section">Imagem atual</div>
	<?php echo "<p><img src=\"upload/{$_GET['type']}/{$_GET['type']}.png\"></p>";?>
	<div class="section">Carregar imagem</div>
	<form action="<?php echo basename(__FILE__)."?type={$_GET['type']}";?>" method="post" enctype="multipart/form-data">	
	<input type="file" name="file" id="file" class="inline_button"/>
	<button type="submit" class="medium">Enviar</button>
	</form>
	<?php
}

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
	// Using switch instead of isset $_GET['type'], because any other 'type' instead of the predefined ones is considered as error
	switch ($_GET['type'])
	{
		case 'header':
		case 'credential':
			if ($_FILES['file']['error'] > 0)
			{
				$eve->output_redirect_page(basename(__FILE__)."?type={$_GET['type']}&fileerrorcode={$_FILES["file"]["error"]}");
			}
			else if (!validate_filetype($_FILES['file'], $image_filetype))
			{
				$eve->output_redirect_page(basename(__FILE__)."?type={$_GET['type']}&validationerror=1");
			}
			else if (!validate_filesizeMB($_FILES['file'], $image_filesize))
			{
				$eve->output_redirect_page(basename(__FILE__)."?type={$_GET['type']}&validationerror=2");
			}
			else
			{
				if (move_uploaded_file($_FILES["file"]["tmp_name"], "upload/{$_GET['type']}/{$_GET['type']}.png"))
					$eve->output_redirect_page("imageupload.php?type={$_GET['type']}&success=1");
				else
					$eve->output_error_page("Erro ao carregar o arquivo.");
			}
			break;
		default:
			$eve->output_error_page("Erro na página");
			break;
	}
	
}
else
{	// Using switch instead of isset $_GET['type'], because any other 'type' instead of the predefined ones is considered as error
	switch ($_GET['type'])
	{
		case 'header':
			$eve->output_html_header();
			$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php",  $eve->_('userarea.option.admin.settings'), "settings.php", "Aparência", "settingsappearance.php", "Imagem de cabe&ccedil;alho", null);
			output_main_content($eve, $image_filetype, $image_filesize);
			$eve->output_html_footer();
			break;
		case 'credential':
			$eve->output_html_header();
			$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php",  $eve->_('userarea.option.admin.settings'), "settings.php", "Credenciais", "settingscredential.php", "Imagem da credencial", null);
			output_main_content($eve, $image_filetype, $image_filesize);
			$eve->output_html_footer();
			break;
		default:
			$eve->output_error_page("Erro na página");
			break;
	}
	
}
?>
