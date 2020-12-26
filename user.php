<?php
session_start();
require_once 'lib/countries/countries.php';
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$EveUserService = new EveUserService($eve);

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
	// If a non-admin tries to pass a 'screenname' GET parameter, they will see this page with their
	// own data, ignoring 'screenname' passed as a GET parameter.
	$admin_mode = (isset($_GET['user']) && $eve->is_admin($_SESSION['screenname'])) ? 1 : 0;			
	$email = ($admin_mode == 1) ? $_GET['user'] : $_SESSION['screenname'];
	$user = $EveUserService->user_get($email); // User data
	$validation_errors = array();

	if (!empty($_POST))
	{
		// There are POST data. There is no need to retrieve data from database.
		// POST data will be validadated. If valid, they will be saved on db,
		// otherwise they will only be displayed with an error message.

		// $_POST['lock_request_from_user'] is not user data, but a request for
		// locking the form sent by user in the non-admin mode.
		$lock_request_from_user = isset($_POST['lock_request_from_user']) ? $_POST['lock_request_from_user'] : 0;
		unset($_POST['lock_request_from_user']);

		// TODO why not saving POST directly?
		// TODO lock_request_from_user? why not sending this to the user_save method?
		foreach ($_POST as $column => $value) {$user[$column] = $value;}
		
		// START OF VALIDATION (NOT PERFORMED IN ADMIN MODE)
		if (!$admin_mode)
		{
			// TODO proper g11n
			$validation_invalid_field_start = "O valor para o campo ";
			$validation_invalid_field_end = " &eacute; inv&aacute;lido.";
			$validation_missing_field_start = "O campo ";
			$validation_missing_field_end = " n&atilde;o pode estar em branco.";

			// Validating name, if visible and mandatory
			if ($eve->getSetting('user_name_visible') && $eve->getSetting('user_name_mandatory') && $user['name'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.name').$validation_missing_field_end;
			// Validating birthday, if visible and mandatory
			if ($eve->getSetting('user_birthday_visible') && $eve->getSetting('user_birthday_mandatory') && !strtotime($user['birthday']))
				$validation_errors[] = $validation_invalid_field_start.$eve->_('user.data.birthday').$validation_invalid_field_end;
			// Validating gender, if visible and mandatory
			if ($eve->getSetting('user_gender_visible') && $eve->getSetting('user_gender_mandatory') && $user['gender'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.gender').$validation_missing_field_end;
			// Validating address, if visible and mandatory
			if ($eve->getSetting('user_address_visible') && $eve->getSetting('user_address_mandatory') && $user['address'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.address').$validation_missing_field_end;
			// Validating city, if visible and mandatory
			if ($eve->getSetting('user_city_visible') && $eve->getSetting('user_city_mandatory') && $user['city'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.city').$validation_missing_field_end;
			// Validating state, if visible and mandatory
			if ($eve->getSetting('user_state_visible') && $eve->getSetting('user_state_mandatory') && $user['state'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.state').$validation_missing_field_end;
			// Validating country, if visible and mandatory
			if ($eve->getSetting('user_country_visible') && $eve->getSetting('user_country_mandatory') && $user['country'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.country').$validation_missing_field_end;
			// Validating postalcode, if visible and mandatory
			if ($eve->getSetting('user_postalcode_visible') && $eve->getSetting('user_postalcode_mandatory') && $user['postalcode'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.postalcode').$validation_missing_field_end;
			// Validating phone1, if visible and mandatory
			if ($eve->getSetting('user_phone1_visible') && $eve->getSetting('user_phone1_mandatory') && $user['phone1'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.phone1').$validation_missing_field_end;
			// Validating phone2, if visible and mandatory
			if ($eve->getSetting('user_phone2_visible') && $eve->getSetting('user_phone2_mandatory') && $user['phone2'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.phone2').$validation_missing_field_end;
			// Validating institution, if visible and mandatory
			if ($eve->getSetting('user_institution_visible') && $eve->getSetting('user_institution_mandatory') && $user['institution'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->_('user.data.institution').$validation_missing_field_end;
			// TODO VALIDATE CUSTOMTEXTS ACCORDING TO THEIR MASKS
			// Validating customtext1, if visible and mandatory
			if ($eve->getSetting('user_customtext1_visible') && $eve->getSetting('user_customtext1_mandatory') && $user['customtext1'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->getSetting('user_customtext1_label').$validation_missing_field_end;
			// Validating customtext2, if visible and mandatory
			if ($eve->getSetting('user_customtext2_visible') && $eve->getSetting('user_customtext2_mandatory') && $user['customtext2'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->getSetting('user_customtext2_label').$validation_missing_field_end;
			// Validating customtext3, if visible and mandatory
			if ($eve->getSetting('user_customtext3_visible') && $eve->getSetting('user_customtext3_mandatory') && $user['customtext3'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->getSetting('user_customtext3_label').$validation_missing_field_end;
			// Validating customtext4, if visible and mandatory
			if ($eve->getSetting('user_customtext4_visible') && $eve->getSetting('user_customtext4_mandatory') && $user['customtext4'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->getSetting('user_customtext4_label').$validation_missing_field_end;
			// Validating customtext5, if visible and mandatory
			if ($eve->getSetting('user_customtext5_visible') && $eve->getSetting('user_customtext5_mandatory') && $user['customtext5'] == '')
				$validation_errors[] = $validation_missing_field_start.$eve->getSetting('user_customtext5_label').$validation_missing_field_end;
		}		
		// END OF VALIDATION

		if (empty($validation_errors))
		{
			// Updating user data, if there are no validating errors
			$EveUserService->user_save($user);
			if (($eve->getSetting('block_user_form') == 'after_sending') && $lock_request_from_user)
			{
				// TODO these things should be inserted in a service.
				$eve->mysqli->query("UPDATE `{$eve->DBPref}userdata` SET `locked_form` = 1 WHERE `email` = '$email';");
				$user['locked_form'] = 1;
			}
			$eve->output_redirect_page("userarea.php?systemmessage=userdata.saved.successfully");
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
		<div class="section"><?php echo $eve->_('userarea.option.userdata');?> - modo administrador <!-- TODO G11N -->
		<button type="button" onclick="document.forms['user_form'].submit()"><?php echo $eve->_('common.action.save');?></button>
		<button type="button" onclick="document.getElementById('credential_form').submit()">Gerar credencial</button>
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
	
	if ($validation_errors)
		$eve->output_error_list_message($validation_errors);

	$mandatory = "<small> (obrigatório)</small>"; //TODO g11n
	?>
	<form method="post" id="user_form" class="dialog_panel" <?php if ($admin_mode) echo "action=\"".basename(__FILE__)."?user=$email\"";?>>

	<?php
	if (!$user['locked_form'] && !$admin_mode && $eve->getSetting('user_display_custom_message_on_unlocked_form'))
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
	foreach ($EveUserService->user_genders() as $gender)
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
		<div class="dialog_section">Campos do administrador</div>

		<label for="user_data_note"><?php echo $eve->_('user.data.note');?></label>
		<input id="user_data_note" type="text" name="note" value="<?php echo $user['note'];?>"/>

		<span>
		<input type="hidden" name="locked_form" value="0"/> <input type="checkbox" id="locked_form_cbx" name="locked_form" value="1" <?php if ($user['locked_form']) echo "checked=\"checked\"";?>/>
		<label for="locked_form_cbx"><?php echo $eve->_('user.data.locked.form'); ?></label>
		</span>
		<?php
	}
	else
	{
		// Displaying note and lockd form as hidden variables
		// TODO kind of a security breach...
		?>
		<input type="hidden" name="note" value="<?php echo $user['note'];?>"/> 
		<input type="hidden" name="locked_form" value="<?php echo $user['locked_form'];?>"/> 
		<?php
	}

	// Control buttons - They are initially storead in an array label (g11n key) => onclick action (javascript)
	$buttons = array();

	// Save and send button - Displayed only if "after_sending" is set as the value of "block_user_form" setting.	
	if (($eve->getSetting('block_user_form') == 'after_sending') && !$user['locked_form'] && !$admin_mode)
	{
		$buttons['user.action.saveandsend'] = 'save_and_send();'; 
		?>
		<script>
		function save_and_send()
		{
			if (confirm("Depois do finalizar, você não poderá mais alterar os dados. Confirma?"))
			{
				// Creating tag <input type="hidden" name="lock_request_from_user" value="1"> and
				// inserting this inside user_form. This will indicate a lock request from user.
				var v_input = document.createElement("input");
				v_input.setAttribute("type", "hidden");
				v_input.setAttribute("name", "lock_request_from_user");
				v_input.setAttribute("value", "1");
				document.getElementById("user_form").appendChild(v_input);
				document.getElementById("user_form").submit();
			}
		return false;
		}
		</script>
		<?php
	}

	// Save button - Displayed only if form is not blocked or if form is viewed in admin mode
	if (!$user['locked_form'] || $admin_mode)
		$buttons['user.action.save'] = 'document.forms[\'user_form\'].submit();';

	// Back button - Always displayed
	$buttons['common.action.back'] = 'window.location.href=\'userarea.php\';';

	// Displaying buttons on form
	echo "<span style=\"display: grid; grid-gap: 0.5em; grid-template-columns:";
	switch (count ($buttons)) { case 3: echo " 1fr 1fr 1fr"; break; case 2: echo " 2fr 1fr"; break; default: echo " 1fr"; break;}
	echo ";\">";
	foreach ($buttons as $label => $action)
	{
		echo "<button type=\"button\" onclick=\"$action\" class=\"submit\">{$eve->_($label)}</button>";
	}
	echo "</span>";

	?>
	</form>
	<?php
	
	// If this form is locked, call js code to disable all inputs
	if ($user['locked_form'] && !$admin_mode)
	{ 
		?>
		<script>
		var form = document.getElementById('user_form');
		var elements = form.elements;
		for (var i = 0, len = elements.length; i < len; ++i)
		{
	    		elements[i].readOnly = true;
		}
		</script>
		<?php
	}

	$eve->output_html_footer();
}
?>
