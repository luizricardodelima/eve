<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.php';
require_once 'eveuserservice.class.php';
require_once 'lib/phpexcel/PHPExcel.php';
require_once 'lib/countries/countries.php';

$eve = new Eve();
$evePaymentService = new EvePaymentService($eve);

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
else
{
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();

	// Set document properties
	$paymentttypes = $evePaymentService->paymenttype_list(true);
	$paymenttypes[NULL]['name'] = $eve->_('paymenttype.name.null');
	// According to manual, null will be cast to the empty string, i.e. the key null will actually be stored under "".
	// Source: http://php.net/manual/en/language.types.array.php


	// TODO Remove SQL injection
	if (isset($_POST['screenname']))
	{
		$where_sql_clause = "where `{$eve->DBPref}userdata`.`email` in ('" . implode("','",$_POST['screenname']). "')";
	}
	else
	{
		$where_sql_clause = '';
	}
	
	$user_res = $eve->mysqli->query
	("
		select
			`{$eve->DBPref}userdata`.`email`, 
			`{$eve->DBPref}userdata`.`name`,
			`{$eve->DBPref}userdata`.`address`,
			`{$eve->DBPref}userdata`.`city`,
			`{$eve->DBPref}userdata`.`state`,
			`{$eve->DBPref}userdata`.`country`,
			`{$eve->DBPref}userdata`.`postalcode`,
			`{$eve->DBPref}userdata`.`birthday`,
			`{$eve->DBPref}userdata`.`gender`,
			`{$eve->DBPref}userdata`.`phone1`,
			`{$eve->DBPref}userdata`.`phone2`,
			`{$eve->DBPref}userdata`.`institution`,
			`{$eve->DBPref}usercategory`.`description`,
			`{$eve->DBPref}userdata`.`customtext1`,
			`{$eve->DBPref}userdata`.`customtext2`,
			`{$eve->DBPref}userdata`.`customtext3`,
			`{$eve->DBPref}userdata`.`customtext4`,
			`{$eve->DBPref}userdata`.`customtext5`,
			`{$eve->DBPref}userdata`.`customflag1`,
			`{$eve->DBPref}userdata`.`customflag2`,
			`{$eve->DBPref}userdata`.`customflag3`,
			`{$eve->DBPref}userdata`.`customflag4`,
			`{$eve->DBPref}userdata`.`customflag5`,
			`{$eve->DBPref}payment`.`id`,
			`{$eve->DBPref}payment`.`paymenttype_id`,
			`{$eve->DBPref}payment`.`value_paid`,
			`{$eve->DBPref}payment`.`value_received`,
			`{$eve->DBPref}payment`.`date`,
			`{$eve->DBPref}payment`.`note`
		from
			`{$eve->DBPref}userdata`
		left outer join
			`{$eve->DBPref}payment` on (`{$eve->DBPref}userdata`.`email` = `{$eve->DBPref}payment`.`email`)
		left outer join
			`{$eve->DBPref}usercategory` on (`{$eve->DBPref}userdata`.`category_id` = `{$eve->DBPref}usercategory`.`id`)
		$where_sql_clause
		order by
			`{$eve->DBPref}userdata`.`name`;
	");

	$row = 1;
	$col = -1; // Columns start from zero!

	// TODO G11N
	if ($eve->getSetting('paymentlisting_export_visible_email'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "E-mail");
	if ($eve->getSetting('paymentlisting_export_visible_name'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Nome");
	if ($eve->getSetting('paymentlisting_export_visible_address'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Endereço");
	if ($eve->getSetting('paymentlisting_export_visible_city'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Cidade");
	if ($eve->getSetting('paymentlisting_export_visible_state'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Estado");
	if ($eve->getSetting('paymentlisting_export_visible_country'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "País");
	if ($eve->getSetting('paymentlisting_export_visible_postalcode'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Cód. postal");
	if ($eve->getSetting('paymentlisting_export_visible_birthday'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Data nasc.");
	if ($eve->getSetting('paymentlisting_export_visible_gender'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Gênero");
	if ($eve->getSetting('paymentlisting_export_visible_phone1'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Telefone");
	if ($eve->getSetting('paymentlisting_export_visible_phone2'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Telefone 2");
	if ($eve->getSetting('paymentlisting_export_visible_institution'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Instituição");
	if ($eve->getSetting('paymentlisting_export_visible_categorydescription'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Categoria");
	if ($eve->getSetting('paymentlisting_export_visible_customtext1'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext1_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customtext2'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext2_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customtext3'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext3_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customtext4'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext4_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customtext5'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext5_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customflag1'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag1_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customflag2'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag2_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customflag3'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag3_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customflag4'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag4_label"));
	if ($eve->getSetting('paymentlisting_export_visible_customflag5'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag5_label"));
	if ($eve->getSetting('paymentlisting_export_visible_pmtid'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Id. Pgt.");
	if ($eve->getSetting('paymentlisting_export_visible_pmttype'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Tipo Pgt.");
	if ($eve->getSetting('paymentlisting_export_visible_pmtvaluepaid'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Valor Pago");
	if ($eve->getSetting('paymentlisting_export_visible_pmtvaluereceived'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Valor Recebido");
	if ($eve->getSetting('paymentlisting_export_visible_pmtdate'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Data Pgt.");
	if ($eve->getSetting('paymentlisting_export_visible_pmtnote'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, "Observação");

	while ($line = $user_res->fetch_assoc())
	{
		++$row;
		$col = -1; // Columns start from zero!
		if ($eve->getSetting('paymentlisting_export_visible_email'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['email']);
		if ($eve->getSetting('paymentlisting_export_visible_name'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['name']);
		if ($eve->getSetting('paymentlisting_export_visible_address'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['address']);
		if ($eve->getSetting('paymentlisting_export_visible_city'))	
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['city']);
		if ($eve->getSetting('paymentlisting_export_visible_state'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['state']);
		if ($eve->getSetting('paymentlisting_export_visible_country'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $countries[$line['country']]);
		if ($eve->getSetting('paymentlisting_export_visible_postalcode'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['postalcode']);
		if ($eve->getSetting('paymentlisting_export_visible_birthday'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['birthday']);
		if ($eve->getSetting('paymentlisting_export_visible_gender'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.gender.'.$line['gender']));
		if ($eve->getSetting('paymentlisting_export_visible_phone1'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['phone1']);
		if ($eve->getSetting('paymentlisting_export_visible_phone2'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['phone2']);
		if ($eve->getSetting('paymentlisting_export_visible_institution'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['institution']);
		if ($eve->getSetting('paymentlisting_export_visible_categorydescription'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['description']);
		if ($eve->getSetting('paymentlisting_export_visible_customtext1'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext1']);
		if ($eve->getSetting('paymentlisting_export_visible_customtext2'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext2']);
		if ($eve->getSetting('paymentlisting_export_visible_customtext3'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext3']);
		if ($eve->getSetting('paymentlisting_export_visible_customtext4'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext4']);
		if ($eve->getSetting('paymentlisting_export_visible_customtext5'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext5']);
		if ($eve->getSetting('paymentlisting_export_visible_customflag1'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag1'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('paymentlisting_export_visible_customflag2'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag2'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('paymentlisting_export_visible_customflag3'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag3'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('paymentlisting_export_visible_customflag4'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag4'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('paymentlisting_export_visible_customflag5'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag5'] ? $eve->_('common.label.yes') : "");	
		if ($eve->getSetting('paymentlisting_export_visible_pmtid'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['id']);
		if ($eve->getSetting('paymentlisting_export_visible_pmttype'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $paymentttypes[$line['paymenttype_id']]['name']);
		if ($eve->getSetting('paymentlisting_export_visible_pmtvaluepaid'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['value_paid']);
		if ($eve->getSetting('paymentlisting_export_visible_pmtvaluereceived'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['value_received']);
		if ($eve->getSetting('paymentlisting_export_visible_pmtdate'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['date']);
		if ($eve->getSetting('paymentlisting_export_visible_pmtnote'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['note']);
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
	$title = str_replace($invalidCharacters, '', $eve->_('userarea.option.admin.payments'));
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
