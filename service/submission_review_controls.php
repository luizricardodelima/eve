<?php
session_start();

require_once '../lib/dynamicform/dynamicform.class.php';
require_once '../lib/dynamicform/dynamicformhelper.class.php';
require_once '../evesubmissionservice.class.php';
require_once '../eve.class.php';

// TODO SESSION VERIFICATION and ID verification

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
DynamicFormHelper::$locale = $eve->getSetting('system_locale');

$submission = $eveSubmissionService->submission_get($_GET['id']);
$submission_definition = $eveSubmissionService->submission_definition_get($submission['submission_definition_id']);
$dynamicForm = new DynamicForm($submission_definition['revision_structure'], json_decode($submission['revision_content']));

if ($submission['revision_status'] == 0) // TODO substituir 0 pelo código correspondente
{
	if ($_SESSION['screenname'] == $submission['reviewer_email'])
	{
		echo $dynamicForm->outputControls('revision_structure', 'revision_content');
		echo "<button type=\"submit\">Enviar</button>";
	}
	else if ($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id']) || $eve->is_admin($_SESSION['screenname'])) 
	{
		echo "<p>Aguardando avaliação.</p>"; // TODO G11n
		echo $dynamicForm->outputControls('revision_structure', 'revision_content', false);
	}
	else
	{
		echo "<p>Você não tem acesso à avaliação desta submissão.</p>"; //TODO G11n
	}
}
else if ($submission['revision_status'] == 1) // TODO substituir 1 pelo código correspondente
	if ($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id']))
	{
		echo $dynamicForm->outputControls('revision_structure', 'revision_content');
		?>
		<button type="submit" onclick="deactivate_button(this)">Enviar</button>
		<?php
	}
	else if ($_SESSION['screenname'] == $submission['reviewer_email'])
	{
		echo "<p>Avaliação realizada. Aguardando confirmação do avaliador final.</p>"; // TODO G11n
		echo $dynamicForm->outputControls('revision_structure', 'revision_content', false);
	}
	else if ($_SESSION['screenname'] == $submission['email'])
	{
		echo "<p>Aguardando avaliação.</p>"; // TODO G11n
	}
	else
	{
		echo "<p>Você não tem acesso à avaliação desta submissão.</p>"; // TODO G11n
	}
else if ($submission['revision_status'] == 2) // TODO substituir 2 pelo código correspondente
{
	if (
		($_SESSION['screenname'] == $submission['email']) || 
		($_SESSION['screenname'] == $submission['reviewer_email']) || 
		($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id'])) ||
		($eve->is_admin($_SESSION['screenname']))
		)
	{
		echo $dynamicForm->outputControls('revision_structure', 'revision_content', false);
	}
	else
	{
		echo "<p>Você não tem acesso à avaliação desta submissão.</p>"; // TODO G11n
	}
}
else
{
	echo "<p>Erro no banco de dados.</p>"; // TODO G11n
}

?>
