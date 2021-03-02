<?php
require_once 'eve.class.php';
require_once 'evepageservice.class.php';

$eve = new Eve();
$evePageService = new EvePageService($eve);

if (isset($_GET['p']))
{
	$page = $evePageService->page_get_content($_GET['p']);
	if ($page === null)
		$eve->output_error_page('common.message.page.not.found', false);
	else
	{
		// TODO: Permissions - only admins can view not visible pages
		$eve->output_html_header();
		echo "<div class=\"dialog_panel_wide\">";
		echo $page;
		echo "</div>";
		$eve->output_html_footer();
	}
}
else
{
	$page = $evePageService->page_get_content($evePageService->page_get_homepage());
	if ($page === null)
		$eve->output_redirect_page("userarea.php");
	else
	{
		$eve->output_html_header();
		echo "<div class=\"dialog_panel_wide\">";
		echo $page;
		echo "</div>";
		$eve->output_html_footer();
	}
}
?>