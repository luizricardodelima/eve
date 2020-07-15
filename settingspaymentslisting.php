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
	$eveSettingsService->settings_update($_POST);
	$eve->output_redirect_page(basename(__FILE__)."?saved=1");
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('userarea.option.admin.settings'), "settings.php", "Listagem dos Pagamentos", null);
	$eve->output_wysiwig_editor_code();

	if (isset($_GET['saved']))
		$eve->output_success_message("Ajustes salvos com sucesso.");

	$settings = $eveSettingsService->settings_get
	(
		'payments_view_name', 'payments_export_name', 
		'payments_view_email', 'payments_export_email', 
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
		'payments_view_customflag5', 'payments_export_customflag5', 
		'payments_view_pmtid', 'payments_export_pmtid', 
		'payments_view_pmtmethod', 'payments_export_pmtmethod', 
		'payments_view_pmtvaluepaid', 'payments_export_pmtvaluepaid', 
		'payments_view_pmtvaluereceived', 'payments_export_pmtvaluereceived', 
		'payments_view_pmtdate', 'payments_export_pmtdate', 
		'payments_view_pmtnote', 'payments_export_pmtnote'
	);

	?>
	<div class="section">Listagem dos Pagamentos
	<button type="button" onclick="document.forms['settings_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	</div>

	<form id="settings_form" method="post">
	<table class="data_table">
	<thead>
	<!--<th style="width: 5%">Tela</th>-->
	<th style="width: 5%">Exportar</th>
	<th style="width: 90%">Campo</th>
	</thead>
	<tr>
	<!--<td><input type="hidden" name="payments_view_name" value="0"/><input type="checkbox" name="payments_view_name" value="1" <?php if ($settings['payments_view_name']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_name" value="0"/><input type="checkbox" name="payments_export_name" value="1" <?php if ($settings['payments_export_name']) echo "checked=\"checked\"";?> /></td>
	<td>Nome</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_email" value="0"/><input type="checkbox" name="payments_view_email" value="1" <?php if ($settings['payments_view_email']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_email" value="0"/><input type="checkbox" name="payments_export_email" value="1" <?php if ($settings['payments_export_email']) echo "checked=\"checked\"";?> /></td>
	<td>E-mail</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_address" value="0"/><input type="checkbox" name="payments_view_address" value="1" <?php if ($settings['payments_view_address']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_address" value="0"/><input type="checkbox" name="payments_export_address" value="1" <?php if ($settings['payments_export_address']) echo "checked=\"checked\"";?> /></td>
	<td>Endereço</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_city" value="0"/><input type="checkbox" name="payments_view_city" value="1" <?php if ($settings['payments_view_city']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_city" value="0"/><input type="checkbox" name="payments_export_city" value="1" <?php if ($settings['payments_export_city']) echo "checked=\"checked\"";?> /></td>
	<td>Cidade</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_state" value="0"/><input type="checkbox" name="payments_view_state" value="1" <?php if ($settings['payments_view_state']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_state" value="0"/><input type="checkbox" name="payments_export_state" value="1" <?php if ($settings['payments_export_state']) echo "checked=\"checked\"";?> /></td>
	<td>Estado</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_country" value="0"/><input type="checkbox" name="payments_view_country" value="1" <?php if ($settings['payments_view_country']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_country" value="0"/><input type="checkbox" name="payments_export_country" value="1" <?php if ($settings['payments_export_country']) echo "checked=\"checked\"";?> /></td>
	<td>País</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_postalcode" value="0"/><input type="checkbox" name="payments_view_postalcode" value="1" <?php if ($settings['payments_view_postalcode']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_postalcode" value="0"/><input type="checkbox" name="payments_export_postalcode" value="1" <?php if ($settings['payments_export_postalcode']) echo "checked=\"checked\"";?> /></td>
	<td>Cód. Postal</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_birthday" value="0"/><input type="checkbox" name="payments_view_birthday" value="1" <?php if ($settings['payments_view_birthday']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_birthday" value="0"/><input type="checkbox" name="payments_export_birthday" value="1" <?php if ($settings['payments_export_birthday']) echo "checked=\"checked\"";?> /></td>
	<td>Data nasc.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_gender" value="0"/><input type="checkbox" name="payments_view_gender" value="1" <?php if ($settings['payments_view_gender']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_gender" value="0"/><input type="checkbox" name="payments_export_gender" value="1" <?php if ($settings['payments_export_gender']) echo "checked=\"checked\"";?> /></td>
	<td>Gênero</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_phone1" value="0"/><input type="checkbox" name="payments_view_phone1" value="1" <?php if ($settings['payments_view_phone1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_phone1" value="0"/><input type="checkbox" name="payments_export_phone1" value="1" <?php if ($settings['payments_export_phone1']) echo "checked=\"checked\"";?> /></td>
	<td>Telefone</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_phone2" value="0"/><input type="checkbox" name="payments_view_phone2" value="1" <?php if ($settings['payments_view_phone2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_phone2" value="0"/><input type="checkbox" name="payments_export_phone2" value="1" <?php if ($settings['payments_export_phone2']) echo "checked=\"checked\"";?> /></td>
	<td>Telefone 2</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_institution" value="0"/><input type="checkbox" name="payments_view_institution" value="1" <?php if ($settings['payments_view_institution']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_institution" value="0"/><input type="checkbox" name="payments_export_institution" value="1" <?php if ($settings['payments_export_institution']) echo "checked=\"checked\"";?> /></td>
	<td>Instituição</td></tr>
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
	<tr>
	<!--<td><input type="hidden" name="payments_view_pmtid" value="0"/><input type="checkbox" name="payments_view_pmtid" value="1" <?php if ($settings['payments_view_pmtid']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_pmtid" value="0"/><input type="checkbox" name="payments_export_pmtid" value="1" <?php if ($settings['payments_export_pmtid']) echo "checked=\"checked\"";?> /></td>
	<td>Id. Pgt.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_pmtmethod" value="0"/><input type="checkbox" name="payments_view_pmtmethod" value="1" <?php if ($settings['payments_view_pmtmethod']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_pmtmethod" value="0"/><input type="checkbox" name="payments_export_pmtmethod" value="1" <?php if ($settings['payments_export_pmtmethod']) echo "checked=\"checked\"";?> /></td>
	<td>Tipo Pgt.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_pmtvaluepaid" value="0"/><input type="checkbox" name="payments_view_pmtvaluepaid" value="1" <?php if ($settings['payments_view_pmtvaluepaid']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_pmtvaluepaid" value="0"/><input type="checkbox" name="payments_export_pmtvaluepaid" value="1" <?php if ($settings['payments_export_pmtvaluepaid']) echo "checked=\"checked\"";?> /></td>
	<td>Valor Pago</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_pmtvaluereceived" value="0"/><input type="checkbox" name="payments_view_pmtvaluereceived" value="1" <?php if ($settings['payments_view_pmtvaluereceived']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_pmtvaluereceived" value="0"/><input type="checkbox" name="payments_export_pmtvaluereceived" value="1" <?php if ($settings['payments_export_pmtvaluereceived']) echo "checked=\"checked\"";?> /></td>
	<td>Valor Recebido</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_pmtdate" value="0"/><input type="checkbox" name="payments_view_pmtdate" value="1" <?php if ($settings['payments_view_pmtdate']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_pmtdate" value="0"/><input type="checkbox" name="payments_export_pmtdate" value="1" <?php if ($settings['payments_export_pmtdate']) echo "checked=\"checked\"";?> /></td>
	<td>Data Pgt.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="payments_view_pmtnote" value="0"/><input type="checkbox" name="payments_view_pmtnote" value="1" <?php if ($settings['payments_view_pmtnote']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="payments_export_pmtnote" value="0"/><input type="checkbox" name="payments_export_pmtnote" value="1" <?php if ($settings['payments_export_pmtnote']) echo "checked=\"checked\"";?> /></td>
	<td>Observação</td></tr>
	</table>

	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
