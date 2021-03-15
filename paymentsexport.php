<?php
session_start();
require_once 'eve.class.php';
require_once 'evepaymentservice.class.php';
require_once 'eveuserservice.class.php';
require_once 'lib/xlsxwriter/xlsxwriter.class.php';
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
	$header = 
	[
		$eve->_('payments.header.id') => 'string',
		$eve->_('payments.header.payment.group') => 'string',
		$eve->_('payments.header.screenname') => 'string',
		$eve->_('payments.header.date') => 'YYYY-MM-DD',
		$eve->_('payments.header.payment.method') => 'string',
		$eve->_('payments.header.value.paid') => '0.00',
		$eve->_('payments.header.value.received') => '0.00',
		$eve->_('payments.header.note') => 'string'
	];
	if ($eve->getSetting('payments_export_name'))
		$header[$eve->_('user.data.name')] = 'string';
	if ($eve->getSetting('payments_export_address'))
		$header[$eve->_('user.data.address')] = 'string';
	if ($eve->getSetting('payments_export_city'))
		$header[$eve->_('user.data.city')] = 'string';
	if ($eve->getSetting('payments_export_state'))
		$header[$eve->_('user.data.state')] = 'string';
	if ($eve->getSetting('payments_export_country'))
		$header[$eve->_('user.data.country')] = 'string';
	if ($eve->getSetting('payments_export_postalcode'))
		$header[$eve->_('user.data.postalcode')] = 'string';
	if ($eve->getSetting('payments_export_birthday'))
		$header[$eve->_('user.data.birthday')] = 'string';
	if ($eve->getSetting('payments_export_gender'))
		$header[$eve->_('user.data.gender')] = 'string';
	if ($eve->getSetting('payments_export_phone1'))
		$header[$eve->_('user.data.phone1')] = 'string';
	if ($eve->getSetting('payments_export_phone2'))
		$header[$eve->_('user.data.phone2')] = 'string';
	if ($eve->getSetting('payments_export_institution'))
		$header[$eve->_('user.data.institution')] = 'string';
	if ($eve->getSetting('payments_export_customtext1'))
		$header[$eve->getSetting("user_customtext1_label")] = 'string';
	if ($eve->getSetting('payments_export_customtext2'))
		$header[$eve->getSetting("user_customtext2_label")] = 'string';
	if ($eve->getSetting('payments_export_customtext3'))
		$header[$eve->getSetting("user_customtext3_label")] = 'string';
	if ($eve->getSetting('payments_export_customtext4'))
		$header[$eve->getSetting("user_customtext4_label")] = 'string';
	if ($eve->getSetting('payments_export_customtext5'))
		$header[$eve->getSetting("user_customtext5_label")] = 'string';
	if ($eve->getSetting('payments_export_customflag1'))
		$header[$eve->getSetting("user_customflag1_label")] = 'string';
	if ($eve->getSetting('payments_export_customflag2'))
		$header[$eve->getSetting("user_customflag2_label")] = 'string';
	if ($eve->getSetting('payments_export_customflag3'))
		$header[$eve->getSetting("user_customflag3_label")] = 'string';
	if ($eve->getSetting('payments_export_customflag4'))
		$header[$eve->getSetting("user_customflag4_label")] = 'string';
	if ($eve->getSetting('payments_export_customflag5'))
		$header[$eve->getSetting("user_customflag5_label")] = 'string';

	$payment_groups = $evePaymentService->payment_group_list(true);

	$content = array();
	foreach ($evePaymentService->payment_list() as $line)
	{
		$line_ = array();
		$line_[] = $line['id'];
		$line_[] = $line['payment_group_id'] === null ? $eve->_('common.select.none') : $payment_groups[$line['payment_group_id']]['name'];
		$line_[] = $line['email'];
		$line_[] = $line['date'];
		$line_[] = $line['payment_method'];
		$line_[] = $line['value_paid'];
		$line_[] = $line['value_received'];
		$line_[] = $line['payment_note'];
		if ($eve->getSetting('payments_export_name'))
			$line_[] = $line['name'];
		if ($eve->getSetting('payments_export_address'))
			$line_[] = $line['address'];
		if ($eve->getSetting('payments_export_city'))	
			$line_[] = $line['city'];
		if ($eve->getSetting('payments_export_state'))
			$line_[] = $line['state'];
		if ($eve->getSetting('payments_export_country'))
			$line_[] = $countries[$line['country']];
		if ($eve->getSetting('payments_export_postalcode'))
			$line_[] = $line['postalcode'];
		if ($eve->getSetting('payments_export_birthday'))
			$line_[] = $line['birthday'];
		if ($eve->getSetting('payments_export_gender'))
			$line_[] = $line['gender'] ? $eve->_('user.gender.'.$line['gender']) : '';
		if ($eve->getSetting('payments_export_phone1'))
			$line_[] = $line['phone1'];
		if ($eve->getSetting('payments_export_phone2'))
			$line_[] = $line['phone2'];
		if ($eve->getSetting('payments_export_institution'))
			$line_[] = $line['institution'];
		if ($eve->getSetting('payments_export_customtext1'))
			$line_[] = $line['customtext1'];
		if ($eve->getSetting('payments_export_customtext2'))
			$line_[] = $line['customtext2'];
		if ($eve->getSetting('payments_export_customtext3'))
			$line_[] = $line['customtext3'];
		if ($eve->getSetting('payments_export_customtext4'))
			$line_[] = $line['customtext4'];
		if ($eve->getSetting('payments_export_customtext5'))
			$line_[] = $line['customtext5'];
		if ($eve->getSetting('payments_export_customflag1'))
			$line_[] = $line['customflag1'] ? $eve->_('common.label.yes') : "";
		if ($eve->getSetting('payments_export_customflag2'))
			$line_[] = $line['customflag2'] ? $eve->_('common.label.yes') : "";
		if ($eve->getSetting('payments_export_customflag3'))
			$line_[] = $line['customflag3'] ? $eve->_('common.label.yes') : "";
		if ($eve->getSetting('payments_export_customflag4'))
			$line_[] = $line['customflag4'] ? $eve->_('common.label.yes') : "";
		if ($eve->getSetting('payments_export_customflag5'))
			$line_[] = $line['customflag5'] ? $eve->_('common.label.yes') : "";
		
		$content[] = $line_;
	}

	$writer = new XLSXWriter();
	$writer->writeSheet($content, $eve->_('userarea.option.admin.payments'), $header);
	ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="export.xlsx"');
	header('Cache-Control: max-age=0');
	$writer->writeToStdOut();
}
?>
