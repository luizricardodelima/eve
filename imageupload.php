<?php
session_start();
require_once 'eve.class.php';

$eve = new Eve();

// TODO: Use the same strategies used in imagemanager
// TODO: Rename this page to image single upload
// TODO: G11N naturally
// This page is used for uploading images for several contexts. This
// This array contains the information necessary to load this page in each context.
$upload_types = 
[
	'credential' =>
	[
		'filetype' => 'image/png',
		'maxfilesize' => 5,
		'uploadfolder' => 'upload/style/',
		'filename' => 'credential.png',
		'navigation_links' => 
		[
			$eve->getSetting('userarea_label') => "userarea.php",
			$eve->_('userarea.option.admin.settings') => "settings.php",
			"Credenciais" => "settingscredential.php",
			"Imagem da credencial" => null			
		]
	],
	'header' =>
	[
		'filetype' => 'image/png',
		'maxfilesize' => 5,
		'uploadfolder' => 'upload/style/',
		'filename' => 'header.png',
		'navigation_links' => 
		[
			$eve->getSetting('userarea_label') => "userarea.php",
			$eve->_('userarea.option.admin.settings') => "settings.php",
			"Aparência" => "settingsappearance.php",
			"Imagem de cabe&ccedil;alho" => null
		]
	],
	'login' =>
	[
		'filetype' => 'image/png',
		'maxfilesize' => 5,
		'uploadfolder' => 'upload/style/',
		'filename' => 'login.png',
		'navigation_links' => 
		[
			$eve->getSetting('userarea_label') => "userarea.php",
			$eve->_('userarea.option.admin.settings') => "settings.php",
			"Aparência" => "settingsappearance.php",
			"Imagem de login" => null
		]
	]	
];

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
else if (!isset($_GET['type']) && !isset($upload_types[$_GET['type']]))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else 
{
	$upload_type = $upload_types[$_GET['type']];
	$upload_error = false;
	$validation_error = 0; // 0 - no error, 1 = wrong type, 2 = exceeded size
	
	if (isset($_FILES['file']))
	{
		// There was a file upload operation
		if ($_FILES['file']['error'] > 0)
		{
			$upload_error = true;
		}
		else if ($_FILES['file']['type'] != $upload_type['filetype'])
		{
			$validation_error = 1;
		}
		else if ($_FILES['file']['size'] > $upload_type['maxfilesize'] * 1048576)
		{
			$validation_error = 2;
		}
		else
		{
			move_uploaded_file($_FILES['file']['tmp_name'], $upload_type['uploadfolder'].$upload_type['filename']);
		}
	}

	$eve->output_html_header();
	$eve->output_navigation($upload_type['navigation_links']);
	echo "<div class=\"section\">";
	echo array_keys($upload_type['navigation_links'])[sizeof($upload_type['navigation_links']) - 1];
	echo "</div>";

	if ($upload_error)
		$eve->output_error_message("Erro ao enviar o arquivo. Código de erro: {$_FILES['file']['error']}");
	if ($validation_error) switch ($validation_error)
	{
		case 1:
			$eve->output_error_message("Erro: Tipo de arquivo inválido. Os formato válido é {$upload_type['filetype']}.");			
			break;
		case 2:
			$eve->output_error_message("Erro: O arquivo tem que ter tamanho menor que {$upload_type['maxfilesize']} megabytes.");
			break;
	}
	?>

	<div class="dialog_panel_wide">
	<div class="dialog_section">Imagem atual</div>
	<?php 
	if (file_exists($upload_type['uploadfolder'].$upload_type['filename']))
		echo "<img src=\"{$upload_type['uploadfolder']}{$upload_type['filename']}\">";
	else
		echo "<label>Não há arquivo carregado.</label>";
	?>
	</div>

	<form action="<?php echo basename(__FILE__)."?type={$_GET['type']}";?>" method="post" enctype="multipart/form-data">
	<div class="dialog_panel_wide">	
	<div class="dialog_section">Carregar imagem</div>
	<input type="file" name="file"/>
	<button class="submit" type="submit">Enviar</button>
	</div>
	</form>
	<?php

	$eve->output_html_footer();
}
?>