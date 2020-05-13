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
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{
	$eveUserServices = new EveUserServices($eve);
	switch ($_POST['action'])
	{
		case 'change_email':
			$msg = $eveUserServices->unverified_user_change_email($_POST['oldemail'], $_POST['newemail']);
			break;
		case "delete":
			$msg = $eveUserServices->unverified_user_delete($_POST['email']);
			break;
		case "send_verification_email":
			$msg = $eveUserServices->unverified_user_send_verification_email($_POST['email']);
			break;
	}
	$eve->output_redirect_page(basename(__FILE__)."?message=$msg");
}
// If there is a valid session, and the current user is administrator and there is no
// action sent by post, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php",  "Usuários sem verificação", null);//TODO g11n
 
	// Success/Error messages
	if (isset($_GET['message'])) switch ($_GET['message'])
	{
		case EveUserServices::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_INVALID_EMAIL:
			$eve->output_error_message("O e-mail informado é inválido."); //TODO g11n
			break;
		case EveUserServices::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_UNVERIFIED_USER_EXISTS:
			$eve->output_error_message("O e-mail informado já é usado por outro usuário não verificado."); //TODO g11n
			break;	
		case EveUserServices::UNVERIFIED_USER_CHANGE_EMAIL_ERROR_USER_EXISTS:
			$eve->output_error_message("O e-mail informado já é usado por outro usuário."); //TODO g11n
			break;		
		case EveUserServices::UNVERIFIED_USER_CHANGE_EMAIL_SUCCESS:
			$eve->output_success_message("E-mail alterado com sucesso."); //TODO g11n
			break;
		case EveUserServices::UNVERIFIED_USER_DELETE_SUCCESS:
			$eve->output_success_message("Usuário sem verificação removido com sucesso."); //TODO g11n
			break;
		case EveUserServices::UNVERIFIED_USER_SEND_VERIFICATION_EMAIL_SUCCESS:
			$eve->output_success_message("E-mail de verificação reenviado."); //TODO g11n
			break;
	}	
	?>
	<table class="data_table">
	<tr>
	<th style="width:40%">E-mail</th>
	<th style="width:40%">Código de verificação</th>
	<th style="width:20%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php
	$unverifieduser_res = $eve->mysqli->query
	("
		SELECT `email`, `verificationcode`
		FROM `{$eve->DBPref}unverifieduser`
		ORDER BY `{$eve->DBPref}unverifieduser`.`email`;
	");
	while ($unverifieduser = $unverifieduser_res->fetch_assoc())
	{	
		echo "<tr>";
		echo "<td style=\"text-align:left\">{$unverifieduser['email']}</td>";
		echo "<td style=\"text-align:left\">{$unverifieduser['verificationcode']}</td>";
		echo "<td><button type=\"button\" onclick=\"send_email('{$unverifieduser['email']}')\"><img src=\"style/icons/email.png\"/></td>";
		echo "<td><button type=\"button\" onclick=\"changeemail('{$unverifieduser['email']}')\"><img src=\"style/icons/changeemail.png\"/></td>";
		echo "<td><button type=\"button\" onclick=\"delete_row('{$unverifieduser['email']}')\"><img src=\"style/icons/delete.png\"></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
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
	function delete_row(screenname)
	{
		if (confirm("Confirma a exclusão do usuário sem verificação " + screenname + "?"))
		{
			document.getElementById("email_hidden_value").value=screenname;
			document.getElementById("delete_form").submit();
		}
		return false;
	}
	function send_email(email)
	{
		if (confirm("Confirma o reevenivo do e-mail de verificação para " + email + "?"))
		{
			document.getElementById("send_email_hidden_value").value=email;
			document.getElementById("send_email_form").submit();
		}
		return false;
	}
	</script>
	<form method="post" id="delete_form">
		<input type="hidden" name="action" value="delete"/>
		<input type="hidden" name="email" id="email_hidden_value"/>
	</form>
	<form method="post" id="change_email_form">
		<input type="hidden" name="action" value="change_email"/>
		<input type="hidden" name="oldemail" id="oldemail_hidden_value"/>
		<input type="hidden" name="newemail" id="newemail_hidden_value"/>
	</form>
	<form method="post" id="send_email_form">
		<input type="hidden" name="action" value="send_verification_email"/>
		<input type="hidden" name="email" id="send_email_hidden_value"/>
	</form>
	<?php
	$eve->output_html_footer();
}
?>
