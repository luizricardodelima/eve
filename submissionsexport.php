<?php
session_start();
require_once 'lib/dynamicform/dynamicform.class.php';
require_once 'lib/dynamicform/dynamicformhelper.class.php';
require_once 'lib/phpexcel/PHPExcel.php';
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
	// Create new PHPExcel object
	$xlsx = new PHPExcel();
	$row = 1;
	$col = -1; // Columns start from zero!

	// TODO ACCESS MODE FOR REVIEWERS. REUSE THE SAME FUNCTION IN SUBMISSIONS.PHP
	// TODO REMOVE PROTECTED FIELDS FOR REVIEWERS
	// TODO G11N
	$submission_structure = new DynamicForm($submission_definition['submission_structure']);
	$revision_structure = new DynamicForm($submission_definition['revision_structure']);
	$structures = array_merge($submission_structure->structure, $revision_structure->structure);

	// Spreadsheet header
	$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Id");
	$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Data");
	$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "E-mail do autor");
	$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "E-mail do avaliador");
	$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Status da avaliação");
	foreach ($structures as $submission_structure_item)
	{
		switch (get_class($submission_structure_item)) 
		{
			case DynamicFormItemGroupedText::class:
			case DynamicFormItemMultipleChoice::class:
				foreach ($submission_structure_item->spec->items as $array_item)
					$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure_item->description." - ".$array_item);
				break;
			default:
				$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure_item->description);
				break;
		}
	}
	$xlsx->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
	
	// Spreadsheet contents
	foreach ($_POST['submission_id'] as $submission_id)
	{	
		$submission = $eveSubmissionService->submission_get($submission_id);
		$row++;
		$col = -1; // Columns start from zero!
		$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['id']);
		$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['date']);
		$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['email']);
		$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['reviewer_email']);
		$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_("submission.revision_status.{$submission['revision_status']}"));
		
		$submission_structure = new DynamicForm($submission['structure'], json_decode($submission['content']));
		$revision_structure = new DynamicForm($submission['revision_structure'], json_decode($submission['revision_content']));
		$structures = array_merge($submission_structure->structure, $revision_structure->structure);

		foreach ($structures as $structure_item)
		{
			$value = $structure_item->getFormattedContent();
			if (is_array($value)) foreach($value as $value_item)
				$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $value_item);
			else
				$xlsx->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $value);
		}
	}

	// Resizing columns
	foreach ($xlsx->getWorksheetIterator() as $worksheet) 
	{
	    $xlsx->setActiveSheetIndex($xlsx->getIndex($worksheet));
	    $sheet = $xlsx->getActiveSheet();
	    $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
	    $cellIterator->setIterateOnlyExistingCells(true);
		foreach ($cellIterator as $cell) 
		{
			$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
	    }
	}

	// Rename worksheet (checking invalid characters)
	$invalidCharacters = $xlsx->getActiveSheet()->getInvalidCharacters();
	$title = str_replace($invalidCharacters, '', $submission_definition['description']);
	$xlsx->getActiveSheet()->setTitle($title);

	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$xlsx->setActiveSheetIndex(0);
		
	// Redirect output to a client’s web browser (Excel2007)
	ob_end_clean(); // This function is needed in order not to return corrupted files
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="export.xlsx"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');
	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0
	$objWriter = PHPExcel_IOFactory::createWriter($xlsx, 'Excel2007');
	$objWriter->save('php://output');
	exit;
}
?>
