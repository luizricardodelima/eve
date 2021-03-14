<?php
session_start();
require_once 'lib/countries/countries.php';
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eveUserService = new EveUserService($eve);

if (!isset($_SESSION['screenname']))
{
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
else if (isset($_GET['user']) && !$eve->user_exists($_GET['user']))
{	
	$eve->output_error_page('common.message.invalid.parameter');	
}
else
{	
	// $admin_mode flag. System administrators can use this page to view and edit other users' data.
	// This is recognized when a 'screenname' GET parameter is passed and the current user is admin.
	// If a non-admin tries to pass a 'screenname' GET parameter, this code will simply ignore it
	$admin_mode = (isset($_GET['user']) && $eve->is_admin($_SESSION['screenname'])) ? 1 : 0;			
	$email = ($admin_mode == 1) ? $_GET['user'] : $_SESSION['screenname'];
	$user = $eveUserService->user_get($email);
	$validation_errors = array();

	if (!empty($_POST))
	{
		// There are POST information with user data. If they are valid, they will be
		// saved, if not an error will be displayed.

		// Overwriting $user with POST data so users can see the data they have
		// inputted, even if the data is not saved because of a validation error.
		foreach ($_POST as $column => $value) {$user[$column] = $value;}
		
		// Validating user data only if not in admin mode
		if (!$admin_mode) $validation_errors = $eveUserService->user_validate($user);

		if (empty($validation_errors))
		{
			// Updating user data, if there are no validating errors
			$msg = $eveUserService->user_save($user);
			if ($admin_mode)
				$eve->output_redirect_page("users.php?msg=$msg");
			else
				$eve->output_redirect_page("userarea.php?msg=$msg");
		}
	}

	$eve->output_html_header();
		
	if ($admin_mode)
	{
		$eve->output_navigation
		([
			$eve->getSetting('userarea_label') => "userarea.php",
			$eve->_('userarea.option.admin.users') => "users.php",
			$eve->_('userarea.option.userdata') => null
		]);		
		?>
		<div class="section"><?php echo $eve->_('userarea.option.userdata');?>
		<button type="button" onclick="document.forms['user_form'].submit()"><?php echo $eve->_('common.action.save');?></button>
		<button type="button" onclick="document.forms['credential_form'].submit()">Gerar credencial</button><!-- TODO G11N -->
		</div>
		<form action="credential.php" method="post" id="credential_form">
		<input type="hidden" name="screenname[]" value="<?php echo($user['email']);?>"/>
		</form>
		<?php	
	}
	else
	{
		$eve->output_navigation
		([
			$eve->getSetting('userarea_label') => "userarea.php",
			$eve->_('userarea.option.userdata') => null
		]);	
		?>
		<div class="section"><?php echo $eve->_('userarea.option.userdata');?></div>
		<?php	
	}	
	
	if (!empty($validation_errors))
		$eve->output_error_list_message($validation_errors);

	$mandatory = "&nbsp;<small><em>{$eve->_('common.mandatory.field')}</em></small>";
	?>
	<form method="post" id="user_form" class="dialog_panel" <?php if ($admin_mode) echo "action=\"".basename(__FILE__)."?user=$email\"";?>>

	<?php
	if (!$admin_mode && $eve->getSetting('user_display_custom_message_on_unlocked_form'))
		echo $eve->getSetting('user_custom_message_on_unlocked_form');
	?>
	<label for="user_data_email"><?php echo $eve->_('user.data.email');?></label>
	<input id="user_data_email" type="text" name="email" readonly="readonly" value="<?php echo $user['email'];?>"/>

	<?php if ($eve->getSetting('user_name_visible')) { ?>
	<label for="user_data_name"><?php echo $eve->_('user.data.name'); if ($eve->getSetting('user_name_mandatory')) echo $mandatory;?></label>
	<input id="user_data_name" type="text" name="name" value="<?php echo $user['name'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_address_visible')) { ?>
	<label for="user_data_address"><?php echo $eve->_('user.data.address'); if ($eve->getSetting('user_address_mandatory')) echo $mandatory;?></label>
	<input id="user_data_address" type="text" name="address" value="<?php echo $user['address'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_city_visible')) { ?>
	<label for="user_data_city"><?php echo $eve->_('user.data.city'); if ($eve->getSetting('user_city_mandatory')) echo $mandatory;?></label>
	<input id="user_data_city" type="text" name="city" value="<?php echo $user['city'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_state_visible')) { ?>
	<label for="user_data_state"><?php echo $eve->_('user.data.state'); if ($eve->getSetting('user_state_mandatory')) echo $mandatory;?></label>
	<input id="user_data_state" type="text" name="state" value="<?php echo $user['state'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_country_visible')) { ?>
	<label for="user_data_country"><?php echo $eve->_('user.data.country'); if ($eve->getSetting('user_country_mandatory')) echo $mandatory;?></label>
	<select id="user_data_country" name="country"/>
	<option value=""><?php echo $eve->_('common.select.null');?></option>
	<?php
	foreach ($countries as $country_code => $country)
	{
		echo "<option value=\"$country_code\"";
		if ($user['country'] == $country_code) echo " selected=\"selected\"";
		echo ">$country</option>";
	}
	?>
	</select>
	<?php } ?>

	<?php if ($eve->getSetting('user_postalcode_visible')) { ?>
	<label for="user_data_postalcode"><?php echo $eve->_('user.data.postalcode'); if ($eve->getSetting('user_postalcode_mandatory')) echo $mandatory;?></label>
	<input id="user_data_postalcode" type="text" name="postalcode" value="<?php echo $user['postalcode'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_birthday_visible')) { ?>
	<label for="user_data_birthday"><?php echo $eve->_('user.data.birthday'); if ($eve->getSetting('user_birthday_mandatory')) echo $mandatory;?></label>
	<input id="user_data_birthday" type="date" name="birthday" value="<?php echo $user['birthday'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_gender_visible')) { ?>
	<label for="user_data_gender"><?php echo $eve->_('user.data.gender'); if ($eve->getSetting('user_gender_mandatory')) echo $mandatory;?></label>
	<select id="user_data_gender" name="gender"/>
	<option value=""><?php echo $eve->_('common.select.null');?></option>
	<?php
	foreach ($eveUserService->user_genders() as $gender)
	{
		echo "<option value=\"$gender\"";
		if ($user['gender'] == $gender) echo " selected=\"selected\"";
		echo ">".$eve->_('user.gender.'.$gender)."</option>";
	}
	?>
	</select>
	<?php } ?>

	<?php if ($eve->getSetting('user_phone1_visible')) { ?>
	<label for="user_data_phone1"><?php echo $eve->_('user.data.phone1'); if ($eve->getSetting('user_phone1_mandatory')) echo $mandatory;?></label>
	<input id="user_data_phone1" type="text" name="phone1" value="<?php echo $user['phone1'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_phone2_visible')) { ?>
	<label for="user_data_phone2"><?php echo $eve->_('user.data.phone2'); if ($eve->getSetting('user_phone2_mandatory')) echo $mandatory;?></label>
	<input id="user_data_phone2" type="text" name="phone2" value="<?php echo $user['phone2'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_institution_visible')) { ?>
	<label for="user_data_institution"><?php echo $eve->_('user.data.institution'); if ($eve->getSetting('user_institution_mandatory')) echo $mandatory;?></label>
	<input id="user_data_institution" type="text" name="institution" value="<?php echo $user['institution'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_customtext1_visible')) { ?>
	<label for="user_data_customtext1"><?php echo $eve->getSetting('user_customtext1_label'); if ($eve->getSetting('user_customtext1_mandatory')) echo $mandatory;?></label>
	<input id="user_data_customtext1" type="text" name="customtext1" value="<?php echo $user['customtext1'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_customtext2_visible')) { ?>
	<label for="user_data_customtext2"><?php echo $eve->getSetting('user_customtext2_label'); if ($eve->getSetting('user_customtext2_mandatory')) echo $mandatory;?></label>
	<input id="user_data_customtext2" type="text" name="customtext2" value="<?php echo $user['customtext2'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_customtext3_visible')) { ?>
	<label for="user_data_customtext3"><?php echo $eve->getSetting('user_customtext3_label'); if ($eve->getSetting('user_customtext3_mandatory')) echo $mandatory;?></label>
	<input id="user_data_customtext3" type="text" name="customtext3" value="<?php echo $user['customtext3'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_customtext4_visible')) { ?>
	<label for="user_data_customtext4"><?php echo $eve->getSetting('user_customtext4_label'); if ($eve->getSetting('user_customtext4_mandatory')) echo $mandatory;?></label>
	<input id="user_data_customtext4" type="text" name="customtext4" value="<?php echo $user['customtext4'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_customtext5_visible')) { ?>
	<label for="user_data_customtext5"><?php echo $eve->getSetting('user_customtext5_label'); if ($eve->getSetting('user_customtext5_mandatory')) echo $mandatory;?></label>
	<input id="user_data_customtext5" type="text" name="customtext5" value="<?php echo $user['customtext5'];?>"/>
	<?php } ?>

	<?php if ($eve->getSetting('user_customflag1_visible')) { ?>
	<span>
	<input type="hidden" name="customflag1" value="0"/> <input type="checkbox" id="customflag1_cbx" name="customflag1" value="1" <?php if ($user['customflag1']) echo "checked=\"checked\"";?>/>
	<label for="customflag1_cbx"><?php echo $eve->getSetting('user_customflag1_label'); ?></label>
	</span>
	<?php } ?>

	<script src="lib/jquery/jquery.mask.min.js"></script>
	<script>
	<?php 
		if (!empty($eve->getSetting('user_customtext1_mask')))
			echo '$(document).ready(function(){$(\'#user_data_customtext1\').mask(\''.$eve->getSetting('user_customtext1_mask').'\');});';
		if (!empty($eve->getSetting('user_customtext2_mask')))
			echo '$(document).ready(function(){$(\'#user_data_customtext2\').mask(\''.$eve->getSetting('user_customtext2_mask').'\');});';
		if (!empty($eve->getSetting('user_customtext3_mask')))
			echo '$(document).ready(function(){$(\'#user_data_customtext3\').mask(\''.$eve->getSetting('user_customtext3_mask').'\');});';
		if (!empty($eve->getSetting('user_customtext4_mask')))
			echo '$(document).ready(function(){$(\'#user_data_customtext4\').mask(\''.$eve->getSetting('user_customtext4_mask').'\');});';
		if (!empty($eve->getSetting('user_customtext5_mask')))
			echo '$(document).ready(function(){$(\'#user_data_customtext5\').mask(\''.$eve->getSetting('user_customtext5_mask').'\');});';
	?> 
	</script>
	
	<?php if ($eve->getSetting('user_customflag2_visible')) { ?>
	<span>
	<input type="hidden" name="customflag2" value="0"/> <input type="checkbox" id="customflag2_cbx" name="customflag2" value="1" <?php if ($user['customflag2']) echo "checked=\"checked\"";?>/>
	<label for="customflag2_cbx"><?php echo $eve->getSetting('user_customflag2_label'); ?></label>
	</span>
	<?php } ?>

	<?php if ($eve->getSetting('user_customflag3_visible')) { ?>
	<span>
	<input type="hidden" name="customflag3" value="0"/> <input type="checkbox" id="customflag3_cbx" name="customflag3" value="1" <?php if ($user['customflag3']) echo "checked=\"checked\"";?>/>
	<label for="customflag3_cbx"><?php echo $eve->getSetting('user_customflag3_label'); ?></label>
	</span>
	<?php } ?>

	<?php if ($eve->getSetting('user_customflag4_visible')) { ?>
	<span>
	<input type="hidden" name="customflag4" value="0"/> <input type="checkbox" id="customflag4_cbx" name="customflag4" value="1" <?php if ($user['customflag4']) echo "checked=\"checked\"";?>/>
	<label for="customflag4_cbx"><?php echo $eve->getSetting('user_customflag4_label'); ?></label>
	</span>
	<?php } ?>

	<?php if ($eve->getSetting('user_customflag5_visible')) { ?>
	<span>
	<input type="hidden" name="customflag5" value="0"/> <input type="checkbox" id="customflag5_cbx" name="customflag5" value="1" <?php if ($user['customflag5']) echo "checked=\"checked\"";?>/>
	<label for="customflag5_cbx"><?php echo $eve->getSetting('user_customflag5_label'); ?></label>
	</span>
	<?php } ?>
	
	<?php
	if ($admin_mode)
	{
		?>
		<label><!-- Empty space --></label>
		<div class="dialog_section">Campos do administrador</div><!-- TODO G11N -->

		<label for="user_data_note"><?php echo $eve->_('user.data.note');?></label>
		<input id="user_data_note" type="text" name="note" value="<?php echo $user['note'];?>"/>

		<?php
	}
	else
	{
		// Displaying note as hidden variables // TODO this is a security breach... A malicious user can change this value
		?>
		<input type="hidden" name="note" value="<?php echo $user['note'];?>"/> 
		<?php
	}

	?>
	<button type="submit" class="submit"><?php echo $eve->_('user.action.save');?></button>
	<button type="button" class="submit" onclick="history.go(-1)"><?php echo $eve->_('common.action.back');?></button>
	</form>
	<?php
	
	$eve->output_html_footer();
}
?>
