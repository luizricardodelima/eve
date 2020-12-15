<?php
session_start();
require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Checking if $_GET['id'] is valid by trying to retireve the submission_definition from database.
else if (!isset($_GET['id']) || !$eveSubmissionService->submission_definition_get($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else 
{
	// If there's a valid session and the security validations apply,
	// display the regular listing page.
	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);
	
	// TODO CREATE METHOD IN SubmissionService validate_access_mode. This has to be used on submissions export
	// $access_mode is passed as a get variable or cannot be passed. Therefore it is 
	// necessary to normalize its value according the user to avoid malicious access.
	$access_mode = isset($_GET['access_mode']) ? $_GET['access_mode'] : '';
	if
	(
		$eve->is_admin($_SESSION['screenname']) &&
		!in_array($access_mode, array('final_reviewer', 'reviewer', 'owner'))
	)
		$access_mode = 'admin';
	else if
	(	
		$eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $_GET['id']) &&
		!in_array($access_mode, array('reviewer', 'owner'))
	)
		$access_mode = 'final_reviewer';
	else if
	(
		$eveSubmissionService->is_reviewer($_SESSION['screenname'], $_GET['id']) &&
		!in_array($access_mode, array('owner'))
	)
		$access_mode = 'reviewer';
	else
		$access_mode = 'owner';

	$message = null;
	$validation_errors = null;
	
	if (isset($_POST['action'])) switch ($_POST['action'])
	{ // TODO RESOLVE SECURITY FLAWS: 'set_reviewer' only for admins and final_reviewers - revision: only for admins, final_reviewers and reviewers
		// TODO MAYBE DO IT IN THE SERVICES...
		case 'change_status':
			var_dump($_POST);
			//$message = $eveSubmissionService->submission_change_revision_status($_POST['submission_id'], $_POST['status']);
			// $eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}&success=3");
			break;
		case 'set_reviewer':
			$submissions_to_set_reviewer = json_decode($_POST['submissions_to_set_reviewer']);
			$message = $eveSubmissionService->submission_set_reviewer($submissions_to_set_reviewer, $_POST['reviewer']);
			break;
		case 'revision':
			$files = (isset($_FILES['revision_content'])) ? $_FILES['revision_content'] : null;
			DynamicFormHelper::$locale = $eve->getSetting('system_locale');
			$dynamicForm = new DynamicForm($_POST['revision_structure'], $_POST['revision_content'], $files, 'upload/submission/');
			$validation_errors = $dynamicForm->validate();
			if (empty($validation_errors))
			{
				$message = $eveSubmissionService->submission_review($_POST['submission_id'], $_SESSION['screenname'], $dynamicForm);
			}
			break;
	} 

	// Page header
	$eve->output_html_header();
	if ($access_mode == 'admin')
		$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submission_definition['description'], null);
	else
		$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $submission_definition['description'], null);
	
	// Success/error messages
	if (!is_null($message)) $eve->output_service_message($message);
	// Validation error messages
	if (!empty($validation_errors))	$eve->output_error_list_message($validation_errors);
	
	?>
	<div class="section">
	<!-- TODO <button type="button" onclick="alert('Implement - Filtro no DynamicForm')">Filtrar</button>--> 
	<?php if (in_array($access_mode, array('final_reviewer', 'admin'))) { ?><button type="button" onclick="window.location.href = 'submission_definition_reviewers.php?id=<?php echo $_GET['id'];?>&backlink=submissions_<?php echo $access_mode;?>';">Gerenciar avaliadores</button><?php } ?>
	<?php if (in_array($access_mode, array('final_reviewer', 'admin'))) { ?><button type="button" onclick="set_reviewer()">Atribuir avaliador</button><?php } ?>
	<?php if (in_array($access_mode, array('final_reviewer', 'admin'))) { ?><button type="button" onclick="submission_change_status()">Alterar status</button><?php } ?>
	<button type="button" onclick="submission_export()">Exportar</button>
	</div>
	
	<!-- Viewer -->
	<div id="viewer" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
	<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('viewer').style.display = 'none';"> X </button>
	<div id="viewer_content" style="padding: 20px; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;"></div>
	</div></div>

	<!-- Set reviewer dialog -->
	<div id="set_reviewer_dialog" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
	<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('set_reviewer_dialog').style.display = 'none';"> X </button>
	<div style="padding: 20px; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;">
	<form method="post" action="<?php echo basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}";?>">
	<select name="reviewer">
	<?php
	$reviewers = $eveSubmissionService->submission_definition_reviewers($_GET['id'], 'reviewer');
	foreach ($reviewers as $reviewer) echo "<option value=\"{$reviewer['email']}\">{$reviewer['name']}</option>";
	?>
	</select>
	<input type="hidden" name="action" value="set_reviewer"/>
	<input type="hidden" name="submissions_to_set_reviewer" id="submissions_to_set_reviewer"/>
	<button type="submit">Atribuir</button>
	<button type="button" onclick="document.getElementById('set_reviewer_dialog').style.display = 'none';">Cancelar</button>
	</form>
	</div>
	</div></div>

	<!-- Review dialog -->
	<div id="review_dialog" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
	<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('review_dialog').style.display = 'none';"> X </button>
	<form style="padding: 20px;" method="post" action="<?php echo basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}";?>" enctype="multipart/form-data" class="dialog_panel">
	<input type="hidden" name="action" value="revision"/>
	<input type="hidden" name="submission_id" id="submission_id_review"/>
	<div id="submission_review_container" class="dialog_panel_wide"></div>
	</form>
	<script>
		function deactivate_button(e)
		{
			var el = document.createElement("p");
			el.style.textAlign = 'center';
			el.innerHTML = '<img src="style/icons/loading.gif" style="height: 2rem; width: 2rem;"/>';
			e.parentNode.insertBefore(el, e);
			e.style.display = 'none';
		}
	</script>
	</div></div>

	<!-- Change status dialog -->
	<div id="change_status_dialog" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
	<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('change_status_dialog').style.display = 'none';"> X </button>
	<div style="padding: 20px; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;">
	<form method="post" action="<?php echo basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}";?>" id="change_status_form">
	<label id="change_status_label">Alterar o status das submissões selecionadas para: </label>
	<select name="status">
	<?php
	//TODO Implement enum with status codes in eve submission service
	for ($i = 0; $i <= 2; $i++) 
	{
		echo "<option value=\"$i\" id=\"change_status_option[$i]\">".$eve->_("submission.revision_status.$i")."</option>";
	}
	?>
	</select>
	<input type="hidden" name="action" value="change_status"/>
	<button type="submit">Alterar</button>
	<button type="button" onclick="document.getElementById('change_status_dialog').style.display = 'none';">Cancelar</button>
	</form>
	</div>
	</div></div>


	<table class="data_table">
	<tr>
	<th style="width:3%"><input type="checkbox" onClick="toggle(this, 'submission[]')"/></th>
	<th style="width:3%">ID</th>
	<th style="width:14%">Data</th>
	<?php if ($access_mode != 'reviewer') { ?><th style="width:20%">E-mail do autor</th><?php } // TODO HARDCODED - Reviewers dont see who has sent the submission ?>
	<th style="width:20%">E-mail do avaliador</th>
	<th style="width:20%">Status da avaliação</th>
	<th style="width:22%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	// Listing submissions. If $access_mode == 'admin' or 'final_reviewer', the third parameter
	// of submission_list function will be ignored
	$submissions = $eveSubmissionService->submission_list($_GET['id'], $access_mode, $_SESSION['screenname']);
	foreach ($submissions as $submission)
	{	
		echo "<tr>";
		echo "<td style=\"text-align:center\"><input type=\"checkbox\" name=\"submission[]\" value=\"{$submission['id']}\" onclick=\"toggleRow(this)\"/></td>";
		echo "<td style=\"text-align:center\">{$submission['id']}</td>";
		echo "<td style=\"text-align:center\">{$submission['date']}</td>";
		if ($access_mode != 'reviewer') echo "<td style=\"text-align:left\">{$submission['email']}</td>"; // TODO HARDCODED - Reviewers dont see who has sent the submission
		echo "<td style=\"text-align:left\">{$submission['reviewer_email']}</td>";
		echo "<td style=\"text-align:left\">".$eve->_("submission.revision_status.{$submission['revision_status']}")."</td>";
		
		echo "<td>";
			if ($access_mode == 'admin') echo "<button type=\"button\" onclick=\"window.location.href = 'submissionedit.php?id={$submission['id']}';\"><img src=\"style/icons/edit.png\"> Editar</button>";
		echo "</td>";
		echo "<td style=\"text-align:center\"><button type=\"button\" onclick=\"submission_view({$submission['id']});\"><img src=\"style/icons/view.png\"> Visualizar</button></td>";
		echo "<td style=\"text-align:center\"><button type=\"button\" onclick=\"submission_review({$submission['id']});\"><img src=\"style/icons/revision.png\"> Revisar</button></td>";
		echo "</tr>";
	}
	?>
	</table>

	<script>
	function submission_change_status()
	{
		var selected_submissions = [];
		var checkboxes = document.getElementsByName('submission[]');
		for(var i=0;i < checkboxes.length; i++)
			if (checkboxes[i].checked) selected_submissions.push(checkboxes[i].value);
		if (selected_submissions.length == 0)
		{
			alert('Nenhuma submissão selecionada.'); // TODO G11n
		}
		else
		{
			form = document.getElementById('change_status_form');
			for(var j=0;j < selected_submissions.length; j++)
			{
				var2 = document.createElement('input');
				var2.setAttribute('type', 'hidden');
				var2.setAttribute('name', 'submission_id[]');
				var2.setAttribute('value', selected_submissions[j]);
				form.appendChild(var2);
			}
			document.getElementById('change_status_dialog').style.display = 'block';
		}
	}

	function submission_export()
	{
		var selected_submissions = [];
		var checkboxes = document.getElementsByName('submission[]');
		for(var i=0;i < checkboxes.length; i++)
			if (checkboxes[i].checked) selected_submissions.push(checkboxes[i].value);
		if (selected_submissions.length == 0)
		{
			alert('Nenhuma submissão selecionada.'); // TODO G11n
		}
		else
		{
			form = document.createElement('form');
			form.setAttribute('method', 'POST');
			form.setAttribute('action', 'submissionsexport.php');
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'submission_definition_id');
			var1.setAttribute('value', <?php echo $submission_definition['id'];?>);
			form.appendChild(var1);
			for(var j=0;j < selected_submissions.length; j++)
			{
				var2 = document.createElement('input');
				var2.setAttribute('type', 'hidden');
				var2.setAttribute('name', 'submission_id[]');
				var2.setAttribute('value', selected_submissions[j]);
				form.appendChild(var2);
			}
			document.body.appendChild(form);
			form.submit();
		}
	}

	function set_reviewer()
	{
		var selected_submissions = [];
		var checkboxes = document.getElementsByName('submission[]');
		for(var i=0;i < checkboxes.length; i++)
			if (checkboxes[i].checked) selected_submissions.push(checkboxes[i].value);
		if (selected_submissions.length == 0)
		{
			alert('Nenhuma submissão selecionada.'); // TODO G11n
		}
		else
		{
			// TODO colocar num array e não stringfy
			document.getElementById('submissions_to_set_reviewer').value = JSON.stringify(selected_submissions);
			document.getElementById('set_reviewer_dialog').style.display = 'block';
		}
	}

	function submission_view(submission_id)
	{
		var xhr = new XMLHttpRequest();
			xhr.open('GET', 'service/submission_view.php?id=' + submission_id);
			xhr.onload = function() {
			    if (xhr.status === 200) {
					var data = JSON.parse(xhr.responseText);
					document.getElementById('viewer_content').innerHTML = data['formatted_content'];
			    }
			    else {
					// HTTP Error message
					var paragraph = document.createElement('p'); 
					paragraph.textContent = '<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status;
					document.getElementById('viewer_content').appendChild(paragraph);
			    }
			};
			xhr.send();
			document.getElementById('viewer').style.display = 'block';
	}

	function submission_review(submission_id)
	{
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/submission_review_controls.php?id=' + submission_id);
		xhr.onload = function() {
		    if (xhr.status === 200) {
			document.getElementById('submission_id_review').value = submission_id;
			document.getElementById('submission_review_container').innerHTML = xhr.responseText;
		    }
		    else {
				// HTTP Error message
				var paragraph = document.createElement('p'); 
				paragraph.textContent = '<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status;
				document.getElementById('submission_review_container').appendChild(paragraph);
		    }
		};
		xhr.send();
		document.getElementById('review_dialog').style.display = 'block';
	}

	function toggle(source, elementname)
	{
		var checkboxes = document.getElementsByName(elementname);
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
	</script>

	<?php
	$eve->output_html_footer();
}
?>
