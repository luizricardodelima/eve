<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';
require_once 'evemail.class.php';

$eve = new Eve();
$eveCertificationService = new EveCertificationService($eve);

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
else if (isset($_POST['action'])) 
{
	switch ($_POST['action'])
	{
		case "lock":
			$eveCertificationService->lock_certifications($_POST['certification']);
			$eve->output_redirect_page(basename(__FILE__)."?success=1");
			break;
		case "unlock":
			$eveCertificationService->unlock_certifications($_POST['certification']);
			$eve->output_redirect_page(basename(__FILE__)."?success=2");
			break;
		case "delete":
			$eveCertificationService->delete_certifications($_POST['certification']);
			$eve->output_redirect_page(basename(__FILE__)."?success=3");
			break;
	}
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php",  "Certificados", null);

	if (isset($_GET['success'])) switch ($_GET['success'])
	{
		case 1: $eve->output_success_message("Certificados bloqueados com sucesso."); break;
		case 2: $eve->output_success_message("Certificados desbloqueados com sucesso."); break;
		case 3: $eve->output_success_message("Certificados excluídos com sucesso."); break;
	}
	?>

	<form id="action_form" method="post"></form>

	<script>
	function there_are_selected_items()
	{
		var result = false;		
		var checkboxes = document.getElementsByName('certification[]');
		for(var i=0, n=checkboxes.length;i<n;i++)
		{
			if (checkboxes[i].checked) 
			{
				result = true;
				break;
			}
		}

		return result;
	}

	function action(action_command)
	{
		if (!there_are_selected_items())
			alert("Não há itens selecionados");
		else
		{	
			// Warning message for delete!
			if (action_command == 'delete')
			{
				if (!confirm('Tem certeza que deseja apagar os itens selecionados? Não há como desfazer esta ação')) 
					return false;
			}

			var container = document.getElementById('action_form');
			
			var input = document.createElement('input');
			input.setAttribute('type', 'hidden');
			input.setAttribute('name', 'action');
			input.setAttribute('value', action_command);
			container.appendChild(input);

			checkboxes = document.getElementsByName('certification[]');
			for(var i=0; i < checkboxes.length; i++)
			{
				if (checkboxes[i].checked == true)
				{
					var input_i = document.createElement("input");
					input_i.setAttribute('type', 'hidden');
					input_i.setAttribute('name', 'certification[' + i + ']');
					input_i.setAttribute('value', checkboxes[i].value);
					container.appendChild(input_i);
				}
			}
			document.forms['action_form'].submit();
		}
	}
	</script>

	<div class="section">		
	<button type="button" onclick="action('lock')">Bloquear</button>
	<button type="button" onclick="action('unlock')">Desbloquear</button>
	<button type="button" onclick="action('delete')">Excluir</button>
	</div>
	<script>
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
	</script>
	<table class="data_table">
	<tr>
	<th><input type="checkbox" onClick="toggle(this, 'certification[]')"/></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=name">Nome</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=certificationname">Certificado</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=locked">Bloqueado</a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=views">Visualizações</a></th>	
	<th><?php echo $eve->_('common.table.header.options');?></th>
	</tr>
	<?php

	$ordernation = (isset($_GET["order-by"])) ? $_GET["order-by"] : "";
	foreach ($eveCertificationService->certification_list($ordernation) as $certification_row)
	{	
		$locked = ($certification_row[3]) ? "&#8226;" : "";
		echo "<tr>";
		echo "<td><input type=\"checkbox\" name=\"certification[]\" value=\"{$certification_row[0]}\" onClick=\"toggleRow(this)\"/></td>";
		echo "<td>{$certification_row[1]}</td>";
		echo "<td>{$certification_row[2]}</td>";
		echo "<td>$locked</td>";
		echo "<td>{$certification_row[4]}</td>";
		echo "<td><button type=\"button\" onclick=\"window.location.href='certification.php?id={$certification_row[0]}'\"><img src=\"style/icons/view.png\"/></button></td>";
		echo "</tr>";
	}
	?>
	</table>
	
	<?php
	$eve->output_html_footer();
}?>
