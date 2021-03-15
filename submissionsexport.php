<?php
session_start();

use SimpleExcel\SimpleExcel;

require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'lib/xlsxwriter/xlsxwriter.class.php';
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
DynamicFormHelper::$locale = $eve->getSetting('system_locale');
$submission_definition = null;

if (!isset($_SESSION['screenname']))
{
	// If there is no session, redirect to front page	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if 
(
	// If the post variables are invalid, show error message
	!isset($_POST['submission_definition_id']) ||
	($submission_definition = $eveSubmissionService->submission_definition_get($_POST['submission_definition_id'])) === null ||
	!isset($_POST['submission_id']) ||
	!is_array($_POST['submission_id'])
)
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else
{
	// TODO G11N
	// Spreadsheet header
	$header = 
	[
		'Id' => 'string',
		'Data' =>'YYYY-MM-DD',
		'E-mail do autor' => 'string',
		'E-mail do avaliador' => 'string',
		'Status da avaliação' => 'string'
	];
	// TODO ACCESS MODE FOR REVIEWERS. REUSE THE SAME FUNCTION IN SUBMISSIONS.PHP
	// TODO REMOVE PROTECTED FIELDS FOR REVIEWERS
	$submission_structure = new DynamicForm($submission_definition['submission_structure']);
	$revision_structure = new DynamicForm($submission_definition['revision_structure']);
	$structures = array_merge($submission_structure->structure, $revision_structure->structure);
	foreach ($structures as $submission_structure_item)
	{
		switch (get_class($submission_structure_item)) 
		{
			case DynamicFormItemGroupedText::class:
			case DynamicFormItemMultipleChoice::class:
				foreach ($submission_structure_item->spec->items as $array_item)
					$header["{$submission_structure_item->description} - {$array_item}"] = 'string';
				break;
			default:
				$header[$submission_structure_item->description] = 'string';
				break;
		}
	}
	
	// Spreadsheet contents
	$content = array();
	foreach ($_POST['submission_id'] as $submission_id)
	{	
		$submission = $eveSubmissionService->submission_get($submission_id);
		$line = array();
		$line[] = $submission['id'];
		$line[] = $submission['date'];
		$line[] = $submission['email'];
		$line[] = $submission['reviewer_email'];
		$line[] = $eve->_("submission.revision_status.{$submission['revision_status']}");

		$submission_form = new DynamicForm($submission['structure'], json_decode($submission['content']));
		$revision_form = new DynamicForm($submission['revision_structure'], json_decode($submission['revision_content']));
		$form_structures = array_merge($submission_form->structure, $revision_form->structure);

		foreach ($form_structures as $structure_item)
		{
			$value = $structure_item->getFormattedContent();
			if (is_array($value)) foreach($value as $value_item)
				$line[] = $value_item;
			else
				$line[] = $value;
		}
		$content[] = $line;
	}

	$writer = new XLSXWriter();
	$writer->writeSheet($content, $submission_definition['description'], $header);
	ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="export.xlsx"');
	header('Cache-Control: max-age=0');
	$writer->writeToStdOut();
}
?>
