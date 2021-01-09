<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';

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
	$msg = "";
	$eveCertificationService = new EveCertificationService($eve);
	switch ($_POST['action'])
	{
		case "create":
			$msg = $eveCertificationService->certificationmodel_create($_POST['name']);
			break;
		case "delete":
			$msg = $eveCertificationService->certificationmodel_delete($_POST['id']);
			break;
		case "duplicate":
			$msg = $eveCertificationService->certificationmodel_duplicate($_POST['id']);
			break;
	}
	$eve->output_redirect_page(basename(__FILE__)."?message=$msg");
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header(['sort-table']);
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.certificationtemplates') => null
	]);

	// Success/Error messages
	if (isset($_GET['message'])) $eve->output_service_message($_GET['message']);

	?>
	<div class="section">		
	<button type="button" onclick="certificationmodel_create();"><?php echo $eve->_('certificationmodels.action.create');?></button>
	<button type="button" onclick="certificationmodel_duplicate();"><?php echo $eve->_('certificationmodels.action.duplicate');?></button>
	<button type="button" onclick="window.location='certificationimages.php';"><?php echo $eve->_('certificationmodels.background.images');?></button>
	</div>
	
	<table class="data_table" id="certification_models_table">
	<tr>
	<th onclick="sortColumn('certification_models_table',0,true)"><?php echo $eve->_('certificationmodel.id');?></th>
	<th onclick="sortColumn('certification_models_table',1,false)"><?php echo $eve->_('certificationmodel.type');?></th>
	<th onclick="sortColumn('certification_models_table',2,false)"><?php echo $eve->_('certificationmodel.name');?></th>
	<th onclick="sortColumn('certification_models_table',3,false)"><?php echo $eve->_('certificationmodel.text');?></th>
	<th colspan="4"><?php echo $eve->_('common.table.header.options');?></th>
	</tr>
	<?php

	$eveCertificationService = new EveCertificationService($eve);
	$certificationmodels = $eveCertificationService->certificationmodel_list();

	while ($certificationmodel = $certificationmodels->fetch_assoc())
	{	
		echo "<tr>";
		echo "<td>{$certificationmodel['id']}</td>";
		echo "<td>".$eve->_('certificationmodel.type.'.$certificationmodel['type'])."</td>";
		echo "<td>{$certificationmodel['name']}</td>";
		echo "<td>".mb_substr($certificationmodel['text'], 0, 70, "UTF-8")."</td>";
		echo "<td><button type=\"button\" onclick=\"window.location='certification.php?model_id={$certificationmodel['id']}';\"><img src=\"style/icons/view.png\"/></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location='certification_model.php?id={$certificationmodel['id']}';\"><img src=\"style/icons/edit.png\"/></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location='certificationattribuition.php?id={$certificationmodel['id']}';\"><img src=\"style/icons/certification_attribuition.png\"></button></td>";	
		echo "<td><button type=\"button\" onclick=\"certificationmodel_delete({$certificationmodel['id']});\"><img src=\"style/icons/delete.png\"/></button></td>";
		echo "</tr>";
	}
	?>
	</table>

	<script>
	function certificationmodel_create() 
	{
		var certificationmodel_name = prompt('<?php echo $eve->_('certificationmodels.message.create');?>');
		if (certificationmodel_name != null)
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
			var2.setAttribute('name', 'name');
        	var2.setAttribute('value', certificationmodel_name);
        	form.appendChild(var2);
        	document.body.appendChild(form);
			form.submit();
		}
	}

	function certificationmodel_delete(certificationmodel_id) 
	{
		var raw_message = '<?php echo $eve->_('certificationmodels.message.delete')?>';
		var message = raw_message.replace("<ID>", certificationmodel_id)	
		if (confirm(message))
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
			var2.setAttribute('name', 'id');
        	var2.setAttribute('value', certificationmodel_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
			form.submit();
		}
	}

	function certificationmodel_duplicate() 
	{
		var certificationmodel_id = prompt('<?php echo $eve->_('certificationmodels.message.duplicate');?>');
		if (certificationmodel_id != null)
		{
			form = document.createElement('form');
        	form.setAttribute('method', 'POST');
        	var1 = document.createElement('input');
        	var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
        	var1.setAttribute('value', 'duplicate');
        	form.appendChild(var1);
			var2 = document.createElement('input');
        	var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'id');
        	var2.setAttribute('value', certificationmodel_id);
        	form.appendChild(var2);
        	document.body.appendChild(form);
			form.submit();
		}
	}
	</script>
	
	<?php
	$eve->output_html_footer();
}
?>
