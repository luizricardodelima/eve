<?php
session_start();

require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'lib/dynamicform/dynamicformvalidationerror.class.php';
require_once 'eve.class.php';
require_once 'eveg11n.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if (!$eveSubmissionService->submission_definition_user_access_permitted($_GET['id'], $_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
else
{
	// Loading submission definition data
	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);
	
	DynamicFormHelper::$locale = $eve->getSetting('system_locale');
	$structure = $submission_definition['submission_structure'];
	$content = (isset($_POST) && isset($_POST['submission_content'])) ? $_POST['submission_content'] : null;
	$files = (isset($_FILES) && isset($_FILES['submission_content'])) ? $_FILES['submission_content'] : null;
	$dynamicForm = new DynamicForm($structure, $content, $files, 'upload/submission/');

	// User actions
	if (isset ($_POST['action'])) switch ($_POST['action'])
	{
		case 'delete':
			$msg = $eveSubmissionService->submission_delete($_POST['submission_id'], $_SESSION['screenname']);
			$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&message=$msg");
			break;
		case 'submission':
			$validationErrors = $dynamicForm->validate();
			if (!empty($validation_errors)) // There are validation errors
			{
				$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&validation=".serialize($validation_errors));				
			}
			else // Everything is correct, creating new submission
			{
				$msg = $eveSubmissionService->submission_create($_GET['id'], $_SESSION['screenname'], $_POST['submission_structure'], json_encode($_POST['submission_content']));
				if ($msg == EveSubmissionService::SUBMISSION_CREATE_SUCCESS)					
					$eve->output_redirect_page("userarea.php?systemmessage=submission.sent");
				else					
					$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&message=$msg");
			}
			break;
	}

	// Loading helper classes	
	$eveG11n = new EveG11n($eve);

	// Header and (sucess or error) messages	
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $submission_definition['description'], null);

	// Success/Error messages
	if (isset($_GET['message'])) switch ($_GET['message'])
	{
		case EveSubmissionService::SUBMISSION_CREATE_ERROR_SQL:
			$eve->output_success_message("Erro no banco de dados ao realizar envio."); //TODO g11n
			break;
		case EveSubmissionService::SUBMISSION_DELETE_ERROR_SQL:
			$eve->output_error_message("Erro no banco de dados ao apagar submissão."); //TODO g11n
			break;
		case EveSubmissionService::SUBMISSION_DELETE_ERROR_FORBIDDEN:
			$eve->output_error_message("Erro ao apagar submissão. Permissão negada."); //TODO g11n
			break;	
		case EveSubmissionService::SUBMISSION_DELETE_ERROR_NOT_FOUND:
			$eve->output_error_message("Erro ao apagar submissão. Submissão não encontrada."); //TODO g11n
			break;		
		case EveSubmissionService::SUBMISSION_DELETE_SUCCESS:
			$eve->output_success_message("Submissão apagada com sucesso."); //TODO g11n
			break;
	}
	if (isset($_GET['validation']))
	{
		//There are validation errors.
		$validation_errors = unserialize($_GET['validation']);
		$validation_errors_messages = array();
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
	}

	$submission_deadline = strtotime($submission_definition['deadline']);
	$within_the_deadline = (time() < $submission_deadline);

	// TODO: Only display the list if allow multiple submissions. 
	// TODO: Display HTML FORM WITH sent data otherwise

	// Displaying sent submissions
	$submissions_sent_by_user = $eveSubmissionService->submission_list($_GET['id'], 'owner', $_SESSION['screenname']);
	if (!empty($submissions_sent_by_user))
	{
		?>
		<div class="section">Envios</div>

		<dialog id="submission_view_dialog" style="position: fixed; top: 0; left: 0; width: 99%; height: 99%;">
		<div style="width:99%; height: 1.5em;"> <button type="button" onclick="document.getElementById('submission_view_dialog').close();">Fechar</button></div>	
		<div style="width:99%; height: calc(99% - 2em); overflow-y: scroll;" id="submission_view_container"></div>
		</dialog>

		<script>
		function delete_submission(submission_id) 
		{
			if (confirm('Tem certeza que você quer apagar este envio? Esta ação não poderá ser desfeita.'))
			{
				form = document.createElement('form');
				form.setAttribute('method', 'POST');
				form.setAttribute('action', '<?php echo basename(__FILE__)."?id=".$_GET['id'];?>');
				var1 = document.createElement('input');
				var1.setAttribute('type', 'hidden');
				var1.setAttribute('name', 'action');
				var1.setAttribute('value', 'delete');
				form.appendChild(var1);
				var2 = document.createElement('input');
				var2.setAttribute('type', 'hidden');
				var2.setAttribute('name', 'submission_id');
				var2.setAttribute('value', submission_id);
				form.appendChild(var2);
				document.body.appendChild(form);
				form.submit();  
			}
			return false;
		}
		function view_submission(submission_id) 
		{
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'service/submission_view.php?id=' + submission_id);
			xhr.onload = function() {
			    if (xhr.status === 200) {
				var data = JSON.parse(xhr.responseText);
				var container = document.getElementById('submission_view_container');
				container.innerHTML = data['formatted_content'];
			    }
			    else {
				document.getElementById('submission_view_container').innerHTML = '<p>Erro na requisição: Erro HTTP ' + xhr.status + '</p>';
			    }
			};
			xhr.send();
			document.getElementById('submission_view_dialog').show();
		}

		function submission_review(submission_id)
		{
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'service/submission_view.php?id=' + submission_id);
			xhr.onload = function() {
			    if (xhr.status === 200) {
				var data = JSON.parse(xhr.responseText);
				var container = document.getElementById('submission_view_container');
				container.innerHTML = data['formatted_revision'];
			    }
			    else {
				document.getElementById('submission_view_container').innerHTML = '<p>Erro na requisição: Erro HTTP ' + xhr.status + '</p>';
			    }
			};
			xhr.send();
			document.getElementById('submission_view_dialog').show();
		}
		</script>
		<table class="data_table">
		<tr>
		<th width="10%">ID</th>
		<th width="50%">Data de envio</th>
		<th width="30%">Status da avaliação</th>
		<th width="10%" colspan="3"><?php echo $eve->_('common.table.header.options');?></th>
		</tr>
		<?php
				
		foreach ($submissions_sent_by_user as $submission)		
		{	
			$formatted_date = $eveG11n->compact_date_time_format(strtotime($submission['date']));
			echo "<tr>";
			echo "<td style=\"text-align:center;\">{$submission['id']}</td>";
			echo "<td style=\"text-align:center;\">{$formatted_date}</td>";
			echo "<td style=\"text-align:center\">".$eve->_("submission.revision_status.ownerview.{$submission['revision_status']}")."</td>";
			echo "<td style=\"text-align:center\"><button type=\"button\" onclick=\"view_submission({$submission['id']});\"><img src=\"style/icons/view.png\"></button></td>";
			echo "<td style=\"text-align:center\">";			
			if ($submission['revision_status'] >= 2)	
				echo "<button type=\"button\" onclick=\"submission_review({$submission['id']});\"><img src=\"style/icons/revision.png\"></button>";			
			echo "</td>";			
			echo "<td style=\"text-align:center\">";
			// Showing delete option only if whithin deadline (or no deadline at all) to avoid possible modifications after the deadline.
			if (!$submission_definition['deadline'] || $within_the_deadline)
				echo "<button type=\"button\" onclick=\"delete_submission({$submission['id']});\"><img src=\"style/icons/delete.png\" /></button>";
			echo "</td>";
			echo "</tr>";
		}
		?>
		</table>
		<?php	
	}
	
	// A new submission is allowed if the submission definition allows multiple submissions or there is no submission sent
	$new_submission_allowed = $submission_definition['allow_multiple_submissions'] || (count($submissions_sent_by_user) ==  0);
	
	// Displaying submission form, if we are before the deadline or user has a 'submission_after_deadline' access restriction (permission, in this case)
	$submission_after_deadline = $eveSubmissionService->submission_after_deadline_allowed($_GET['id'], $_SESSION['screenname']);

	// Showing the submission form if $new_submission_allowed (previously explainded) AND under the three cases below:
	// If there is no deadline
	// If there is deadline and it is within the deadline
	// If there is deadline, out of date, but there's a special permission for user
	if ($new_submission_allowed && (!$submission_definition['deadline'] || $within_the_deadline || $submission_after_deadline))
	{	
		?>
		<div class="section">Novo envio
		<?php if ($submission_definition['deadline'])
		{
			echo " | Prazo para envio: "; // TODO g11n
			echo $eveG11n->full_date_time_format($submission_deadline);
			if($submission_after_deadline) echo "&nbsp;(O prazo para envio foi prorrogado para você)."; // TODO g11n
		}
		?>
		</div>

		<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" enctype="multipart/form-data" class="user_dialog_panel_large">
		<?php echo $submission_definition['information']; ?>
		<?php echo $dynamicForm->outputControls('submission_structure', 'submission_content') ?>
		<input type="hidden" name="action" value="submission"/>
		<button type="submit" class="submit">Enviar</button>
		<p></p>
		</form>
		<?php
	}
	else if (!$new_submission_allowed)
	{
		?>
		<div class="section">Novo envio</div>
		<p>Envio já realizado.</p> 
		<?php
	}
	else
	{
		?>
		<div class="section">Novo envio</div>
		<p>Prazo encerrado para envios.</p> 
		<?php
	}

	$eve->output_html_footer();
}?>
