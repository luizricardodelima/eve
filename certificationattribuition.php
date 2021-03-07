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
		$msgs[$_POST['submission_id']] = $eveCertificationService->certification_model_attribuition($_GET['id'], $_POST['screenname'], $_POST['submission_id']);
		$eve->output_redirect_page(basename(__FILE__)."?id=".$_GET['id']."&msgs=".urlencode(json_encode($msgs)));
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
	</form>

	<div class="section">
	<label for="certification_ipt">Atribuição de <?php echo $certificationmodel['name'];?></label>

	<?php
	if ($certificationmodel['type'] == 'submissioncertification')
	{
		echo " para ";
		echo "<select id=\"sel_submissiondefinition\" onchange=\"changeSubmissionDefinition()\">";
		echo "<option value=\"null\">{$eve->_('common.select.null')}</option>";
		foreach ($eveSubmissionService->submission_definition_list() as $submissiondefinition)
			echo "<option value=\"{$submissiondefinition['id']}\">{$submissiondefinition['description']}</option>";
		echo "</select>";
		echo "<button type=\"button\" onclick=\"specialSubmissionAttribuition()\">Atribuição especial</button>";
	}
	?>
	</div>
	<?php

	if ($certificationmodel['type'] == "submissioncertification")
	{
		?>
		<table class="data_table" id="submissions_table">
		<thead>
		<th>E-mail</th>
		<th>Nome</th>
		<th>Id Subm.</th>
		<th>Tipo de Atrib.</th>
		<th>Id Cert.</th>
		<th>Visualizações</th>
		<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
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
		<th>E-mail</th>
		<th>Nome</th>
		<th>Id cert.</th>
		<th>Visualizações</th>
		<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
		</thead>
		<tbody>
		<?php
		
		foreach($eveCertificationService->certificationmodel_user_certification_list($_GET['id']) as $user_certification)
		{	
			$tr_style = ($user_certification['id'] !== null) ? " style=\"font-style: italic;\"" : "";
			echo "<tr$tr_style>";
			echo "<td>{$user_certification['email']}</td>";
			echo "<td>{$user_certification['name']}</td>";
			echo "<td>{$user_certification['id']}</td>";
			echo "<td>{$user_certification['views']}</td>";
			echo "<td>"; 
			echo ($user_certification['id'] === null) ?
				"<button type=\"button\" onclick=\"certification_attribuition('{$user_certification['email']}', null)\"/><img src=\"style/icons/certification_new.png\"/>Atribuir</button>" : 
				"<button type=\"button\" onclick=\"window.location.href='certification.php?id={$user_certification['id']}'\"><img src=\"style/icons/view.png\"/>Ver</button>";
			echo "</td>";
			echo "<td>"; 
			echo ($user_certification['id'] === null) ?
				"" : 
				"<button type=\"button\" onclick=\"alert('Implement Delete')\"/><img src=\"style/icons/delete.png\"/>Apagar</button>" ;
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
		function certification_attribuition(screenname, submission_id)
		{
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'service/certification_attribuition.php?certificationmodel_id=<?php echo $_GET['id'];?>&screenname='+screenname+'&submission_id='+submission_id);
			xhr.onload = function() {
				if (xhr.status === 200) 
				{
					if (xhr.responseText == '<?php echo EveCertificationService::CERTIFICATION_MODEL_ATTRIBUITION_SUCCESS; ?>')
						alert('success! reload page - ' + xhr.responseText);
					else
						alert('not success - ' + xhr.responseText);		
				}
				else 
				{
					// HTTP Error message
					alert('<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status);
				}
			};
			xhr.send();
		}
	function changeSubmissionDefinition()
	{
		var submission_definition_id = document.getElementById("sel_submissiondefinition").value;
		var table = document.getElementById("submissions_table");		
		while (table.rows.length > 1) table.deleteRow(-1);

		var structure_variable = null;
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'service/submission_certifications_list.php?certificationmodel_id=<?php echo $_GET['id'];?>&submission_definition_id=' + submission_definition_id);
		xhr.onload = function() {
		    if (xhr.status === 200) {
				structure_variable = JSON.parse(xhr.responseText);
				for (i = 0; i < structure_variable.length; i++)
				{ 
					var row = table.insertRow(-1);
					var cell_email = row.insertCell(-1);
					var cell_name = row.insertCell(-1);
					var cell_submissionid = row.insertCell(-1);
					var cell_attribuitiontype = row.insertCell(-1);
					var cell_id = row.insertCell(-1);
					var cell_views = row.insertCell(-1);
					var cell_option1 = row.insertCell(-1);
					var cell_option2 = row.insertCell(-1);

					cell_email.innerHTML = structure_variable[i].email;
					cell_name.innerHTML = structure_variable[i].name;
					cell_submissionid.innerHTML = structure_variable[i].submission_id;
					cell_attribuitiontype.innerHTML = structure_variable[i].attibuition_type;
					cell_id.innerHTML = structure_variable[i].id;
					cell_views.innerHTML = structure_variable[i].views;
					if (structure_variable[i].id == null)
					{
						cell_option1.innerHTML = '<button type="button" onclick="certification_attribuition(\''+structure_variable[i].email+'\','+structure_variable[i].submission_id+')"/><img src="style/icons/certification_new.png"/>Atribuir</button>';				
					}
					else
					{
						row.style.fontStyle = 'italic';
						cell_option1.innerHTML = '<button type="button" onclick="window.location.href=\'certification.php?id=' + structure_variable[i].id + '\'"><img src="style/icons/view.png"/>Ver</button>';
						cell_option2.innerHTML = '<button type="button" onclick="alert(\'Implement Delete\')"/><img src="style/icons/delete.png"/>Apagar</button>';
					}
				}
		    }
		    else {
				// HTTP Error message
		        alert('<?php echo $eve->_('common.message.error.http.request');?>' + xhr.status);
		    }
		};
		xhr.send();
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
					document.forms['specialsubmissionattibuition_form'].submit();
				}
			}
		}	
	}
	</script>
	<?php
	
	$eve->output_html_footer();
}?>
