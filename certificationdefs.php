<?php
session_start();
require_once 'eve.class.php';
require_once 'evecertificationservice.class.php';

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
// Checking whether there are post actions. If so, perform these actions and reload
// current page without post actions. It's done this way to prevent repeating actions
// when page is reloaded.
else if (isset($_POST['action']))
{
	$msg = "";
	$eveCertificationService = new EveCertificationService($eve);
	switch ($_POST['action'])
	{
		case "create":
			$msg = $eveCertificationService->certificationmodel_create($_POST['name']);
			break;
		case "delete":
			$msg = $eveCertificationService->certificationmodel_delete($_POST['id']);
			break;
		case "duplicate":
			$msg = $eveCertificationService->certificationmodel_duplicate($_POST['id']);
			break;
	}
	$eve->output_redirect_page(basename(__FILE__)."?message=$msg");
}
// If there's a valid session, and the current user is administrator and there's no
// action, display the regular listing page.
else
{
	$eve->output_html_header();
	//TODO g11n
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Modelos de certificado", null);

	// Success/Error messages
	if (isset($_GET['message'])) switch ($_GET['message'])
	{
		case EveCertificationService::CERTIFICATIONMODEL_CREATE_ERROR_SQL:
			$eve->output_error_message("Erro no banco de dados ao criar modelo de certificado."); //TODO g11n
			break;
		case EveCertificationService::CERTIFICATIONMODEL_CREATE_SUCCESS:
			$eve->output_success_message("Modelo de certificado criado com sucesso."); //TODO g11n
			break;	
		case EveCertificationService::CERTIFICATIONMODEL_DELETE_ERROR_SQL:
			$eve->output_error_message("Erro no banco de dados ao apagar modelo de certificado."); //TODO g11n
			break;		
		case EveCertificationService::CERTIFICATIONMODEL_DELETE_SUCCESS:
			$eve->output_success_message("Modelo de certificado apagado com sucesso."); //TODO g11n
			break;
		case EveCertificationService::CERTIFICATIONMODEL_DUPLICATE_ERROR_INVALID_ID:
			$eve->output_error_message("Erro ao duplicar modelo de certificado: ID inválido."); //TODO g11n
			break;
		case EveCertificationService::CERTIFICATIONMODEL_DUPLICATE_ERROR_SQL:
			$eve->output_error_message("Erro no banco de dados ao duplicar modelo de certificado."); //TODO g11n
			break;
		case EveCertificationService::CERTIFICATIONMODEL_DUPLICATE_SUCCESS:
			$eve->output_success_message("Modelo de certificado duplicado com sucesso."); //TODO g11n
			break;
	}
	?>
	<div class="section">		
	<button type="button" onclick="certificationmodel_create();">Criar novo</button>
	<button type="button" onclick="certificationmodel_duplicate();">Duplicar</button>
	<button type="button" onclick="window.location='certificationimages.php';">Gerenciar imagens de fundo</button>
	</div>
	
	<table class="data_table">
	<tr>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=id"><?php echo $eve->_('certificationmodel.id');?></a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=type"><?php echo $eve->_('certificationmodel.type');?></a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=name"><?php echo $eve->_('certificationmodel.name');?></a></th>
	<th><a href="<?php echo basename(__FILE__);?>?order-by=text"><?php echo $eve->_('certificationmodel.text');?></a></th>
	<th colspan="4"><?php echo $eve->_('common.table.header.options');?></th>
	</tr>
	<?php

	$eveCertificationService = new EveCertificationService($eve);
	$ordenation = (isset($_GET["order-by"])) ? $_GET["order-by"] : '';
	$certificationmodels = $eveCertificationService->certificationmodel_list($ordenation);

	while ($certificationmodel = $certificationmodels->fetch_assoc())
	{	
		echo "<tr>";
		echo "<td>{$certificationmodel['id']}</td>";
		echo "<td>".$eve->_('certificationmodel.type.'.$certificationmodel['type'])."</td>";
		echo "<td>{$certificationmodel['name']}</td>";
		echo "<td>".mb_substr($certificationmodel['text'], 0, 70, "UTF-8")."</td>";
		echo "<td><button type=\"button\" onclick=\"window.location='certification.php?templateid={$certificationmodel['id']}';\"><img src=\"style/icons/view.png\"/></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location='certificationdef.php?id={$certificationmodel['id']}';\"><img src=\"style/icons/edit.png\"/></button></td>";
		echo "<td><button type=\"button\" onclick=\"window.location='certificationattribuition.php?id={$certificationmodel['id']}';\"><img src=\"style/icons/certification_attribuition.png\"></button></td>";	
		echo "<td><button type=\"button\" onclick=\"certificationmodel_delete({$certificationmodel['id']});\"><img src=\"style/icons/delete.png\"/></button></td>";
		echo "</tr>";
	}
	?>
	</table>

	<script>
	function certificationmodel_create() 
	{
		var certificationmodel_name = prompt('Digite o nome do novo modelo de certificado');
		if (certificationmodel_name != null)
		{			
			document.getElementById('certificationmodel_create_name').value = certificationmodel_name;
			document.getElementById('certificationmodel_create_form').submit();
		}
		return false;
	}
	function certificationmodel_delete(certificationmodel_id) 
	{
		if (confirm('Tem certeza que deseja apagar este modelo de certificado?'))
		{			
			document.getElementById('certificationmodel_delete_id').value = certificationmodel_id;
			document.getElementById('certificationmodel_delete_form').submit();
		}
		return false;
	}
	function certificationmodel_duplicate() 
	{
		var certificationmodel_id = prompt('Digite o ID do modelo de certificado a ser duplicado');
		if (certificationmodel_id != null)
		{
			document.getElementById('certificationmodel_duplicate_id').value=certificationmodel_id;
			document.getElementById('certificationmodel_duplicate_form').submit();
		}
		return false;
	}
	</script>
	
	<form id="certificationmodel_create_form" method="post">
		<input type="hidden" name="action" value="create"/>
		<input type="hidden" name="name" id="certificationmodel_create_name"/>
	</form>
	<form id="certificationmodel_delete_form" method="post">
		<input type="hidden" name="action" value="delete"/>
		<input type="hidden" name="id" id="certificationmodel_delete_id"/>
	</form>
	<form id="certificationmodel_duplicate_form" method="post">
		<input type="hidden" name="action" value="duplicate"/>
		<input type="hidden" name="id" id="certificationmodel_duplicate_id"/>
	</form>

	<?php
	$eve->output_html_footer();
}
?>
