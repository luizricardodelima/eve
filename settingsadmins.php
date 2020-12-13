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
			$eve->output_redirect_page(basename(__FILE__)."?msg=$message");
		break;
		case "remove_admin":
			$message = $eveUserService->admin_remove($_POST['screenname'], $_SESSION['screenname']);
			$eve->output_redirect_page(basename(__FILE__)."?msg=$message");
		break;
	}
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.settings') => "settings.php",
		$eve->_('settings.system.admins') => null,
	]);

	?>
	<div class="section"><?php echo $eve->_('settings.system.admins');?>
	<button type="button" onclick="add_admin();"><?php echo $eve->_('settings.system.admins.add');?></button>	
	</div>
	<?php if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);?>
	<table class="data_table">
	<tr>
	<th style="width:45%"><?php echo $eve->_('user.data.email');?></th>
	<th style="width:45%"><?php echo $eve->_('user.data.name');?></th>
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
		var raw_message = '<?php echo $eve->_('settings.system.admins.message.remove')?>';
		var message = raw_message.replace("<EMAIL>", screenname);
		if (confirm(message))
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'remove_admin');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'screenname');
        	var2.setAttribute('value', screenname);
        	form.appendChild(var2);
        	document.body.appendChild(form);
			form.submit();
		}
		return false;
	}
	function add_admin()
	{
		var screenname = prompt('<?php echo $eve->_('settings.system.admins.message.add');?>');
		if (screenname != null)
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'add_admin');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'screenname');
        	var2.setAttribute('value', screenname);
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