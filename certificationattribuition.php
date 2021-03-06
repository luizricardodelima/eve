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
	case "specialsubmissionattibuition":
		$msgs = array();
		$msgs[$_POST['submission_id']] = $eveCertificationService->certification_model_attribuition($_GET['id'], $_POST['screenname'], $_POST['submission_id'], $_POST['locked']);
		$eve->output_redirect_page(basename(__FILE__)."?id=".$_GET['id']."&msgs=".urlencode(json_encode($msgs)));
		break;
	case "attribuition":
		if ($certificationmodel['type'] == "submissioncertification")
		{
			$msgs = array();
			foreach($_POST['submission'] as $submission_id) //$_POST['submission'] is an array
			{
				$screenname = $eveSubmissionService->submission_get($submission_id)['email'];
				$msgs[$submission_id] = $eveCertificationService->certification_model_attribuition($_GET['id'], $screenname, $submission_id, $_POST['locked']);
			}
			$eve->output_redirect_page(basename(__FILE__)."?id=".$_GET['id']."&msgs=".urlencode(json_encode($msgs)));
		}
		else if  ($certificationmodel['type'] == "usercertification") 
		{
			$msgs = array();
			foreach($_POST['screenname'] as $screenname) //$_POST['screenname'] is an array
			{
				$msgs[$screenname] = $eveCertificationService->certification_model_attribuition($_GET['id'], $screenname, null, $_POST['locked']);
			}
			$eve->output_redirect_page(basename(__FILE__)."?id=".$_GET['id']."&msgs=".urlencode(json_encode($msgs)));
		}
	break;
	default:
		// If an unrecognized post action is passed, simply reload the page
		$eve->output_redirect_page(basename(__FILE__)."?id=".$_GET['id']);
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
		$eve->_('certificationmodel.attribuition') => null
	]);
	
	?>
	<form method="post" action="<?php echo basename(__FILE__)."?id=".$_GET['id'];?>" id="specialsubmissionattibuition_form">
	<input type="hidden" name="action" value="specialsubmissionattibuition"/>
	<input type="hidden" name="submission_id" id="ipt_specialsubmissionattibuition_submissionid"/>
	<input type="hidden" name="screenname" id="ipt_specialsubmissionattibuition_screenname"/>
	<input type="hidden" name="locked" id="ipt_specialsubmissionattibuition_locked"/>
	</form>
	<form method="post" action="<?php echo basename(__FILE__)."?id=".$_GET['id'];?>" id="certificationattribuition_form">
	<input type="hidden" name="action" value="attribuition"/>
	<div class="section">
	<label for="certification_ipt">Atribuição de <?php echo $certificationmodel['name'];?></label>
	<script>
	function changeSubmissionDefinition()
	{
		var submission_definition_id = document.getElementById("sel_submissiondefinition").value;
		var table = document.getElementById("submissions_table");		
		while (table.rows.length > 1) table.deleteRow(-1);

		var structure_variable = null;
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/submissions_list.php?id=' + submission_definition_id);
		xhr.onload = function() {
		    if (xhr.status === 200) {
			structure_variable = JSON.parse(xhr.responseText);
			for (i = 0; i < structure_variable.length; i++)
			{ 
				var row = table.insertRow(-1);
				var cell_check = row.insertCell(-1);
				var cell_id = row.insertCell(-1);
				var cell_date = row.insertCell(-1);
				var cell_email = row.insertCell(-1);
				var cell_authorname = row.insertCell(-1);
				
				var input = document.createElement("input");
				input.type = "checkbox";
				input.name = "submission[]";
				input.value = structure_variable[i].id;
				input.setAttribute("onchange", "toggleRow('this')");
				cell_check.appendChild(input);

				//cell_check.innerHTML = '<input type="checkbox" name="submission[]" value="'+structure_variable[i].id+' onclick="toggleRow(this)"/>';
				cell_id.innerHTML = structure_variable[i].id;
				cell_date.innerHTML = structure_variable[i].date;
				cell_email.innerHTML = structure_variable[i].email;
				cell_authorname.innerHTML = structure_variable[i].name;
			}
		    }
		    else {
				// HTTP Error message
		        alert('<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status);
		    }
		};
		xhr.send();
	}
	function toggle(source, elementname)
	{
		checkboxes = document.getElementsByName(elementname);
		for(var i=0, n=checkboxes.length;i<n;i++)
		{
			checkboxes[i].checked = source.checked;
			toggleRow(checkboxes[i]);
		}
	}

	function toggleRow(source)
	{
		if (source.checked)
		{
			source.parentNode.parentNode.classList.add('selected');
		}
		else
		{
			source.parentNode.parentNode.classList.remove('selected');
		}
	}
	function specialSubmissionAttribuition()
	{
		var submissiondefinition_id = document.getElementById('sel_submissiondefinition').value;
		if (submissiondefinition_id == "null")
			alert("É necessário selecionar uma definição de submissão");
		else
		{
			var submission_id = prompt("Digite o id da submissão");
			if (submission_id  != null)
			{
				var screenname = prompt("Digite o email do usuário");
				if (screenname  != null)
				{
					document.getElementById('ipt_specialsubmissionattibuition_submissionid').value = submission_id;
					document.getElementById('ipt_specialsubmissionattibuition_screenname').value = screenname;
					if (document.getElementById('locked_ipt').checked)					
						document.getElementById('ipt_specialsubmissionattibuition_locked').value = 1;
					else
						document.getElementById('ipt_specialsubmissionattibuition_locked').value = 0;
					document.forms['specialsubmissionattibuition_form'].submit();
				}
			}
		}	
	}
	</script>
	<?php
	if ($certificationmodel['type'] == "submissioncertification")
	{
		echo " para ";
		echo "<select id=\"sel_submissiondefinition\" onchange=\"changeSubmissionDefinition()\">";
		echo "<option value=\"null\">{$eve->_('common.select.null')}</option>";
		foreach ($eveSubmissionService->submission_definition_list() as $submissiondefinition)
			echo "<option value=\"{$submissiondefinition['id']}\">{$submissiondefinition['description']}</option>";
		echo "</select>";
	}
	?>	
	<input type="hidden" name="locked" value="0"/>
	<label for="locked_ipt">
	<input type="checkbox" name="locked" id="locked_ipt" value="1"/>
	Bloqueado
	</label>
	<button type="submit">Atribuir</button>
	<?php
	if ($certificationmodel['type'] == "submissioncertification")
	{
		?>
		<button type="button" onclick="specialSubmissionAttribuition()">Atribuição especial</button>
		<?php
	}
	?>
	</div>
	<?php

	// Success or error messages on certification model attribuition
	if (isset($_GET['msgs']))
	{
		$msgs = json_decode($_GET['msgs'], true);
		$success_count = 0;
		$error_count = 0;
		$detailed_messages = array();
		if (is_array($msgs)) foreach($msgs as $key => $msg)
		{
			if ($msg == EveCertificationService::CERTIFICATION_MODEL_ATTRIBUITION_SUCCESS)
				$success_count++;
			else
				$error_count++;
			$detailed_messages[] = "$key: ".$eve->_($msg);
		}
		$base_message = $eve->_('certificationmodel.attribuition.result', ['<SUCCESS_COUNT>' => $success_count, '<ERROR_COUNT>' => $error_count]);
		$eve->output_info_messagebox($base_message, true, $detailed_messages);
	}

	if ($certificationmodel['type'] == "submissioncertification")
	{
		?>
		<table class="data_table" id="submissions_table">
		<thead>
		<th><input type="checkbox" onClick="toggle(this, 'screenname[]')"/></th>
		<th>Id</th>
		<th>Data de envio</th>
		<th>E-mail</th>
		<th>Nome do autor</th>
		</thead>
		<tbody>
		</tbody>
		</table>
		<?php
	}
	else if ($certificationmodel['type'] == "usercertification") 
	{
		$eveUserService = new EveUserService($eve);
		?>
		<table class="data_table">
		<thead>
		<th><input type="checkbox" onClick="toggle(this, 'screenname[]')"/></th>
		<th>Nome</th>
		<th>E-mail</th>
		<th>Obs</th>
		<th>Bloq.</th>
		</thead>
		<tbody>
		<?php
		
		foreach($eveUserService->user_simple_list('name') as $user)
		{	
			$locked_form = ($user['locked_form']) ? "&#8226;" : "";
			echo "<tr>";
			echo "<td><input type=\"checkbox\" name=\"screenname[]\" value=\"{$user['email']}\" onclick=\"toggleRow(this)\"/></td>";
			echo "<td>{$user['name']}</td>";
			echo "<td>{$user['email']}</td>";
			echo "<td>{$user['note']}</td>";
			echo "<td style=\"text-align:center\">$locked_form</td>";
			echo "</tr>";
		}
		?>
		</tbody>
		</table>
		<?php
	}
	?>
	</form>
	<?php
	
	$eve->output_html_footer();
}?>
