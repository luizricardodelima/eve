<?php
session_start();
require_once 'eve.class.php';
require_once 'evepageservice.class.php';

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

	$evePageService = new EvePageService($eve);
	switch ($_POST['action'])
	{
		case "change_position":
			$evePageService->page_change_position($_POST['id'], $_POST['new_position']);
			break;
		case "create":
			$evePageService->page_create();
			break;
		case "delete":
			$evePageService->page_delete($_POST['pageid']);			
			break;
	}
	$eve->output_redirect_page(basename(__FILE__));
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$evePageService = new EvePageService($eve);
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.pages') => null
	]);

	?>
	<div class="section">
	<button type="button" onclick="document.forms['create_form'].submit();">Criar p&aacute;gina</button>
	</div>

	<table class="data_table">
	<tr>
	<th style="width:06%">Id</th>
	<th style="width:60%">Título</th>
	<th style="width:06%">Visualizações</th>
	<th style="width:06%">Home</th>
	<th style="width:06%">Visível</th>
	<th style="width:06%">Posição</th>
	<th style="width:10%" colspan="5"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	foreach ($evePageService->page_list(false) as $page)
	{	
		$home = ($page['is_homepage'])? "&#8226;" : "";
		$visible = ($page['is_visible'])? "&#8226;" : "";
		echo "<tr>";
		echo "<td style=\"text-align:center\">{$page['id']}</td>";
		echo "<td style=\"text-align:left\">{$page['title']}</td>";
		echo "<td style=\"text-align:right\">{$page['views']}</td>";
		echo "<td style=\"text-align:center\">$home</td>";
		echo "<td class=\"text-align:center\">$visible</td>";
		echo "<td style=\"text-align:center\">{$page['position']}</td>";
		echo "<td><button type=\"button\" onclick=\"change_position({$page['id']},{$page['position']}+1)\"><img src=\"style/icons/arrow_down.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"change_position({$page['id']},{$page['position']}-1)\"><img src=\"style/icons/arrow_up.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='page.php?id={$page['id']}'\"><img src=\"style/icons/edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='index.php?p={$page['id']}'\"><img src=\"style/icons/view.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"delete_row({$page['id']})\"><img src=\"style/icons/delete.png\"></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
	function change_position(page_id, new_position)
	{
		document.getElementById('chpos_id_hidden_value').value=page_id;
		document.getElementById('chpos_newpos_hidden_value').value=new_position;
		document.getElementById('change_position_form').submit();
		return false;
	}
	function delete_row(page_id)
	{
		if (confirm("Confirma a exclusão da página de id " + page_id + "?"))
		{
			document.getElementById('pageid_hidden_value').value=page_id;
			document.getElementById('delete_form').submit();
		}
		return false;
	}
	</script>
	<form method="post" id="change_position_form">
		<input type="hidden" name="action" value="change_position"/>
		<input type="hidden" name="id" id="chpos_id_hidden_value"/>
		<input type="hidden" name="new_position" id="chpos_newpos_hidden_value"/>
	</form>
	<form method="post" id="delete_form">
		<input type="hidden" name="action" value="delete"/>
		<input type="hidden" name="pageid" id="pageid_hidden_value"/>
	</form>
	<form method="post" id='create_form'>
		<input type="hidden" name="action" value="create"/>
	</form>
	<?php

	$eve->output_html_footer();
}
?>
