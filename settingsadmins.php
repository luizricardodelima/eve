<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eveUserService = new EveUserService($eve);

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
else if (isset($_POST['action']) && isset($_POST['screenname']))
{
	//if (!$eve->user_exists($_POST['screenname'])) $eve->output_redirect_page(basename(__FILE__)."?error=2");
	switch ($_POST['action'])
	{
		case "add_admin":
			$message = $eveUserService->admin_add($_POST['screenname']);
			$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
		case "remove_admin":
			$message = $eveUserService->admin_remove($_POST['screenname'], $_SESSION['screenname']);
			$eve->output_redirect_page(basename(__FILE__)."?message=$message");
		break;
	}
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Administradores do sistema", null);

	?>
	<div class="section">Administradores do sistema 
	<button type="button" onclick="add_admin();">Adicionar</button>	
	</div>
	<?php if (isset($_GET['message'])) $eve->output_service_message($_GET['message']);?>
	<table class="data_table">
	<tr>
	<th style="width:45%">Email</th>
	<th style="width:45%">Nome</th>
	<th style="width:10%" colspan="1"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	foreach($eveUserService->admin_list() as $admin)
	{	
		echo "<tr>";
		echo "<td style=\"text-align:left\">{$admin['email']}</td>";
		echo "<td style=\"text-align:left\">{$admin['name']}</td>";
		echo "<td><button type=\"button\" onclick=\"remove_admin('{$admin['email']}')\"><img src=\"style/icons/delete.png\"></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
	function remove_admin(screenname)
	{
		if (confirm("Confirma a exclusão dos privilégios de administrador para " + screenname + "?"))
		{
			document.getElementById('remove_admin_hidden_value').value=screenname;
			document.getElementById('remove_admin_form').submit();
		}
		return false;
	}
	function add_admin()
	{
		var screenname = prompt("Insira o e-mail do novo administrador. Ele deve estar previamente cadastrado neste sistema.");
		if (screenname != null)
		{
			document.getElementById('add_admin_hidden_value').value=screenname;
			document.getElementById('add_admin_form').submit();
		}
		return false;
	}
	</script>
	<form method="post" id="remove_admin_form">
		<input type="hidden" name="action" value="remove_admin"/>
		<input type="hidden" name="screenname" id="remove_admin_hidden_value"/>
	</form>
	<form method="post" id="add_admin_form">
		<input type="hidden" name="action" value="add_admin"/>
		<input type="hidden" name="screenname" id="add_admin_hidden_value"/>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
