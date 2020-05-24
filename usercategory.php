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
// Blocking sql injections by accepting numbers only for id
else if (!is_numeric($_GET['id'])) 
{
	$eve->output_error_page('common.message.invalid.parameter'); 
}
// Checking whether the id passed is valid
// This page opens deactivated userusercategory too
else if (!$eve->mysqli->query("SELECT * FROM `{$eve->DBPref}usercategory` WHERE `id` = {$_GET['id']};")->num_rows)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Categorias", "usercategories.php", "Categoria (ID: {$_GET['id']})", null);


	$data = array();
	if (empty($_POST))
	{
		//No POST data. Retrieving data from database.
		$category = $eve->mysqli->query("SELECT * FROM `{$eve->DBPref}usercategory` WHERE `id`={$_GET['id']}")->fetch_assoc();
		foreach ($category as $column => $value) 
		{
			$data[$column] = $value;
		} 
	}
	else
	{
		// There is POST data. There is no need to retrieve data from database.
		// POST data will be validadated. If valid, they will be saved on db.
		foreach ($_POST as $column => $value)
		{
			$data[$column] = $value;
			$eve->mysqli->query("UPDATE `{$eve->DBPref}usercategory` SET `$column` = '$value' WHERE `id` = {$_GET['id']};");
		}
		$eve->output_success_message("Dados salvos com sucesso");
	}
	?>
	<div class="section">
	<button type="button" onclick="document.forms['category_form'].submit();"/>Salvar</button>
	</div>
	<form action="<?php echo basename(__FILE__)."?id=".$_GET['id'];?>" method="post" id="category_form">
	<div class="dialog_panel">
		<p></p>
		<label for="ipt_description">Descrição</label>
		<input id="ipt_description" type="text" name="description" value="<?php echo $data['description'];?>"/>
		<span>
			<input type="hidden" name="special" value="0"/>
			<input id="ipt_special" type="checkbox" name="special" value="1" <?php if ($data['special']) echo "checked=\"checked\"";?> />
			<label for="ipt_special">Especial</label>
		</span>
		<p></p>
	</div>
	</form>
	
	<?php
	$eve->output_html_footer();
}
?>
