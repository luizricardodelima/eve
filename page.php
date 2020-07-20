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
// Checking whether an id was passed
else if (!isset($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// Blocking sql injections by accepting numbers only
else if (!is_numeric($_GET['id'])) 
{
	$eve->output_error_page('common.message.invalid.parameter'); 
}
// Checking whether the id passed is valid
else if (!$eve->mysqli->query("SELECT * FROM `{$eve->DBPref}page` WHERE `id`={$_GET['id']};")->num_rows)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	// TODO use evepageservice.class.php
	$data = array();
	$validation_errors = array();
	$data_updated = 0;
	// Will be false if there is no update (on retrieving data) or when there are validation errors
	// We use this variable to lazily call eve's html rendering fuctions AFTER changes are made in database.
	// This is especially important in this page because we deal with page names and titles, and any change on
	// a page title or position, for example, change the page header rendering

	if (empty($_POST))
	{
		//No POST data. Retrieving data from database.
		$page_res = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}page` WHERE `id`={$_GET['id']};");
		$data = $page_res->fetch_assoc();
	}
	else
	{
		// There is POST data. There is no need to retrieve data from database.
		// POST data will be validadated. If valid, they will be stored on db.	
		foreach ($_POST as $column => $value)
		{
			$data[$column] = $value;			
		}
	
		// Validation
		if (!is_numeric($data['position'])) $validation_errors[] = "Posição não é um número válido";

		if (empty($validation_errors))
		{
			$data['position'] = intval($data['position']);
			foreach ($data as $column => $value)
			{
				$value = $eve->mysqli->real_escape_string($value);
				$eve->mysqli->query("UPDATE `{$eve->DBPref}page` SET `$column` = '$value' WHERE `id` = {$_GET['id']};");
			}
			// If current page is set as homepage, unset all the other pages as homepage
			if ($data['is_homepage']) $eve->mysqli->query("UPDATE `{$eve->DBPref}page` SET `is_homepage` = 0 WHERE `id` != {$_GET['id']};");
			$data_updated = 1;
		}
		
	}
	
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Páginas", "pages.php", "Página (ID: {$_GET['id']})", null);
	$eve->output_wysiwig_editor_code();
	
	?>
	<div class="section">
	<button type="button" onclick="document.forms['page_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>
	<?php

	if ($data_updated) $eve->output_success_message("Dados salvos com sucesso");
	else if ($validation_errors) $eve->output_error_list_message($validation_errors);
	
	?>
	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" id="page_form">
	<table style="width: 100%;">
	<tr><td>Titulo</td><td><textarea rows="1" name="title"><?php echo $data['title'];?></textarea></td></tr>
	<tr><td>Conteúdo</td><td><textarea class="htmleditor" rows="6" cols="50" name="content"><?php echo $data['content'];?></textarea></td></tr>
	<tr><td>Posição</td><td><input type="number" name="position" value="<?php echo $data['position'];?>"/></td></tr>
	<tr><td>Visível</td><td><input type="hidden" name="is_visible" value="0"/><input type="checkbox" name="is_visible" value="1" <?php if ($data['is_visible']) echo "checked=\"checked\"";?> /></td></tr>
	<tr><td>Home page</td><td><input type="hidden" name="is_homepage" value="0"/><input type="checkbox" name="is_homepage" value="1" <?php if ($data['is_homepage']) echo "checked=\"checked\"";?> /></td></tr>
	</table>
	</form>
	<?php

	$eve->output_html_footer();
}
?>
