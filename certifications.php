<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';
require_once 'evesubmissionservice.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);
$eveSubmissionService = new EveSubmissionService($eve);
$certificationmodel = $eveCertificationService->certificationmodel_get($_GET['id']);

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
// Parameter verification
else if ($certificationmodel == null)
{
	$eve->output_error_page('common.message.invalid.parameter');
} 
// Checking if there are post actions
else if (isset($_POST['action'])) switch ($_POST['action'])
{
	case "certification_assignment_to_non_owner":
		$msg = $eveCertificationService->certification_assignment($_GET['id'], $_POST['screenname'], $_POST['submission_id']);
		if (is_int($msg)) // the certification was successfully created and its id was returned.
			$msg = EveCertificationService::CERTIFICATION_ASSIGNMENT_SUCCESS;

		$url = basename(__FILE__)."?id=".$_GET['id'];
		if (isset($_GET['submission_definition_id'])) $url .= "&submission_definition_id=".$_GET['submission_definition_id'];
		$url .= "&msg=".urlencode($msg);
		$eve->output_redirect_page($url);
		break;
	case "certification_delete":
		$msg = $eveCertificationService->certification_delete($_POST['certification_id']);
		
		$url = basename(__FILE__)."?id=".$_GET['id'];
		if (isset($_GET['submission_definition_id'])) $url .= "&submission_definition_id=".$_GET['submission_definition_id'];
		$url .= "&msg=".urlencode($msg);
		$eve->output_redirect_page($url);
		break;
}
// Regular view
else
{
	$eve->output_html_header();
	$eve->output_navigation
	([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.certification_models') => "certification_models.php",
		$eve->_('certifications.title', ["<CERTIFICATION_MODEL_NAME>" => $certificationmodel['name']]) => null
	]);
	
	?>
	<div class="section">
	<?php echo $eve->_('certifications.title', ["<CERTIFICATION_MODEL_NAME>" => $certificationmodel['name']]);?>
	<?php if ($certificationmodel['type'] == 'submissioncertification')
	{
		echo "<button type=\"button\" onclick=\"certification_assignment_to_non_owner()\">{$eve->_('certifications.button.assignment.to.non.owner')}</button>";
	}	
	?>
	</div>
	<?php
	
	if (isset($_GET['msg'])) $eve->output_service_message($_GET['msg']); // Success/Error messages

	if ($certificationmodel['type'] == "submissioncertification")
	{
		?>
		<table class="data_table" id="submissions_table">
		<thead>
		<th>
			<?php
			echo "<select id=\"sel_submissiondefinition\" onchange=\"submission_definition_select()\">";
			echo "<option value=\"null\">{$eve->_('common.select.null')}</option>";
			foreach ($eveSubmissionService->submission_definition_list() as $submissiondefinition)
			{
				echo "<option value=\"{$submissiondefinition['id']}\"";
				if (isset($_GET['submission_definition_id']) && $_GET['submission_definition_id'] == $submissiondefinition['id'])
					echo " selected=\"selected\"";
				echo ">{$submissiondefinition['description']}</option>";
			}
			echo "</select> {$eve->_('certifications.header.submission.id')}";
			?>
		</th>
		<th><?php echo $eve->_('certifications.header.user.email');?></th>
		<th><?php echo $eve->_('certifications.header.user.name');?></th>
		<th><?php echo $eve->_('certifications.header.certification.assignment.type');?></th>
		<th><?php echo $eve->_('certifications.header.certification.id');?></th>
		<th><?php echo $eve->_('certifications.header.certification.views');?></th>
		<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
		</thead>
		<tbody></tbody>
		</table>
		<?php
	}
	else if ($certificationmodel['type'] == "usercertification") 
	{
		?>
		<table class="data_table">
		<thead>
		<th><?php echo $eve->_('certifications.header.user.email');?></th>
		<th><?php echo $eve->_('certifications.header.user.name');?></th>
		<th><?php echo $eve->_('certifications.header.certification.id');?></th>
		<th><?php echo $eve->_('certifications.header.certification.views');?></th>
		<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
		</thead>
		<tbody>
		<?php
		$eveUserService = new EveUserService($eve);
		foreach($eveCertificationService->certificationmodel_user_certification_list($_GET['id']) as $certification)
		{	
			$tr_style = ($certification['id'] !== null) ? " style=\"font-style: italic;\"" : ""; // TODO css style
			echo "<tr$tr_style>";
			echo "<td>{$certification['email']}</td>";
			echo "<td>{$certification['name']}</td>";
			echo "<td>{$certification['id']}</td>";
			echo "<td>{$certification['views']}</td>";
			echo "<td>"; 
			echo ($certification['id'] === null) ?
				"<button type=\"button\" onclick=\"certification_assignment('{$certification['email']}', null)\"><img src=\"style/icons/certification_new.png\"/>{$eve->_('certifications.button.assign')}</button>" : 
				"<button type=\"button\" onclick=\"certification_view({$certification['id']})\"><img src=\"style/icons/view.png\"/>{$eve->_('certifications.button.view')}</button>";
			echo "</td>";
			echo "<td>"; 
			echo ($certification['id'] === null) ?
				"" : 
				"<button type=\"button\" onclick=\"certification_delete({$certification['id']})\"/><img src=\"style/icons/delete.png\"/>{$eve->_('certifications.button.delete')}</button>" ;
			echo "</td>";
			echo "</tr>";
		}
		?>
		</tbody>
		</table>
		<?php
	}
	?>
	<script>
	function certification_assignment(screenname, submission_id)
	{
		var button = event.srcElement;
		while (button.nodeName != 'BUTTON') button = button.parentElement; // sometimes the element inside the button is returned
		while (button.lastChild) button.removeChild(button.lastChild); // removing all the contents of button
		button.innerHTML = '<img src="style/icons/loading.gif" height="16" width="16"> <?php echo $eve->_('common.action.pleasewait');?>';
		button.disabled = true; // preventing the user to click assignment button twice
		var row = button.parentElement.parentElement;

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/certification_assignment.php?certificationmodel_id=<?php echo $_GET['id'];?>&screenname='+screenname+'&submission_id='+submission_id);
		xhr.onload = function() {
			if (xhr.status === 200) 
			{
				if (isNaN(parseInt(xhr.responseText)))
					// Assuming that an error occurred
					alert('<?php echo $eve->_('certification.assignment.error.sql');?> - ' + xhr.responseText);
				else
				{
					// Removing the last 4 cells of the row
					for (let index = 0; index < 4; index++) row.removeChild(row.lastChild);
					// Inserting new 4 cells with the values of the newly created certification
					var cell_id = row.insertCell(-1);
					var cell_views = row.insertCell(-1);
					var cell_option1 = row.insertCell(-1);
					var cell_option2 = row.insertCell(-1);
					cell_id.innerHTML = xhr.responseText;
					cell_views.innerHTML = '0';
					cell_option1.innerHTML = '<button type="button" onclick="certification_view(' + xhr.responseText + ')"><img src="style/icons/accept.png"/><?php echo $eve->_('certifications.button.view');?></button>';
					cell_option2.innerHTML = '<button type="button" onclick="certification_delete(' + xhr.responseText + ')"><img src="style/icons/delete.png"/><?php echo $eve->_('certifications.button.delete');?></button>';
					row.style.fontStyle = 'italic'; // TODO css style
				}
			}
			else 
			{
				alert('<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status);
			}
		};
		xhr.send();
	}
	function certification_assignment_to_non_owner()
	{
		var submissiondefinition_id = document.getElementById('sel_submissiondefinition').value;
		if (submissiondefinition_id == 'null')
			alert('<?php echo $eve->_('certifications.message.submission.definition.mandatory')?>');
		else
		{
			var submission_id = prompt('<?php echo $eve->_('certifications.message.enter.submission.id')?>');
			if (submission_id  != null)
			{
				var screenname = prompt('<?php echo $eve->_('certifications.message.enter.user.screenname')?>');
				if (screenname  != null)
				{
					form = document.createElement('form');
					form.setAttribute('method', 'POST');
					form.setAttribute('action', '<?php echo basename(__FILE__)."?id=".$_GET['id']."&submission_definition_id=";?>'+submissiondefinition_id);
					var1 = document.createElement('input');
					var1.setAttribute('type', 'hidden');
					var1.setAttribute('name', 'action');
					var1.setAttribute('value', 'certification_assignment_to_non_owner');
					form.appendChild(var1);
					var2 = document.createElement('input');
					var2.setAttribute('type', 'hidden');
					var2.setAttribute('name', 'submission_id');
					var2.setAttribute('value', submission_id);
					form.appendChild(var2);
					var3 = document.createElement('input');
					var3.setAttribute('type', 'hidden');
					var3.setAttribute('name', 'screenname');
					var3.setAttribute('value', screenname);
					form.appendChild(var3);
					document.body.appendChild(form);
					form.submit();
				}
			}
		}	
	}
	function certification_delete(certification_id) {
		var raw_message = '<?php echo $eve->_('certifications.message.confirm.delete')?>';
		var message = raw_message.replace('<ID>', certification_id);
		if (confirm(message))
		{
			form = document.createElement('form');
			form.setAttribute('method', 'POST');
			if (document.getElementById('sel_submissiondefinition') != null && document.getElementById('sel_submissiondefinition').value != 'null')
				form.setAttribute('action', '<?php echo basename(__FILE__)."?id={$_GET['id']}&submission_definition_id=";?>'+document.getElementById('sel_submissiondefinition').value);
			else
				form.setAttribute('action', '<?php echo basename(__FILE__)."?id={$_GET['id']}";?>');
			var1 = document.createElement('input');
			var1.setAttribute('type', 'hidden');
			var1.setAttribute('name', 'action');
			var1.setAttribute('value', 'certification_delete');
			form.appendChild(var1);
			var2 = document.createElement('input');
			var2.setAttribute('type', 'hidden');
			var2.setAttribute('name', 'certification_id');
			var2.setAttribute('value', certification_id);
			form.appendChild(var2);
			document.body.appendChild(form);
			form.submit();
		}
	}
	function certification_view(certification_id)
	{
		window.location.href = 'certification.php?id=' + certification_id;
	}
	function submission_definition_select()
	{
		var submission_definition_id = document.getElementById("sel_submissiondefinition").value;
		var table = document.getElementById("submissions_table");		
		while (table.rows.length > 1) table.deleteRow(-1);

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/submission_certifications_list.php?certificationmodel_id=<?php echo $_GET['id'];?>&submission_definition_id=' + submission_definition_id);
		xhr.onload = function() {
		    if (xhr.status === 200) 
			{
				var certifications = JSON.parse(xhr.responseText);
				for (i = 0; i < certifications.length; i++)
				{ 
					var row = table.insertRow(-1);
					var cell_submissionid = row.insertCell(-1);
					var cell_email = row.insertCell(-1);
					var cell_name = row.insertCell(-1);					
					var cell_assignmenttype = row.insertCell(-1);
					var cell_id = row.insertCell(-1);
					var cell_views = row.insertCell(-1);
					var cell_option1 = row.insertCell(-1);
					var cell_option2 = row.insertCell(-1);
					cell_submissionid.innerHTML = certifications[i].submission_id;
					cell_email.innerHTML = certifications[i].email;
					cell_name.innerHTML = certifications[i].name;
					if (certifications[i].assignment_type == 'owner')
						cell_assignmenttype.innerHTML = '<?php echo $eve->_('certifications.label.assignment.type.to.owner');?>';
					else if (certifications[i].assignment_type == 'non.owner')
						cell_assignmenttype.innerHTML = '<?php echo $eve->_('certifications.label.assignment.type.to.non.owner');?>';
					if (certifications[i].id == null)
					{
						cell_option1.innerHTML = '<button type="button" onclick="certification_assignment(\''+certifications[i].email+'\','+certifications[i].submission_id+')"/><img src="style/icons/certification_new.png"/><?php echo $eve->_('certifications.button.assign');?></button>';				
					}
					else
					{
						cell_id.innerHTML = certifications[i].id;
						cell_views.innerHTML = certifications[i].views;
						cell_option1.innerHTML = '<button type="button" onclick="certification_view(' + certifications[i].id  + ')"><img src="style/icons/view.png"/><?php echo $eve->_('certifications.button.view');?></button>';
						cell_option2.innerHTML = '<button type="button" onclick="certification_delete(' + certifications[i].id  + ')"><img src="style/icons/delete.png"/><?php echo $eve->_('certifications.button.delete');?></button>';
						row.style.fontStyle = 'italic'; // TODO css style
					}
				}
		    }
		    else {
		        alert('<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status);
		    }
		};
		xhr.send();
	}

	if (document.getElementById('sel_submissiondefinition') != null) submission_definition_select();
	</script>
	<?php
	
	$eve->output_html_footer();
}?>