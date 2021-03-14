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
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'change_email':
			$msg = $eveUserService->unverified_user_change_email($_POST['oldemail'], $_POST['newemail']);
			break;
		case "delete":
			$msg = $eveUserService->unverified_user_delete($_POST['email']);
			break;
		case "resend_email":
			$msg = $eveUserService->unverified_user_send_verification_email($_POST['email']);
			break;
	}
	$eve->output_redirect_page(basename(__FILE__)."?msg=$msg");
}
// If there is a valid session, and the current user is administrator and there is no
// action sent by post, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.unverifiedusers') => null
	]);
 
	// Success/Error messages
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	?>
	<table class="data_table">
	<tr>
	<th style="width:40%"><?php echo $eve->_('unverified.users.header.email');?></th>
	<th style="width:40%"><?php echo $eve->_('unverified.users.header.verificationcode');?></th>
	<th style="width:20%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	$unverifiedusers = $eveUserService->unverified_user_list();
	foreach ($unverifiedusers as $unverifieduser) 
	{	
		echo "<tr>";
		echo "<td style=\"text-align:left\">{$unverifieduser['email']}</td>";
		echo "<td style=\"text-align:left\">{$unverifieduser['verificationcode']}</td>";
		echo "<td><button type=\"button\" onclick=\"unverified_user_resend_email('{$unverifieduser['email']}')\"><img src=\"style/icons/email.png\"/></td>";
		echo "<td><button type=\"button\" onclick=\"unverified_user_change_email('{$unverifieduser['email']}')\"><img src=\"style/icons/changeemail.png\"/></td>";
		echo "<td><button type=\"button\" onclick=\"unverified_user_delete('{$unverifieduser['email']}')\"><img src=\"style/icons/delete.png\"></td>";
		echo "</tr>";
	}
	?>
	</table>
	<script>
	function unverified_user_change_email(oldemail)
	{
		var raw_message = '<?php echo $eve->_('unverified.users.message.change.email');?>';
		var message = raw_message.replace('<OLD_EMAIL>', oldemail);
		var newemail = prompt(message);
		if (newemail != null)
		{
			form = document.createElement('form');
			form.method = "post";
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
			var1.setAttribute('value', 'change_email');
			form.appendChild(var1);
			var2 = document.createElement('input');
			var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'oldemail');
			var2.setAttribute('value', oldemail);
			form.appendChild(var2);
			var3 = document.createElement('input');
			var3.setAttribute('type', 'hidden');
			var3.setAttribute('name', 'newemail');
			var3.setAttribute('value', newemail);
			form.appendChild(var3);
			document.body.appendChild(form);
			form.submit();
		}
	}
	function unverified_user_delete(email)
	{
		var raw_message = '<?php echo $eve->_('unverified.users.message.delete');?>';
		var message = raw_message.replace('<EMAIL>', email);
		if (confirm(message))
		{
			form = document.createElement('form');
			form.method = "post";
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
			var1.setAttribute('value', 'delete');
			form.appendChild(var1);
			var2 = document.createElement('input');
			var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'email');
			var2.setAttribute('value', email);
			form.appendChild(var2);
			document.body.appendChild(form);
			form.submit();
		}
	}
	function unverified_user_resend_email(email)
	{
		form = document.createElement('form');
		form.method = "post";
		var1 = document.createElement('input');
		var1.setAttribute('type', 'hidden');
		var1.setAttribute('name', 'action');
		var1.setAttribute('value', 'resend_email');
		form.appendChild(var1);
		var2 = document.createElement('input');
		var2.setAttribute('type', 'hidden');
		var2.setAttribute('name', 'email');
		var2.setAttribute('value', email);
		form.appendChild(var2);
		document.body.appendChild(form);
		form.submit();
	}
	</script>
	<?php
	$eve->output_html_footer();
}
?>
