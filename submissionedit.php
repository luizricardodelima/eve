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
else if (!isset($_GET['id']) || !$eveSubmissionService->submission_get($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
// Checking user permissions: User must be admin or final reviewer of given submission definition // TODO IMPLEMENT!
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{	
	switch ($_POST['action'])
	{
		case 'update':
			$validation_errors = $eveCustomInputService->custom_input_validate(json_decode($_POST['structure']), $_POST['content'], $_FILES['content'], 'upload/submission/');
			if (!empty($validation_errors))
			{
				// There are validation errors. Redirecting to this page showing where the errors are
				$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&validation=".serialize($validation_errors));				
			}
			else // TODO: Duplicate on submission.php and submissions. the validation should occur on evecustominput.class
			{
				// No validation errors. Uploading files and populating $_POST['content'] with uploaded filenames
				$file_errors = array();
				foreach(json_decode($_POST['structure']) as $i => $structure_item) switch($structure_item->type)
				{
					case 'file':
						$random_filename = md5(uniqid(rand(), true)); // Generating a random filename
						$extension = pathinfo($_FILES['content']['name'][$i], PATHINFO_EXTENSION);
						if (move_uploaded_file($_FILES['content']['tmp_name'][$i], "upload/submission/$random_filename.$extension"))
							$_POST['content'][$i] = "upload/submission/$random_filename.$extension";
						else
							$file_errors[$i] = EveCustomInputService::CUSTOM_INPUT_VALIDATION_ERROR_FILE_ERROR;
						break;
				}
				if(!empty($file_errors))
				{
					// Errors on uploading files. Redirecting to this page showing where the errors are
					$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&file_error=".serialize($file_errors));
				}
				else
				{
					// Everything is right so far. Creating new submission
					$msg = $eveSubmissionService->submission_update($_GET['id'], json_encode($_POST['content']));
					if ($msg == EveSubmissionService::SUBMISSION_UPDATE_SUCCESS)					
						$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&success=1");
					else					
						$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&message=$msg");
				}
			}
			break;
	} 
}
// If there's a valid session, and the current user is administrator and there are no
// actions, display the regular listing page.
else
{
	$submission = $eveSubmissionService->submission_get($_GET['id']);
	$submissiondefinition = $eveSubmissionService->submission_definition_get($submission['submission_definition_id']);
	
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submissiondefinition['description'], "submissions.php?id={$submissiondefinition['id']}", "ID {$submission['id']}", null);

	if (isset($_GET['success'])) switch ($_GET['success'])
	{
		case '1':
			$eve->output_success_message("Submissão atualizada com sucesso");
			break;
	}
	if (isset($_GET['validation']))
	{
		//There are validation errors on revisition.
		$validation_errors = unserialize($_GET['validation']);
		$validation_errors_messages = array();
		$structure = json_decode($submission['structure']);
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
		// TODO usar função internacionalizável // TODO File error handling
		$eve->output_error_list_message($validation_errors_messages);
	}

	?>
	<!--<div class="section"></div>-->

	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" enctype="multipart/form-data" class="user_dialog_panel_large">
	<input type="hidden" name="action" value="update"/>
	<input type="hidden" name="structure" value="<?php echo htmlentities($submission['structure']);?>"/>
	<p></p>	
	<?php
	$submission_structure = json_decode($submission['structure']);
	$submission_content = json_decode($submission['content']);
	$eveCustomInputService->custom_input_output_html_controls($submission_structure, 'content', $submission_content);
	?>
	<button type="submit" class="submit">Salvar</button>
	<p></p>
	</form>
	
	<?php
	$eve->output_html_footer();
}
?>
