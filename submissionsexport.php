<?php
session_start();
require_once 'eve.class.php';
require_once 'lib/phpexcel/PHPExcel.php';
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
// Administrative privileges verification. // TODO Change this. Reviewers must have access to this page. even regular users too
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
// Checking if $_GET['id'] is valid by trying to retireve the submission_definition from database.
else if (!isset($_GET['id']) || !$eveSubmissionService->submission_definition_get($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else
{
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();

	$row = 1;
	$col = -1; // Columns start from zero!

	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);
	$submission_structure = json_decode($submission_definition['submission_structure']);
	$revision_structure = json_decode($submission_definition['revision_structure']);
	
	

	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Id");
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Data");
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "E-mail do autor");
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Nome do autor");
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "E-mail do avaliador");
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Status da avaliação");
	foreach ($submission_structure as $submission_structure_item)
	{
		if ($submission_structure_item->type == "array")
		{
			foreach ($submission_structure_item->spec->items as $array_item)
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure_item->description." - ".$array_item);
		}
		else
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure_item->description);
	}
	foreach ($revision_structure as $submission_structure_item)
	{
		if ($submission_structure_item->type == "array")
		{
			foreach ($submission_structure_item->spec->items as $array_item)
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure_item->description." - ".$array_item);
		}
		else
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure_item->description);
	}

	$submissions = $eveSubmissionService->submission_list($_GET['id']);

	foreach ($submissions as $submission)
	{	
		++$row;
		$col = -1; // Columns start from zero!
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['id']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['date']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['email']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['name']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['reviewer_email']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission['revision_status']);

		// TODO. RELY ON eve custom input service to deliver the values.
		$submission_content = json_decode($submission['content']);
		for ($i = 0; $i < count($submission_structure); $i++) {
			switch ($submission_structure[$i]->type)
			{
				case 'text':
				case 'bigtext':
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_content[$i]);
					break;
				case 'array':
					foreach ($submission_content[$i] as $submission_content_item_item)
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_content_item_item);
					break;
				case 'enum':
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_structure[$i]->spec->items[$submission_content[$i]]);
					break;
				case 'file': // TODO LINK
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_content[$i]);
					break;
				case 'check':
					if ($submission_content[$i])
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('common.label.yes'));
					else
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('common.label.no'));
					break;
				
			}
		}

		$revision_content = json_decode($submission['revision_content']);
		for ($i = 0; $i < count($revision_structure); $i++) {
			switch ($revision_structure[$i]->type)
			{
				case 'text':
				case 'bigtext':
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $revision_content[$i]);
					break;
				case 'array':
					foreach ($revision_content[$i] as $revision_content_item_item)
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $revision_content_item_item);
					break;
				case 'enum':
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $revision_structure[$i]->spec->items[$revision_content[$i]]);
					break;
				case 'file': // TODO LINK
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $revision_content[$i]);
					break;
				case 'check': // TODO LINK
					if ($revision_content[$i])
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('common.label.yes'));
					else
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('common.label.no'));
					break;
				
			}
		}
		/*
		foreach ($submission_content as $submission_content_item)
		{
	
			// TODO filetypes
			if (is_array($submission_content_item))
			{
				foreach ($submission_content_item as $submission_content_item_item)
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_content_item_item);
			}
			else
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $submission_content_item);
		}
		*/
	}
	// Autofilter!
	$objPHPExcel->getActiveSheet()->setAutoFilter('A1:' . PHPExcel_Cell::stringFromColumnIndex($col) . $row);

	// Resizing columns
	foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {

	    $objPHPExcel->setActiveSheetIndex($objPHPExcel->getIndex($worksheet));

	    $sheet = $objPHPExcel->getActiveSheet();
	    $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
	    $cellIterator->setIterateOnlyExistingCells(true);
	    /** @var PHPExcel_Cell $cell */
	    foreach ($cellIterator as $cell) {
		$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
	    }
	}

	// Rename worksheet (checking invalid characters)
	$invalidCharacters = $objPHPExcel->getActiveSheet()->getInvalidCharacters();
	$title = str_replace($invalidCharacters, '', $submission_definition['description']);
	$objPHPExcel->getActiveSheet()->setTitle($title);

	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$objPHPExcel->setActiveSheetIndex(0);
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
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('php://output');
	exit;
}
?>
