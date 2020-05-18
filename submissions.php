<?php
session_start();
require_once 'eve.class.php';
require_once 'evecustominputservice.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
$eveCustomInputService = new EveCustomInputService($eve);

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
	

// If there's a valid session, and the current user is administrator and there are no
// actions, display the regular listing page.

	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);
	
	$access_mode = null;
	if 	($eve->is_admin($_SESSION['screenname']) && $_GET['access_mode'] != 'final_reviewer' && $_GET['access_mode'] != 'reviewer')
		$access_mode = 'admin';
	else if ($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $_GET['id'])  && $_GET['access_mode'] != 'reviewer')
		$access_mode = 'final_reviewer';
	else if ($eveSubmissionService->is_reviewer($_SESSION['screenname'], $_GET['id']))
		$access_mode = 'reviewer';
	else
		$access_mode = 'owner';

	$message == null;
	$validation_errors == null;
	
	if (isset($_POST['action'])) switch ($_POST['action'])
	{ // TODO RESOLVE SECURITY FLAWS: 'set_revierer' only for admins and final_reviewers - revision: only for admins, final_reviewers and reviewers
		// TODO MAYBE DO IT IN THE SERVICES...
		// TODO submission services have to return the correct messages! Reloading is probably not necessary anymore
		case 'change_status':
			$message = $eveSubmissionService->submission_change_revision_status($_POST['submission_id'], $_POST['status']);
			// $eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}&success=3");
			break;
		case 'set_reviewer':
			foreach ($_POST['submission'] as $submission_id) // TODO SUM UP THE MESSAGES!
				$message = $eveSubmissionService->submission_set_reviewer($submission_id, $_POST['reviewer']);
			// $eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}&success=1");
			break;
		case 'revision':
			$validation_errors = $eveCustomInputService->custom_input_validate(json_decode($_POST['revision_structure']), $_POST['revision_content'], $_FILES['revision_content'], 'upload/submission');
			if (empty($validation_errors))
			{
				$message = $eveSubmissionService->submission_review($_POST['submission_id'], json_decode($_POST['revision_structure']), $_POST['revision_content'], $_SESSION['screenname']);
				//$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}&success=2");
			}
			else
			{
				//$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}&validation=".serialize($validation_errors));
			}
			break;
	} 

	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submission_definition['description'], null);

	// Success/error messages
	if (!is_null($message)) $eve->output_service_message($message);
	// Validation error messages
	if (!empty($validation_errors))	$eve->output_error_list_message($validation_errors);
	
	/* THIS BLOCKED WILL BE REMOVED BECAUSE ERRROR MESSAGES ARE THROWN BY SERVICES AND
	 * TRANSLATED IN EVE CLASS. VALIDATION MESSAGES ARE THROWN ALREADY TRANSLATED BY
	 * DYNAMIC FORM
	//TODO: CASE 3
	if (isset($_GET['success'])) switch ($_GET['success'])
	{
		case '1':
			$eve->output_success_message("Avaliador atribuído com sucesso");
			break;
		case '2':
			$eve->output_success_message("Submissão avaliada com sucesso");
			break;
	}
	// Necessary because revision is done here. This will go to a separate screen
	if (isset($_GET['validation']))
	{
		//There are validation errors on revisition.
		$validation_errors = unserialize($_GET['validation']);
		$validation_errors_messages = array();
		$structure = json_decode($submission_definition['revision_structure']);
		foreach ($validation_errors as $pos => $validation_error)
		{
			switch ($validation_error)
			{
				case EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_MANDATORY:
					$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Obrigatório";
					break;
				case EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_UNDER_MIN_WORDS:
					$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Não tem o número mínimo de palavras exigido.";
					break;
				case EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_OVER_MAX_WORDS:
					$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Ultrapassou o número máximo de palavras permitido.";
					break;
				case EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_FILE_ERROR:
					$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Erro ao fazer upload do arquivo.";
					break;
				case EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_FILE_EXCEEDED_SIZE:
					$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Arquivo maior que o permitido.";
					break;
				case EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_FILE_WRONG_TYPE:
					$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Tipo de arquivo não permitido.";
					break;
			}
			
		}
		// TODO usar função internacionalizável
		$eve->output_error_list_message($validation_errors_messages);
		
	}*/

	?>
	<div class="section">
	<!-- TODO --> <button type="button" onclick="alert('Implement - Filtro no DynamicForm, deixar lista em javascript client-side e atualizar view')">Filtrar</button>
	<?php if ($access_mode == 'admin' || $access_mode == 'final_reviewer') { ?><button type="button" onclick="alert('Implement - Avaliador final pode gerenciar avaliadores - passar referencia para a tela de gerenciamento de avaliadores pra voltar a esta tela depois');">Gerenciar avaliadores</button><?php } ?>
	<!-- TODO --><?php if ($access_mode == 'admin' || $access_mode == 'final_reviewer') { ?><button type="button" onclick="set_reviewer_show_dialog();">Atribuir avaliador</button><?php } ?>
	<!-- TODO PARA admins, final_reviewers and reviewers --><button type="button" onclick="alert('To be implemented');">Exportar</button>
	<?php if ($access_mode == 'admin' || $access_mode == 'final_reviewer') { ?><button type="button" onclick="window.location.href = 'submissionsexport.php?id=<?php echo $_GET['id'];?>';">Exportar tudo</button><?php } ?>
	
	</div>
	
	<!--
	<dialog id="submission_view_dialog" style="position: fixed; top: 0; left: 0; width: 99%; height: 99%;">
	<div style="width:99%; height: 1.5em;"> <button type="button" onclick="document.getElementById('submission_view_dialog').close();">Fechar</button></div>	
	<div style="width:99%; height: calc(99% - 2em); overflow-y: scroll;" id="submission_view_container"></div>
	</dialog>
	-->

	<dialog id="submission_review_dialog" style="position: fixed; top: 0; left: 0; width: 99%; height: 99%;">
	<div style="width:99%; height: 1.5em;"> <button type="button" onclick="document.getElementById('submission_review_dialog').close();">Fechar</button></div>	
	<div style="width:99%; height: calc(99% - 2em); overflow-y: scroll;" >	
	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}";?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="action" value="revision"/>
	<input type="hidden" name="submission_id" id="submission_id_review"/>
	<input type="hidden" name="revision_structure" value="<?php echo htmlentities($submission_definition['revision_structure']);?>"/>
	<div id="submission_review_container" class="user_dialog_panel_large"></div>
	</form>
	</div>
	</dialog>

	<form id="change_status_form" action="<?php echo basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}";?>" method="post">
	<input type="hidden" name="action" value="change_status"/>
	<input type="hidden" name="submission_id" id="change_status_form_submission_id"/>
	<dialog id="change_status_dialog">
	<label id="change_status_label"></label>
	<select name="status">
	<?php
	for ($i = 0; $i <= 2; $i++) //TODO Implement a enum with status codes in eve submission service
	{
		echo "<option value=\"$i\">".$eve->_("submission.revision_status.$i")."</option>";
	}
	?>
	</select>
	<button type="submit">Alterar</button>
	<button type="button" onclick="document.getElementById('change_status_dialog').close();">Cancelar</button>
	</dialog>	
	</form>

	<form id="submissions_form" action="<?php echo basename(__FILE__)."?id={$_GET['id']}&access_mode={$access_mode}";?>" method="post">
	<input type="hidden" name="action" id="action_hidden_value"/>
	<dialog id="set_reviewer_dialog" style="display:none">
	<select name="reviewer">
	<?php
	$reviewers = $eveSubmissionService->submission_definition_reviewers($_GET['id'], 'reviewer');
	foreach ($reviewers as $reviewer) echo "<option value=\"{$reviewer['email']}\">{$reviewer['name']}</option>";
	?>
	</select>
	<button type="button" onclick="set_reviewer();">Atribuir</button>
	<button type="button" onclick="document.getElementById('set_reviewer_dialog').style.display = 'none';">Cancelar</button>
	</dialog>

	<table class="data_table">
	<tr>
	<th style="width:2%"><input type="checkbox" onClick="toggle(this, 'submission[]')"/></th>
	<th style="width:4%">ID</th>
	<th style="width:14%">Data</th>
	<?php if ($access_mode != 'reviewer') { ?><th style="width:20%">E-mail do autor</th><?php } // TODO HARDCODED - Reviewers dont see who has sent the submission ?>
	<th style="width:20%">E-mail do avaliador</th>
	<th style="width:20%">Status da avaliação</th>
	<th style="width:10%" colspan="5"><?php echo $eve->_('common.table.header.options');?></th>		
	</tr>
	<?php

	$submissions = array();
	switch ($access_mode)
	{
		case 'admin':
			$submissions = $eveSubmissionService->submission_list($_GET['id'], 'admin');
			break;
		case 'final_reviewer':
			$submissions = $eveSubmissionService->submission_list($_GET['id'], 'final_reviewer');
			break;
		case 'reviewer':
			$submissions = $eveSubmissionService->submission_list($_GET['id'], 'reviewer', $_SESSION['screenname']);
			break;
		case 'owner':
			$submissions = $eveSubmissionService->submission_list($_GET['id'], 'owner', $_SESSION['screenname']);
			break;
	}

	
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
			if ($access_mode == 'admin') echo "<button type=\"button\" onclick=\"window.location.href = 'submissionedit.php?id={$submission['id']}';\"><img src=\"style/icons/edit.png\"></button>";
		echo "</td>";

		echo "<td style=\"text-align:center\"><button type=\"button\" onclick=\"submission_show(this, {$submission['id']});\"><img src=\"style/icons/view.png\"></button></td>";

		//echo "<td style=\"text-align:center\"><button type=\"button\" onclick=\"submission_view({$submission['id']});\"><img src=\"style/icons/view.png\"></button></td>";
		
		echo "<td style=\"text-align:center\"><button type=\"button\" onclick=\"submission_review({$submission['id']});\"><img src=\"style/icons/revision.png\"></button></td>";
		echo "<td>";		
		if ($access_mode == 'admin' || $access_mode == 'final_reviewer')
			echo "<button type=\"button\" onclick=\"change_status({$submission['id']});\"><img src=\"style/icons/revision_change_status.png\"></button>";
		echo "</td>";		
		echo "</tr>";
	}
	?>
	</table>
	</form>
	<script>
	function change_status(submission_id)
	{
		document.getElementById('change_status_form_submission_id').value = submission_id;
		document.getElementById('change_status_label').innerHTML = "Alterar o status de " + submission_id + " para: ";
		document.getElementById('change_status_dialog').show();
		return false;
	}

	function set_reviewer()
	{
		document.getElementById('action_hidden_value').value = "set_reviewer";
		document.forms['submissions_form'].submit();
		return false;
	}

	function set_reviewer_show_dialog()
	{
		var selected_checkboxes = 0;
		var checkboxes = document.getElementsByName('submission[]');
		for(var i=0, n=checkboxes.length;i<n;i++)
			if (checkboxes[i].checked) selected_checkboxes++;
		
		if (selected_checkboxes == 0)
			alert('É necessário selecionar as submissões primeiro.');
		else
			document.getElementById('set_reviewer_dialog').style.display = 'block';
		return false;
	}

	// TODO: This code probably wont work anymore. change for the code used in submisson.php
	function submission_show(el, submission_id)
	{
		alert('reimplement!');
		/*
		el.disabled = true;
		var currTR = el.parentNode.parentNode;
		var newTR = document.createElement("tr");
		newTR.className = currTR.className;	
		var newTD = document.createElement("td");
		var closeButton = document.createElement("button");
		closeButton.innerHTML = "&#10060; Fechar"; // TODO g11n
		closeButton.type = "button";
		closeButton.onclick = function(x) { el.disabled = false; currTR.parentNode.removeChild(newTR) }
		closeButton.style = "float: right;";
		
		newTD.colSpan = 10;
		newTD.appendChild(closeButton);

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/submission_view.php?id=' + submission_id);
		xhr.onload = function() {
		    if (xhr.status === 200) {
			var data = JSON.parse(xhr.responseText);
			var tbl = document.createElement("table");
			for (var i in data.formatted_content)
			{
				var tr = document.createElement("tr");
				tr.innerHTML = "<td><strong>" + i + "</strong></td><td>" + data.formatted_content[i] + "</td>";
				tbl.appendChild(tr);
			}
			newTD.appendChild(tbl);
		    }
		    else {
		        newTD.innerHTML = '<p>Erro na requisição: Erro HTTP ' + xhr.status + '</p>';
		    }
		};
		xhr.send();
		newTR.appendChild(newTD);
		currTR.parentNode.insertBefore(newTR, currTR.nextSibling);*/
	}

/*
	function submission_view(submission_id)
	{
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/submission_view.php?id=' + submission_id);
		xhr.onload = function() {
		    if (xhr.status === 200) {
			var data = JSON.parse(xhr.responseText);
			const container = document.getElementById('submission_view_container');
			while (container.firstChild) container.removeChild(container.firstChild);
			var tbl = document.createElement("table");
			tbl.className = 'data_table';
			for (var i in data.formatted_content)
			{
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				td1.innerHTML = i;
				tr.appendChild(td1);
				var td2 = document.createElement("td");
				td2.innerHTML = data.formatted_content[i];
				tr.appendChild(td2);			
				tbl.appendChild(tr);
			}
			container.appendChild(tbl);
		    }
		    else {
		        document.getElementById('submission_view_container').innerHTML = '<p>Erro na requisição: Erro HTTP ' + xhr.status + '</p>';
		    }
		};
		xhr.send();
		document.getElementById('submission_view_dialog').show();
	}
*/
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
		        document.getElementById('submission_review_container').innerHTML = '<p>Erro na requisição: Erro HTTP ' + xhr.status + '</p>';
		    }
		};
		xhr.send();
		document.getElementById('submission_review_dialog').show();
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
