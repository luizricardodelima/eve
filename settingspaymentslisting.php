<?php
session_start();
require_once 'eve.class.php';
require_once 'evesettingsservice.class.php';

$eve = new Eve();
$eveSettingsService = new EveSettingsService($eve);

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
else if (!empty($_POST))
{
	// There are settings as POST variables to be saved.
	$msg = $eveSettingsService->settings_update($_POST);
	$eve->output_redirect_page(basename(__FILE__)."?msg=$msg");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.settings') => "settings.php",
		$eve->_('settings.payments.listing') => null,
	]);		
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	$settings = $eveSettingsService->settings_get
	(
		'payments_view_name', 'payments_export_name',		
		'payments_view_address', 'payments_export_address', 
		'payments_view_city', 'payments_export_city', 
		'payments_view_state', 'payments_export_state', 
		'payments_view_country', 'payments_export_country', 
		'payments_view_postalcode', 'payments_export_postalcode', 
		'payments_view_birthday', 'payments_export_birthday', 
		'payments_view_gender', 'payments_export_gender', 
		'payments_view_phone1', 'payments_export_phone1', 
		'payments_view_phone2', 'payments_export_phone2', 
		'payments_view_institution', 'payments_export_institution', 
		'payments_view_customtext1', 'payments_export_customtext1', 
		'payments_view_customtext2', 'payments_export_customtext2', 
		'payments_view_customtext3', 'payments_export_customtext3', 
		'payments_view_customtext4', 'payments_export_customtext4', 
		'payments_view_customtext5', 'payments_export_customtext5', 
		'payments_view_customflag1', 'payments_export_customflag1', 
		'payments_view_customflag2', 'payments_export_customflag2', 
		'payments_view_customflag3', 'payments_export_customflag3', 
		'payments_view_customflag4', 'payments_export_customflag4', 
		'payments_view_customflag5', 'payments_export_customflag5'
	);

	?>
	<div class="section"><?php echo $eve->_('settings.payments.listing');?>
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	<table class="data_table">
	<thead>
	<!--<th style="width: 5%"><?php echo $eve->_('settings.payments.listing.header.view');?></th>-->
	<th style="width: 5%"><?php echo $eve->_('settings.payments.listing.header.export');?></th>
	<th style="width: 90%"><?php echo $eve->_('settings.payments.listing.header.field');?></th>
	</thead>
	<tr>
	<!--<td><input type="hidden" name="payments_view_name" value="0"/><input type="checkbox" name="payments_view_name" value="1" <?php if ($settings['payments_view_name']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_name" value="0"/><input type="checkbox" name="payments_export_name" value="1" <?php if ($settings['payments_export_name']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.name');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_address" value="0"/><input type="checkbox" name="payments_view_address" value="1" <?php if ($settings['payments_view_address']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_address" value="0"/><input type="checkbox" name="payments_export_address" value="1" <?php if ($settings['payments_export_address']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.address');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_city" value="0"/><input type="checkbox" name="payments_view_city" value="1" <?php if ($settings['payments_view_city']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_city" value="0"/><input type="checkbox" name="payments_export_city" value="1" <?php if ($settings['payments_export_city']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.city');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_state" value="0"/><input type="checkbox" name="payments_view_state" value="1" <?php if ($settings['payments_view_state']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_state" value="0"/><input type="checkbox" name="payments_export_state" value="1" <?php if ($settings['payments_export_state']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.state');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_country" value="0"/><input type="checkbox" name="payments_view_country" value="1" <?php if ($settings['payments_view_country']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_country" value="0"/><input type="checkbox" name="payments_export_country" value="1" <?php if ($settings['payments_export_country']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.country');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_postalcode" value="0"/><input type="checkbox" name="payments_view_postalcode" value="1" <?php if ($settings['payments_view_postalcode']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_postalcode" value="0"/><input type="checkbox" name="payments_export_postalcode" value="1" <?php if ($settings['payments_export_postalcode']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.postalcode');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_birthday" value="0"/><input type="checkbox" name="payments_view_birthday" value="1" <?php if ($settings['payments_view_birthday']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_birthday" value="0"/><input type="checkbox" name="payments_export_birthday" value="1" <?php if ($settings['payments_export_birthday']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.birthday');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_gender" value="0"/><input type="checkbox" name="payments_view_gender" value="1" <?php if ($settings['payments_view_gender']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_gender" value="0"/><input type="checkbox" name="payments_export_gender" value="1" <?php if ($settings['payments_export_gender']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.gender');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_phone1" value="0"/><input type="checkbox" name="payments_view_phone1" value="1" <?php if ($settings['payments_view_phone1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_phone1" value="0"/><input type="checkbox" name="payments_export_phone1" value="1" <?php if ($settings['payments_export_phone1']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.phone1');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_phone2" value="0"/><input type="checkbox" name="payments_view_phone2" value="1" <?php if ($settings['payments_view_phone2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_phone2" value="0"/><input type="checkbox" name="payments_export_phone2" value="1" <?php if ($settings['payments_export_phone2']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.phone2');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_institution" value="0"/><input type="checkbox" name="payments_view_institution" value="1" <?php if ($settings['payments_view_institution']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_institution" value="0"/><input type="checkbox" name="payments_export_institution" value="1" <?php if ($settings['payments_export_institution']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->_('user.data.institution');?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customtext1" value="0"/><input type="checkbox" name="payments_view_customtext1" value="1" <?php if ($settings['payments_view_customtext1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customtext1" value="0"/><input type="checkbox" name="payments_export_customtext1" value="1" <?php if ($settings['payments_export_customtext1']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext1_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customtext2" value="0"/><input type="checkbox" name="payments_view_customtext2" value="1" <?php if ($settings['payments_view_customtext2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customtext2" value="0"/><input type="checkbox" name="payments_export_customtext2" value="1" <?php if ($settings['payments_export_customtext2']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext2_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customtext3" value="0"/><input type="checkbox" name="payments_view_customtext3" value="1" <?php if ($settings['payments_view_customtext3']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customtext3" value="0"/><input type="checkbox" name="payments_export_customtext3" value="1" <?php if ($settings['payments_export_customtext3']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext3_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customtext4" value="0"/><input type="checkbox" name="payments_view_customtext4" value="1" <?php if ($settings['payments_view_customtext4']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customtext4" value="0"/><input type="checkbox" name="payments_export_customtext4" value="1" <?php if ($settings['payments_export_customtext4']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext4_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customtext5" value="0"/><input type="checkbox" name="payments_view_customtext5" value="1" <?php if ($settings['payments_view_customtext5']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customtext5" value="0"/><input type="checkbox" name="payments_export_customtext5" value="1" <?php if ($settings['payments_export_customtext5']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext5_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customflag1" value="0"/><input type="checkbox" name="payments_view_customflag1" value="1" <?php if ($settings['payments_view_customflag1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customflag1" value="0"/><input type="checkbox" name="payments_export_customflag1" value="1" <?php if ($settings['payments_export_customflag1']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag1_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customflag2" value="0"/><input type="checkbox" name="payments_view_customflag2" value="1" <?php if ($settings['payments_view_customflag2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customflag2" value="0"/><input type="checkbox" name="payments_export_customflag2" value="1" <?php if ($settings['payments_export_customflag2']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag2_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customflag3" value="0"/><input type="checkbox" name="payments_view_customflag3" value="1" <?php if ($settings['payments_view_customflag3']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customflag3" value="0"/><input type="checkbox" name="payments_export_customflag3" value="1" <?php if ($settings['payments_export_customflag3']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag3_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customflag4" value="0"/><input type="checkbox" name="payments_view_customflag4" value="1" <?php if ($settings['payments_view_customflag4']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customflag4" value="0"/><input type="checkbox" name="payments_export_customflag4" value="1" <?php if ($settings['payments_export_customflag4']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag4_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_customflag5" value="0"/><input type="checkbox" name="payments_view_customflag5" value="1" <?php if ($settings['payments_view_customflag5']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_customflag5" value="0"/><input type="checkbox" name="payments_export_customflag5" value="1" <?php if ($settings['payments_export_customflag5']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag5_label");?></td></tr>
	</table>
	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
