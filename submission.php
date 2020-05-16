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

	// Header	
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $submission_definition['description'], null);

	// There are success/error messages
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
	// There are validation error messages
	// TODO Make the messages in DynamicForm Lib AND DO NOT RELOAD THE PAGE IF VALIDATION FAILS
	if (isset($_GET['validation']))
	{
		$validation_errors = unserialize($_GET['validation']);
		$validation_errors_messages = array();
		foreach ($validation_errors as $pos => $array) foreach($array as $validation_error) switch ($validation_error)
		{
			case DynamicFormValidationError::MANDATORY:
				$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Obrigatório";
				break;
			case DynamicFormValidationError::UNDER_MIN_WORDS:
				$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Não tem o número mínimo de palavras exigido.";
				break;
			case DynamicFormValidationError::OVER_MAX_WORDS:
				$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Ultrapassou o número máximo de palavras permitido.";
				break;
			case DynamicFormValidationError::FILE_ERROR:
			case DynamicFormValidationError::FILE_UPLOAD_ERROR:
				$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Erro ao fazer upload do arquivo.";
				break;
			case DynamicFormValidationError::FILE_EXCEEDED_SIZE:
				$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Arquivo maior que o permitido.";
				break;
			case DynamicFormValidationError::FILE_WRONG_TYPE:
				$validation_errors_messages[] = "Campo {$structure[$pos]->description}: Tipo de arquivo não permitido.";
				break;
		}
		// TODO certificar que seja função internacionalizável
		$eve->output_error_list_message($validation_errors_messages);
	}

	$submission_deadline = strtotime($submission_definition['deadline']);
	$within_the_deadline = (time() < $submission_deadline);
	$submissions_sent_by_user = $eveSubmissionService->submission_list($_GET['id'], 'owner', $_SESSION['screenname']);

	// Displaying previous submissions // TODO: Consistent style of "wiew" dialog
	if (!empty($submissions_sent_by_user))
	{
		?>
		<div id="viewer" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
		<div style="background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;" >
		<button type="button" style="background-color:#333; color: white; float: right; border-radius: 0;" onclick="document.getElementById('viewer').style.display = 'none';"> X </button>
		<div id="viewer_content" style="padding: 20px; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;"></div>
		</div></div>

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
		function view_submission(submission_id, field) 
		{
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'service/submission_view.php?id=' + submission_id);
			xhr.onload = function() {
			    if (xhr.status === 200) {
					var data = JSON.parse(xhr.responseText);
					document.getElementById('viewer_content').innerHTML = data[field];
			    }
			    else {
					document.getElementById('viewer_content').innerHTML = '<p>Erro na requisição: Erro HTTP ' + xhr.status + '</p>';
			    }
			};
			xhr.send();
			document.getElementById('viewer').style.display = 'block';
		}

		</script>
		<?php
		if ($submission_definition['allow_multiple_submissions'])
		{
			// Multiple submissions allowed. Display a list
			?>
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
				echo "<td><button type=\"button\" onclick=\"view_submission({$submission['id']},'formatted_content');\"><img src=\"style/icons/view.png\"></button></td>";
				echo "<td>";			
				if ($submission['revision_status'] >= 2)	
					echo "<button type=\"button\" onclick=\"view_submission({$submission['id']},'formatted_review');\"><img src=\"style/icons/revision.png\"></button>";			
				echo "</td>";			
				echo "<td>";
				// Delete option is shown if whithin deadline (or no deadline at all)  and is not reviewed.
				if ((!$submission_definition['deadline'] || $within_the_deadline) && $submission['revision_status'] == 0)
					echo "<button type=\"button\" onclick=\"delete_submission({$submission['id']});\"><img src=\"style/icons/delete.png\" /></button>";
				echo "</td>";
				echo "</tr>";
			}
			?>
			</table>
			<?php
		}
		else
		{
			// Only one submission allowed, display it
			?>
			<div class="section">Envio em <?php echo $eveG11n->compact_date_time_format(strtotime($submissions_sent_by_user[0]['date'])); ?>
				<button onclick="alert('todo')">Apagar</button>
				<button onclick="alert('todo')">Resultado da Revisão</button>
			</div>
			<?php
			$dynamicForm = new DynamicForm($submissions_sent_by_user[0]['structure'], json_decode($submissions_sent_by_user[0]['content']));
			echo $dynamicForm->getHtmlFormattedContent('data_table');
			
			
			 ?>
			<?php
		}
	}

	// A new submission is allowed if the submission definition allows multiple submissions or there is no submission sent
	$new_submission_allowed = $submission_definition['allow_multiple_submissions'] || (count($submissions_sent_by_user) ==  0);
	
	// Displaying submission form, if we are before the deadline or user has a 'submission_after_deadline' access restriction (permission, in this case)
	$submission_after_deadline = $eveSubmissionService->submission_after_deadline_allowed($_GET['id'], $_SESSION['screenname']);

	// Showing the submission form if $new_submission_allowed (previously explainded) AND under the three cases below:
	// If there is no deadline
	// If there is deadline and it is within the deadline
	// If there is deadline, out of date, but there's a special permission for user
	echo "<div class=\"section\">Novo envio</div>";
	if ($new_submission_allowed && (!$submission_definition['deadline'] || $within_the_deadline || $submission_after_deadline))
	{	
		?>
		<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" enctype="multipart/form-data" class="user_dialog_panel_large">
		<p><?php if ($submission_definition['deadline'])
		{
			echo "Prazo para envio: "; // TODO g11n
			echo $eveG11n->full_date_time_format($submission_deadline);
			if($submission_after_deadline) echo "&nbsp;(O prazo para envio foi prorrogado para você)."; // TODO g11n
		}
		?>
		</p>

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
		<!--  A new submission is not possible because there is a submission sent -->
		<div class="user_dialog_panel_large">
		<p>Envio já realizado.</p>
		</div>
		<?php
	}
	else
	{
		?>
		<!--  A new submission is not possible because there is a submission sent -->
		<div class="user_dialog_panel_large">
		<p>Prazo encerrado para envios.</p>
		</div>
		<?php
	}

	// Footer
	$eve->output_html_footer();
}?>
