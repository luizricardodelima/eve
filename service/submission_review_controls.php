<?php
session_start();

/** NOTICE: The page that calls this php file needs to load custominputs.js*/
/** one get parameter id*/
require_once '../evesubmissionservice.class.php';
require_once '../eve.class.php';
require_once '../evecustominputservice.class.php';

// TODO SESSION VERIFICATION

$eve = new Eve("../"); // TODO !!!!
$eveSubmissionService = new EveSubmissionService($eve);
$eveCustomInputService = new EveCustomInputService($eve);

$submission = $eveSubmissionService->submission_get($_GET['id']);
$submission_definition = $eveSubmissionService->submission_definition_get($submission['submission_definition_id']);
$revision_structure = json_decode($submission_definition['revision_structure']);
$revision_content = json_decode($submission['revision_content']);


if ($submission['revision_status'] == 0) // TODO substituir 0 pelo código correspondente
{
	if ($_SESSION['screenname'] == $submission['reviewer_email'])
	{
		$eveCustomInputService->custom_input_output_html_controls($revision_structure, 'revision_content');
		echo "<button type=\"submit\" class=\"submit\">Enviar</button>";
	}
	else if ($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id'])) 
	{
		echo "<p>Aguardando avaliação.</p>";
	}
	else
	{
		echo "<p>Você não tem acesso à avaliação desta submissão.</p>";
	}
}
else if ($submission['revision_status'] == 1) // TODO substituir 1 pelo código correspondente
	if ($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id']))
	{
		$eveCustomInputService->custom_input_output_html_controls($revision_structure, 'revision_content', $revision_content);
		echo "<button type=\"submit\" class=\"submit\">Enviar</button>";
	}
	else if ($_SESSION['screenname'] == $submission['reviewer_email'])
	{
		$eveCustomInputService->custom_input_output_html_controls($revision_structure, 'revision_content', $revision_content, true);
	}
	else if ($_SESSION['screenname'] == $submission['email'])
	{
		echo "<p>Aguardando avaliação.</p>";
	}
	else
	{
		echo "<p>Você não tem acesso à avaliação desta submissão.</p>";
	}
else if ($submission['revision_status'] == 2) // TODO substituir 2 pelo código correspondente
{
	if (($_SESSION['screenname'] == $submission['email']) || ($_SESSION['screenname'] == $submission['reviewer_email']) || ($eveSubmissionService->is_final_reviewer($_SESSION['screenname'], $submission_definition['id'])))
	{
		$eveCustomInputService->custom_input_output_html_controls($revision_structure, 'revision_content', $revision_content, true);
	}
	else
	{
		echo "<p>Você não tem acesso à avaliação desta submissão.</p>";
	}
}
else
{
	echo "<p>Erro no banco de dados.</p>";
}

?>
