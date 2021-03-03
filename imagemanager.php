<?php
session_start();
require_once 'eve.class.php';
require_once 'lib/filechecker/filechecker.php';

$eve = new Eve();
// TODO Add GIFs and WEBPs into 'page'. Maybe filechecker is not needed...
$settings = 
[
	'certification'=> 
	[	
		'directory' => 'upload/certification/',
		'filetypes' => ['image/png'],
		'breadcrumbs' => 
		[
				$eve->getSetting('userarea_label') => "userarea.php",
				$eve->_('userarea.option.admin.certification_models') => "certification_models.php",
				$eve->_('imagemanager') => null
		]
	],
	'page' => 
	[
		'directory' => 'upload/images/',
		'filetypes' => ['image/png', 'image/jpeg'],
		'breadcrumbs' => 
		[
				$eve->getSetting('userarea_label') => "userarea.php",
				$eve->_('userarea.option.admin.pages') => "pages.php",
				$eve->_('imagemanager') => null
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
// Checking if the parameter 'entity' exists and is valid (it is one of the keys of $settings)
else if (!isset($_GET['entity']) || !in_array($_GET['entity'], array_keys($settings)))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else if (isset($_FILES['file']))
{
	if ($_FILES["file"]["error"] > 0)
	{
		$eve->output_redirect_page(basename(__FILE__)."?entity={$_GET['entity']}&msg=common.message.upload.error.{$_FILES['file']['error']}");
	}
	else if (!validate_filetype($_FILES['file'], $settings[$_GET['entity']]['filetypes']))
	{
		$eve->output_redirect_page(basename(__FILE__)."?entity={$_GET['entity']}&msg=common.message.upload.error.invalid.type");
	}
	else
	{
		$result = move_uploaded_file($_FILES['file']['tmp_name'], "{$settings[$_GET['entity']]['directory']}{$_FILES['file']['name']}");
		if ($result)
			$eve->output_redirect_page(basename(__FILE__)."?entity={$_GET['entity']}&msg=common.message.upload.success");
		else
			$eve->output_redirect_page(basename(__FILE__)."?entity={$_GET['entity']}&msg=common.message.upload.error");
	}
}
else if (isset($_POST['action'])) switch ($_POST['action'])
{	
	case 'delete':
		if (unlink($settings[$_GET['entity']]['directory'] .  $_POST['imagename']))
			$eve->output_redirect_page(basename(__FILE__)."?entity={$_GET['entity']}&msg=imagemanager.message.delete.success");
		else
			$eve->output_redirect_page(basename(__FILE__)."?entity={$_GET['entity']}&msg=imagemanager.message.delete.error");
	break;
}
else
{	
	$eve->output_html_header();
	$eve->output_navigation($settings[$_GET['entity']]['breadcrumbs']);
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	?>
	<div class="section">
	<?php echo $eve->_('imagemanager');?>
	<button type="button" onclick="document.getElementById('uploader').style.display = 'block';"><?php echo $eve->_('imagemanager.button.load.image');?></button>
	</div>

	<!-- Image uploader -->
	<div id="uploader" class="viewer"><div class="viewer_container">
	<button class="close_button" type="button" onclick="document.getElementById('uploader').style.display = 'none';"> X </button>
	<div class="viewer_content">
	<form method="post" enctype="multipart/form-data" class="dialog_panel_wide">
	<input  id="file" type="file" name="file" class="inline_button"/>
	<button type="submit"><?php echo $eve->_('imagemanager.button.send');?></button>
	<small>
	<?php echo $eve->_('imagemanager.label.supported.file.types');?>&nbsp;
	<?php echo(implode(' ', extensions($settings[$_GET['entity']]['filetypes']))); ?>
	</small>
	</form>
	</div></div></div>

	<!-- Image viewer -->
	<div id="viewer" class="viewer"><div class="viewer_container">
	<button class="close_button" type="button" onclick="document.getElementById('viewer').style.display = 'none';"> X </button>
	<div class="viewer_content" id="viewer_content">
	</div></div></div>
	
	<table class="data_table">
	<tr>
	<th><?php echo $eve->_('imagemanager.header.thumbnail');?></th>
	<th><?php echo $eve->_('imagemanager.header.filename');?></th>
	<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
	</tr>
	<?php

	$files = scandir($settings[$_GET['entity']]['directory']);
	unset($files[1]); // The first two results are the directories . and ..
	unset($files[0]); // Removing them.
	foreach($files as $file)
	{	
		echo "<tr>";
		echo "<td style=\"text-align:center;\"><img src=\"{$settings[$_GET['entity']]['directory']}{$file}\" style=\"width: 4rem; height: 4rem; object-fit: cover;\"/></td>";
		echo "<td style=\"text-align:center;\">{$file}</td>";
		echo "<td><button type=\"button\" onclick=\"image_view('{$file}')\"><img src=\"style/icons/view.png\"/></button></td>";
		echo "<td><button type=\"button\" onclick=\"image_delete('{$file}')\"><img src=\"style/icons/delete.png\"/></button></td>";
		echo "</tr>";
	}
	?>

	</table>
	<script>
	function image_delete(imagename) 
	{
		var raw_message = '<?php echo $eve->_("imagemanager.message.delete")?>';
		var message = raw_message.replace('<IMAGENAME>', imagename)	
		if (confirm(message))
		{
			form = document.createElement('form');
			form.setAttribute('method', 'POST');
			form.setAttribute('action', '<?php echo basename(__FILE__)."?entity=".$_GET['entity'];?>');
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
			var1.setAttribute('value', 'delete');
			form.appendChild(var1);
			var2 = document.createElement('input');
			var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'imagename');
			var2.setAttribute('value', imagename);
			form.appendChild(var2);
			document.body.appendChild(form);
			form.submit();
		}
	}

	function image_view(imagename) 
	{
		// Cleaning viewer
		const node = document.getElementById('viewer_content');
 		while (node.lastElementChild) {
    		node.removeChild(node.lastElementChild);
  		}

		var img = document.createElement('img'); 
        img.src = '<?php echo $settings[$_GET['entity']]['directory'];?>' + imagename;
		img.style.width = '100%';
		img.style.objectFit = 'scale-down';
        document.getElementById('viewer_content').appendChild(img); 
		document.getElementById('viewer').style.display = 'block';
	}
	</script>

	<?php
	$eve->output_html_footer();
}
?>
