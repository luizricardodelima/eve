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
// Post actions
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'change_email':
			$msg = $eveUserService->user_change_email($_POST['oldemail'], $_POST['newemail']);
			$eve->output_redirect_page(basename(__FILE__)."?msg=$msg");
			break;
		case 'delete_user':
			$msg = $eveUserService->user_delete($_POST['screenname']);
			$eve->output_redirect_page(basename(__FILE__)."?msg=$msg");
			break;
	}
}
// Regular view
else
{
	$eve->output_html_header(['sort-table']);
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Usuários", null);	
	if (isset ($_GET['msg'])) $eve->output_service_message($_GET['msg']);

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
		if (source.checked) source.parentNode.parentNode.classList.add('selected');
		else source.parentNode.parentNode.classList.remove('selected');
	}
	function user_change_email(oldemail)
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
	<table class="data_table" id="users_table">
	<tr>
	<th><input type="checkbox" onClick="toggle(this, 'screenname[]')"/></th>
	<th onclick="sortColumn('users_table',1,false)">Nome</th>
	<th onclick="sortColumn('users_table',2,false)">E-mail</th>
	<th onclick="sortColumn('users_table',3,false)">Obs</th>
	<th onclick="sortColumn('users_table',4,false)">Bloq.</th>
	<th colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	foreach($eveUserService->user_general_list() as $user)
	{	
		$locked_form = ($user['locked_form']) ? "&#8226;" : "";
		echo "<tr>";
		echo "<td><input type=\"checkbox\" name=\"screenname[]\" value=\"{$user['email']}\" onclick=\"toggleRow(this)\"/></td>";
		echo "<td>{$user['name']}</td>";
		echo "<td>{$user['email']}</td>";
		echo "<td>{$user['note']}</td>";
		echo "<td class=\"icon\">$locked_form</td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='user.php?user={$user['email']}'\"><img src=\"style/icons/user_edit.png\"></button></td>";
		echo "<td><button type=\"button\" onclick=\"user_change_email('{$user['email']}')\"><img src=\"style/icons/changeemail.png\"/></td>";
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
