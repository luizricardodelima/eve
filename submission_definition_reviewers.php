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
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'add_reviewer':
			$msg = $eveSubmissionService->submission_definition_reviewer_add($_GET['id'], $_POST['email'], 'reviewer');
			break;
		case 'add_final_reviewer':
			$msg = $eveSubmissionService->submission_definition_reviewer_add($_GET['id'], $_POST['email'], 'final_reviewer');
			break;
		case 'delete_reviewer':
			$msg = $eveSubmissionService->submission_definition_reviewer_delete($_POST['id']);
			break;
	}
	$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&message=$msg");
}
// If there's a valid session, and the current user is administrator and there are no
// actions, display the regular listing page.
else
{
	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submission_definition['description'], "submission_definition.php?id={$_GET['id']}", $eve->_('submission_definition.reviewers'), null);

	if (isset($_GET['message'])) switch ($_GET['message'])
	{
		// TODO g11n
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_INVALID_EMAIL:
			$eve->output_error_message("Erro ao adicionar avaliador: e-mail inválido.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_USER_DOES_NOT_EXIST:
			$eve->output_error_message("Erro ao adicionar avaliador: usuário não existe.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_INVALID_TYPE:
			$eve->output_error_message("Erro ao adicionar avaliador: tipo inválido.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_REVIEWER_ALREADY_EXISTS:
			$eve->output_error_message("Erro ao adicionar avaliador: avaliador já existe.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_ADD_ERROR_SQL:
			$eve->output_error_message("Erro no banco de dados ao adicionar avaliador.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_ADD_SUCCESS:
			$eve->output_success_message("Sucesso ao adicionar avaliador.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_DELETE_ERROR_SQL:
			$eve->output_error_message("Erro no banco de dados ao apagar avaliador.");
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_REVIEWER_DELETE_SUCCESS:
			$eve->output_success_message("Sucesso ao apagar avaliador.");
			break;
	}

	?>

	<div class="section">		
	<button type="button" onclick="add_reviewer();">Adicionar avaliador</button>
	<button type="button" onclick="add_final_reviewer();">Adicionar avaliador final</button>
	</div>

	<form method="post" id="submission_definition_reviewers_form">
	<input type="hidden" name="action" id="submission_definition_reviewers_action"/>
	<input type="hidden" name="email" id="submission_definition_reviewers_email"/>
	</form>

	<form method="post" id="submission_definition_reviewers_delete_form">
	<input type="hidden" name="action" value="delete_reviewer"/>
	<input type="hidden" name="id" id="submission_definition_reviewers_delete_id"/>
	</form>

	<script>
	function add_reviewer()
	{
		var reviewer_email = prompt("Digite o e-mail do avaliador");
		if (reviewer_email != null)
		{
			document.getElementById('submission_definition_reviewers_action').value='add_reviewer';
			document.getElementById('submission_definition_reviewers_email').value=reviewer_email;
			document.forms['submission_definition_reviewers_form'].submit();
		}
		return false;
	}

	function add_final_reviewer()
	{
		var reviewer_email = prompt("Digite o e-mail do avaliador final");
		if (reviewer_email != null)
		{
			document.getElementById('submission_definition_reviewers_action').value='add_final_reviewer';
			document.getElementById('submission_definition_reviewers_email').value=reviewer_email;
			document.forms['submission_definition_reviewers_form'].submit();
		}
		return false;
	}

	function delete_reviewer(reviewer_id, reviewer_email)
	{
		if (confirm("Confirma a exclusão do avaliador " + reviewer_email + "?"))
		{
			document.getElementById('submission_definition_reviewers_delete_id').value=reviewer_id;
			document.forms['submission_definition_reviewers_delete_form'].submit();
		}
		return false;
	}
	</script>

	<table class="data_table">
	<tr>
	<th style="width:30%"><?php echo $eve->_("submission_definition_reviewer.email"); ?></th>
	<th style="width:30%"><?php echo $eve->_("submission_definition_reviewer.name"); ?></th>
	<th style="width:30%"><?php echo $eve->_("submission_definition_reviewer.type"); ?></th>
	<th style="width:10%"><?php echo $eve->_("common.table.header.options"); ?></th>		
	</tr>
	<?php

	$submission_definition_reviewers = $eveSubmissionService->submission_definition_reviewers($_GET['id'], 'all');
	foreach ($submission_definition_reviewers as $submission_definition_reviewer)
	{	
		?>
		<tr>
		<td style="text-align:center"><?php echo $submission_definition_reviewer['email']; ?></td>
		<td style="text-align:center"><?php echo $submission_definition_reviewer['name']; ?></td>
		<td style="text-align:center"><?php echo $eve->_("submission_definition_reviewer.type.".$submission_definition_reviewer['type']); ?></td>
		<td style="text-align:center"><button type="button" onclick="delete_reviewer(<?php echo $submission_definition_reviewer['id']; ?>, '<?php echo $submission_definition_reviewer['email']; ?>');"><img src="style/icons/delete.png"/></button></td>
		</tr>
		<?php
	}
	?>
	</table>
	
	<?php
	$eve->output_html_footer();
}
?>
