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
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case "create":
			$eve->mysqli->query("insert into `{$eve->DBPref}usercategory` (`description`) values ('{$_POST['catdescription']}');");
			$eve->output_redirect_page(basename(__FILE__));
			break;
		case "delete":
			$eve->mysqli->query("delete from `{$eve->DBPref}usercategory` where `{$eve->DBPref}usercategory`.`id` = {$_POST['catid']};");
			$eve->output_redirect_page(basename(__FILE__));
		break;
	}
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Categorias", null);

	?>
	<div class="section">
	<button type="button" onclick="create_category();">Criar categoria</button>
	</div>

	<table class="data_table">
	<tr>
	<th style="width:10%">Id</th>
	<th style="width:60%">Descrição</th>
	<th style="width:10%">Especial</th>
	<th style="width:20%" colspan="2"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	$category_res = $eve->mysqli->query
	(" 
		SELECT *
		FROM `{$eve->DBPref}usercategory`
		ORDER BY `{$eve->DBPref}usercategory`.`description`
	");
	while ($category = $category_res->fetch_assoc())
	{	
		$special = ($category['special'])? "&#8226;" : "";
		echo "<tr>";
		echo "<td style=\"text-align:center\">{$category['id']}</td>";
		echo "<td style=\"text-align:left\">{$category['description']}</td>";
		echo "<td style=\"text-align:center\">$special</td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='usercategory.php?id={$category['id']}'\"><img src=\"style/icons/edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"delete_row({$category['id']})\"><img src=\"style/icons/delete.png\"></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
	function delete_row(cat_id)
	{
		if (confirm("Confirma a exclusão da categoria de id " + cat_id + "?"))
		{
		    form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'delete');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'catid');
        	var2.setAttribute('value', cat_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();  
		}
		return false;
	}
	function create_category()
	{
		var cat_description = prompt("Digite o nome da categoria");
		if (cat_description != null)
		{
		    form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'create');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'catdescription');
        	var2.setAttribute('value', cat_description);
        	form.appendChild(var2);
        	document.body.appendChild(form);
        	form.submit();  
		}
		return false;
	}
	</script>
	<?php
	$eve->output_html_footer();
}
?>
