<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';
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
	// Creating new PHPExcel object
	$objPHPExcel = new PHPExcel();
	$row = 1; $col = -1; // Columns start from zero!

	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.id'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.payment.group'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.screenname'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.date'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.payment.method'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.value.paid'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.value.received'));
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('payments.header.note'));
	if ($eve->getSetting('payments_export_name'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.name'));
	if ($eve->getSetting('payments_export_address'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.address'));
	if ($eve->getSetting('payments_export_city'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.city'));
	if ($eve->getSetting('payments_export_state'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.state'));
	if ($eve->getSetting('payments_export_country'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.country'));
	if ($eve->getSetting('payments_export_postalcode'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.postalcode'));
	if ($eve->getSetting('payments_export_birthday'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.birthday'));
	if ($eve->getSetting('payments_export_gender'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.gender'));
	if ($eve->getSetting('payments_export_phone1'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.phone1'));
	if ($eve->getSetting('payments_export_phone2'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.phone2'));
	if ($eve->getSetting('payments_export_institution'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->_('user.data.institution'));
	if ($eve->getSetting('payments_export_customtext1'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext1_label"));
	if ($eve->getSetting('payments_export_customtext2'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext2_label"));
	if ($eve->getSetting('payments_export_customtext3'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext3_label"));
	if ($eve->getSetting('payments_export_customtext4'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext4_label"));
	if ($eve->getSetting('payments_export_customtext5'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customtext5_label"));
	if ($eve->getSetting('payments_export_customflag1'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag1_label"));
	if ($eve->getSetting('payments_export_customflag2'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag2_label"));
	if ($eve->getSetting('payments_export_customflag3'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag3_label"));
	if ($eve->getSetting('payments_export_customflag4'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag4_label"));
	if ($eve->getSetting('payments_export_customflag5'))
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $eve->getSetting("user_customflag5_label"));
	
	$payment_groups = $evePaymentService->payment_group_list(true);
	$curr_formatter = new NumberFormatter($eve->getSetting('system_locale'), NumberFormatter::CURRENCY);
	$date_formatter = new IntlDateFormatter($eve->getSetting('system_locale'), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

	foreach ($evePaymentService->payment_list() as $line)
	{
		++$row;
		$col = -1; // Columns start from zero!
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['id']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['payment_group_id'] === null ? $eve->_('common.select.none') : $payment_groups[$line['payment_group_id']]['name']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['email']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $date_formatter->format(strtotime($line['date'])));
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['payment_method']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $curr_formatter->format($line['value_paid']));
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $curr_formatter->format($line['value_received']));
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['payment_note']);
		if ($eve->getSetting('payments_export_name'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['name']);
		if ($eve->getSetting('payments_export_address'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['address']);
		if ($eve->getSetting('payments_export_city'))	
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['city']);
		if ($eve->getSetting('payments_export_state'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['state']);
		if ($eve->getSetting('payments_export_country'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $countries[$line['country']]);
		if ($eve->getSetting('payments_export_postalcode'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['postalcode']);
		if ($eve->getSetting('payments_export_birthday'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['birthday']);
		if ($eve->getSetting('payments_export_gender'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['gender'] ? $eve->_('user.gender.'.$line['gender']) : '');
		if ($eve->getSetting('payments_export_phone1'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['phone1']);
		if ($eve->getSetting('payments_export_phone2'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['phone2']);
		if ($eve->getSetting('payments_export_institution'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['institution']);
		if ($eve->getSetting('payments_export_customtext1'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext1']);
		if ($eve->getSetting('payments_export_customtext2'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext2']);
		if ($eve->getSetting('payments_export_customtext3'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext3']);
		if ($eve->getSetting('payments_export_customtext4'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext4']);
		if ($eve->getSetting('payments_export_customtext5'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customtext5']);
		if ($eve->getSetting('payments_export_customflag1'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag1'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('payments_export_customflag2'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag2'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('payments_export_customflag3'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag3'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('payments_export_customflag4'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag4'] ? $eve->_('common.label.yes') : "");
		if ($eve->getSetting('payments_export_customflag5'))
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$col, $row, $line['customflag5'] ? $eve->_('common.label.yes') : "");
	}

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
