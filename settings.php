<?php
session_start();
require_once 'eve.class.php';

$eve = new Eve();

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
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.settings') => null
	]);

	?>
	<div class="section"><?php echo $eve->_('userarea.option.admin.settings');?></div>
	<div class="dialog_panel_thin">
	<div class="dialog_section"><?php echo $eve->_('settings.section.general');?></div>
		<button type="button" class="submit" onclick="window.location.href='settingsgeneralinfo.php'">
		<?php echo $eve->_('settings.general');?>
		</button>
		<button type="button" class="submit" onclick="window.location.href='settingsadmins.php'">
		<?php echo $eve->_('settings.system.admins');?>
		</button>
		<button type="button" class="submit" onclick="window.location.href='settingsphpmailer.php'">
		<?php echo $eve->_('settings.mail.configuration');?>
		</button>
		<button type="button" class="submit" onclick="window.location.href='settingsappearance.php'">
		<?php echo $eve->_('settings.appearance');?>
		</button>
	</div>

	<div class="dialog_panel_thin">
	<div class="dialog_section"><?php echo $eve->_('settings.section.users.and.payments');?></div>
	<button type="button" class="submit" onclick="window.location.href='settingsusersignup.php'">
	<?php echo $eve->_('settings.user.signup');?>
	</button>
	<button type="button" class="submit" onclick="window.location.href='settingsuserdata.php'">
	<?php echo $eve->_('settings.user.data');?>
	</button>
	<button type="button" class="submit" onclick="window.location.href='settingscredential.php'">
	<?php echo $eve->_('settings.user.credentials');?>
	</button>
	<button type="button" class="submit" onclick="window.location.href='settingspayments.php'">
	<?php echo $eve->_('settings.payments');?>
	</button>
	<button type="button" class="submit" onclick="window.location.href='settingspaymentslisting.php'">
	<?php echo $eve->_('settings.payments.listing');?>
	</button>
	</div>

	<div class="dialog_panel_thin">
	<div class="dialog_section"><div class="dialog_section"><?php echo $eve->_('settings.section.submissions.and.certifications');?></div></div>
	<button type="button" class="submit" onclick="window.location.href='settingssubmissions.php'">
	<?php echo $eve->_('settings.submissions');?>
	</button>
	<button type="button" class="submit" onclick="window.location.href='settingsreviewers.php'">
	<?php echo $eve->_('settings.reviewers.and.revisions');?>
	</button>
	<button type="button" class="submit" onclick="window.location.href='settingscertification.php'">
	<?php echo $eve->_('settings.certifications');?>
	</button>
	</div>

	<div class="dialog_panel_thin">
	<div class="dialog_section"><?php echo $eve->_('settings.section.plugins');?></div>
	<?php
	// Showing plugins configs
	$plugins = glob('plugins/*' , GLOB_ONLYDIR);
	if (!empty($plugins))
	{
		foreach ($plugins as $plugin)
		{
			$plugin_info = parse_ini_file("$plugin/plugin.ini");
			echo "<button type=\"button\" class=\"submit\" onclick=\"window.location.href='$plugin/{$plugin_info['settingsscreen']}'\">{$plugin_info['name']}</button>";
		}
	}
	?>
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>