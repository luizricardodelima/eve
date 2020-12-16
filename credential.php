<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';
require_once 'lib/fpdf/fpdf.php';

$eve = new Eve();
$eveUserService = new EveUserService($eve);

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
	// Only Reporting Errors. If less critical messages (such as warnings) are displayed, the pdf won't be generated.
	error_reporting(E_ERROR);

	// Loading settings
	// TODO verify whether $page_size and $page_orientation contain valid values
	$page_size = $eve->getSetting('credential_page_size');
	$page_orientation = $eve->getSetting('credential_page_orientation');
	$page_top_margin  = $eve->getSetting('credential_page_top_margin');
	$page_left_margin = $eve->getSetting('credential_page_left_margin');
	$cell_width = $eve->getSetting('credential_cell_width');
	$cell_height = $eve->getSetting('credential_cell_height');
	$cells_per_line = $eve->getSetting('credential_cells_per_line');
	$lines_per_page = $eve->getSetting('credential_lines_per_page');
	$border = $eve->getSetting('credential_border');
	$border_color = $eve->getSetting('credential_border_color');
	list($border_r, $border_g, $border_b) = sscanf($border_color, "#%02x%02x%02x");
	$bgimage = $eve->getSetting('credential_bgimage');
	$textbox = $eve->getSetting('credential_textbox');
	$textbox_x = $eve->getSetting('credential_textbox_x');
	$textbox_y = $eve->getSetting('credential_textbox_y');
	$textbox_w = $eve->getSetting('credential_textbox_w');
	$textbox_h = $eve->getSetting('credential_textbox_h');
	$textbox_fontsize = $eve->getSetting('credential_textbox_fontsize');
	$textbox_content = $eve->getSetting('credential_textbox_content');
	$countryflag = $eve->getSetting('credential_countryflag');
	$countryflag_x = $eve->getSetting('credential_countryflag_x');
	$countryflag_y = $eve->getSetting('credential_countryflag_y');
	$countryflag_w = $eve->getSetting('credential_countryflag_w');
	$countryflag_h = $eve->getSetting('credential_countryflag_h');	

	// Creating PDF file
	$pdf = new FPDF($page_orientation, 'mm', $page_size);
	$pdf->SetTopMargin(0);

	// $row holds the position of the current row in the page. By using this value at start, it will
	// force the creation of a new page
	$row = $lines_per_page;
	$col = 0;
	
	foreach ($_POST['screenname'] as $sname)
	{
		if ($row >= $lines_per_page)
		{
			$pdf->AddPage();
			$row = 0;
		}

		// Drawing background image (checking if background image file exists, first)
		if ($bgimage && fopen("upload/style/credential.png", "r"))
		{
			$pdf->Image
			(
				"upload/style/credential.png",
				($page_left_margin + ($col * $cell_width)),
				($page_top_margin + ($row * $cell_height)),
				$cell_width,
				$cell_height
			);
		}

		$user = $eveUserService->user_get($sname);

		// Drawing country flag		
		if ($countryflag && $user['country'])
		{
			$pdf->Image
			(
				"lib/countries/png/".strtoupper ($user['country']).".png",
				($page_left_margin + ($col * $cell_width)) + $countryflag_x,
				($page_top_margin + ($row * $cell_height)) + $countryflag_y,
				$countryflag_w,
				$countryflag_h
			);
		}

		// Drawing text box
		if ($textbox)
		{
			$content = str_replace('$user[name]', $user['name'], $textbox_content);

			$pdf->SetFont('Arial','',$textbox_fontsize);
			$pdf->SetXY
			(
				($page_left_margin + ($col * $cell_width) + $textbox_x),
				($page_top_margin + ($row * $cell_height) + $textbox_y)
			);
			// TODO: Its NOT UTF8!!!
			$pdf->MultiCell
			(
				$textbox_w,
				$textbox_h,
				utf8_decode(mb_strtoupper($content, 'UTF-8')),
				0,
				'C'
			);
		}

		// Drawing border
		if ($border)
		{
			$pdf->SetDrawColor($border_r, $border_g, $border_b);
			$pdf->Rect
			(
				($page_left_margin + ($col * $cell_width)),
				($page_top_margin + ($row * $cell_height)),
				$cell_width,
				$cell_height
			);
		}
	
		// Updating col and row positions
		$col++;
		if ($col >= $cells_per_line)
		{
			$col = 0;
			$row++;
		}
	}
		
	// Displaying PDF
	$pdf->Output();
	
}
?>
