<?php
session_start();
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);

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
// Checking whether an id was passed and whether it is valid
else if (!isset($_GET['id']) || !$eveSubmissionService->submission_definition_get($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else if (isset($_POST['action'])) switch ($_POST['action'])
{
	case "create_access":
		$msg = $eveSubmissionService->submission_definition_access_create($_GET['id'], $_POST['type'], $_POST['content']);	
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&msg=$msg");
		break;
	case "delete_access":
		$msg = $eveSubmissionService->submission_definition_access_delete($_POST['id']);
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&msg=$msg");
        break;
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular page.
else
{
	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('submission_definitions') => "submission_definitions.php",
		$submission_definition['description'] => "submission_definition.php?id={$_GET['id']}",
		"Gerenciar acesso restrito" => null
	]);

	if (isset ($_GET['msg'])) $eve->output_service_message($_GET['msg']);
	?>

	<div class="section">
	<?php
		foreach($eveSubmissionService->submission_definition_access_types() as $type)
		{
			echo "<button type=\"button\" onclick=\"create_access('$type')\">";
			echo "+ ".$eve->_('submission.definition.access.type.'.$type);
			echo "</button>";
		}
	?>
	</div>

	<table class="data_table">
	<tr>
	<th><?php echo $eve->_('submission.definition.access.type');?></th>
	<th><?php echo $eve->_('submission.definition.access.content');?></th>
	<th><?php echo $eve->_('common.table.header.options');?></th>
	</tr>
	<?php
	foreach($eveSubmissionService->submission_definition_access_list($_GET['id']) as $access)
	{	
		echo "<tr>";
		echo "<td>".$eve->_('submission.definition.access.type.'.$access['type'])."</td>";
		echo "<td>{$access['content']}</td>";
		echo "<td>";
		echo "<button type=\"button\" onclick=\"delete_access('{$access['id']}')\"><img src=\"style/icons/delete.png\"/></button>";
		echo "</td>";
		echo "</tr>";
	}
	?>
	</table>
	
	<script>
	function create_access(type)
	{
		var promptmessage = null;
		switch (type)
		{
			case 'specific_user':
			case 'submission_after_deadline': 
				promptmessage = "Insira o e-mail do usuário";
				break;
			default: // In case a new access type is created in db and not treated here
				promptmessage = "Insira a especificação";
				break;
		}
		var content = prompt(promptmessage);
		if (content != null)
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'create_access');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'type');
        	var2.setAttribute('value', type);
        	form.appendChild(var2);
			var3 = document.createElement('input');
        	var3.setAttribute('type', 'hidden');
			var3.setAttribute('name', 'content');
        	var3.setAttribute('value', content);
        	form.appendChild(var3);
        	document.body.appendChild(form);
        	form.submit(); 
		}
		return false;
	}
	function delete_access(id)
	{
		if (confirm("Confirma a exclusão da restrição de acesso?"))
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'delete_access');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'id');
        	var2.setAttribute('value', id);
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