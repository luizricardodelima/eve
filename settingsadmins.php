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
else if (isset($_POST['action']) && isset($_POST['screenname']))
{
	if (!$eve->user_exists($_POST['screenname'])) $eve->output_redirect_page(basename(__FILE__)."?error=2");
	else switch ($_POST['action'])
	{
		case "add_admin":
			// Adding given screenname as administrator
			$eve->mysqli->query
			("
				update `{$eve->DBPref}userdata`
				set `{$eve->DBPref}userdata`.`admin` = 1
				where `{$eve->DBPref}userdata`.`email` = '{$_POST['screenname']}';
			");
			$eve->output_redirect_page(basename(__FILE__)."?addsuccessful={$_POST['screenname']}");
		break;
		case "remove_admin":
			// Case insensitive comparison
			if (strcasecmp($_POST['screenname'], $_SESSION['screenname']) == 0)
			{
				// The current user cannot unset himself as an administrator. He must ask another admin to do so.
				$eve->output_redirect_page(basename(__FILE__)."?error=1");
			}
			else
			{
				// Removing given screenname as administrator
				$eve->mysqli->query
				("
					update `{$eve->DBPref}userdata`
					set `{$eve->DBPref}userdata`.`admin` = 0
					where `{$eve->DBPref}userdata`.`email` = '{$_POST['screenname']}';
				");
				$eve->output_redirect_page(basename(__FILE__)."?removesuccessful={$_POST['screenname']}");
			}
		break;
	}
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Ajustes do sistema", "settings.php", "Administradores do sistema", null);

	// Error messages, if any
	if (isset($_GET['error'])) switch ($_GET['error'])
	{
		case 1:
			$eve->output_error_message("O usuário atual não pode retirar seu privilégio de administrador. Outo usuário administrador deve fazer esta operação.");
			break;
		case 2:
			$eve->output_error_message("Usuário inválido.");
			break;
	}
	// Success messages, if any
	if (isset($_GET['addsuccessful']))
		$eve->output_success_message("Usuário {$_GET['addsuccessful']} adicionado como administrador.");
	if (isset($_GET['removesuccessful']))
		$eve->output_success_message("Usuário {$_GET['removesuccessful']} removido como administrador.");
	?>
	<div class="section">
	<button type="button" onclick="add_admin();">Adicionar novo administrador</button>	
	</div>

	<table class="data_table">
	<tr>
	<th style="width:45%">Email</th>
	<th style="width:45%">Nome</th>
	<th style="width:10%" colspan="1"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	// TODO: Move to a service
	$userdata_res = $eve->mysqli->query
	("
		SELECT *		
		FROM `{$eve->DBPref}userdata`
		WHERE `{$eve->DBPref}userdata`.`admin` = 1
		ORDER BY `{$eve->DBPref}userdata`.`email`;
	");

	while ($userdata_row = $userdata_res->fetch_assoc())
	{	
		echo "<tr>";
		echo "<td style=\"text-align:left\">{$userdata_row['email']}</td>";
		echo "<td style=\"text-align:left\">{$userdata_row['name']}</td>";
		echo "<td><button type=\"button\" onclick=\"remove_admin('{$userdata_row['email']}')\"><img src=\"style/icons/delete.png\"></button></td>";
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
