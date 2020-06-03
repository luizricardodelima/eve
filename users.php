<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

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
// Post actions
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'change_email':
			// Validation
			$EveUserService = new EveUserService($eve);
			
			if (!filter_var($_POST['newemail'], FILTER_VALIDATE_EMAIL))
				$eve->output_redirect_page(basename(__FILE__)."?error=1");
			else if ($EveUserService->userExists($_POST['newemail']))
				$eve->output_redirect_page(basename(__FILE__)."?error=2");
			else
			{
				$EveUserService->changeEmail($_POST['oldemail'], $_POST['newemail']);
				$eve->output_redirect_page(basename(__FILE__)."?success=1");
				
			}
		break;
		
		case 'delete_user':
			$EveUserService = new EveUserService($eve);
			$EveUserService->deleteUser($_POST['screenname']);
			$eve->output_redirect_page(basename(__FILE__)."?success=2");
		break;
		
	}
}
// Regular view
else
{
	$EveUserService = new EveUserService($eve);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Usuários", null);	

	if (isset($_GET['success']))
	{
		if ($_GET['success'] == 1) $eve->output_success_message("E-mail alterado com sucesso.");
		else if ($_GET['success'] == 2) $eve->output_success_message("Usuário apagado com sucesso.");
	}
	else if (isset($_GET['error']))
	{
		if ($_GET['error'] == 1) $eve->output_error_message("O e-mail inserido é inválido.");
		else if ($_GET['error'] == 2) $eve->output_error_message("O e-mail inserido já existe em sistema.");
	}
	?>
	<script>
	function toggle(source, elementname)
	{
		checkboxes = document.getElementsByName(elementname);
		for(var i=0, n=checkboxes.length;i<n;i++)
		{
			checkboxes[i].checked = source.checked;
			toggleRow(checkboxes[i]);
		}
	}
	function toggleRow(source)
	{
		if (source.checked)
		{
			source.parentNode.parentNode.classList.add('selected');
		}
		else
		{
			source.parentNode.parentNode.classList.remove('selected');
		}
	}
	function changeemail(oldemail)
	{
		var newemail = prompt("Insira o novo e-mail em substituição a " + oldemail + ".");
		if (newemail != null)
		{
			document.getElementById('oldemail_hidden_value').value=oldemail;
			document.getElementById('newemail_hidden_value').value=newemail;
			document.getElementById('change_email_form').submit();
		}
		return false;
	}
	function delete_user(screenname)
	{
		if (confirm("Você confirma a exclusão do usuário de e-mail " + screenname + "? ATENÇÃO: Esta ação não pode ser desfeita."))
		{
			document.getElementById('delete_user_hidden_value').value=screenname;
			document.getElementById('delete_user_form').submit();
		}
		return false;
	}
	</script>

	<div class="section">
	<button type="button" onclick="document.forms['users_form'].submit();">Gerar credencial</button>
	<button type="button" onclick="window.location.href='usercreation.php';">Criar usuários</button>
	</div>

	<form method="post" action="credential.php" id="users_form">
	<table class="data_table">
	<tr>
	<th><input type="checkbox" onClick="toggle(this, 'screenname[]')"/></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=name">Nome</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=email">E-mail</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=description">Categoria</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=note">Obs</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=locked_form">Bloq.</a></th>
	<th colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	$order_criteria = isset($_GET["order-by"]) ? $_GET["order-by"] : "";
	$users = $EveUserService->user_general_list($order_criteria);
	while ($user = $users->fetch_assoc())
	{	
		$locked_form = ($user['locked_form']) ? "&#8226;" : "";
		echo "<tr>";
		echo "<td><input type=\"checkbox\" name=\"screenname[]\" value=\"{$user['email']}\" onclick=\"toggleRow(this)\"/></td>";
		echo "<td>{$user['name']}</td>";
		echo "<td>{$user['email']}</td>";
		echo "<td>{$user['description']}</td>";
		echo "<td>{$user['note']}</td>";
		echo "<td class=\"icon\">$locked_form</td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='user.php?user={$user['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"changeemail('{$user['email']}')\"><img src=\"style/icons/changeemail.png\"/></td>";
		echo "<td><button type=\"button\" onclick=\"delete_user('{$user['email']}')\"><img src=\"style/icons/delete.png\"/></td>";
		echo "</tr>";
	}
	?>
	</table>
	</form>
	<form method="post" id="change_email_form">
		<input type="hidden" name="action" value="change_email"/>
		<input type="hidden" name="oldemail" id="oldemail_hidden_value"/>
		<input type="hidden" name="newemail" id="newemail_hidden_value"/>
	</form>
	<form method="post" id="delete_user_form">
		<input type="hidden" name="action" value="delete_user"/>
		<input type="hidden" name="screenname" id="delete_user_hidden_value"/>
	</form>
	
	<?php
	$eve->output_html_footer();
}?>
