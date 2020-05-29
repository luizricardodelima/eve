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
		'paymentlisting_screen_visible_name', 'paymentlisting_export_visible_name', 
		'paymentlisting_screen_visible_email', 'paymentlisting_export_visible_email', 
		'paymentlisting_screen_visible_address', 'paymentlisting_export_visible_address', 
		'paymentlisting_screen_visible_city', 'paymentlisting_export_visible_city', 
		'paymentlisting_screen_visible_state', 'paymentlisting_export_visible_state', 
		'paymentlisting_screen_visible_country', 'paymentlisting_export_visible_country', 
		'paymentlisting_screen_visible_postalcode', 'paymentlisting_export_visible_postalcode', 
		'paymentlisting_screen_visible_birthday', 'paymentlisting_export_visible_birthday', 
		'paymentlisting_screen_visible_gender', 'paymentlisting_export_visible_gender', 
		'paymentlisting_screen_visible_phone1', 'paymentlisting_export_visible_phone1', 
		'paymentlisting_screen_visible_phone2', 'paymentlisting_export_visible_phone2', 
		'paymentlisting_screen_visible_institution', 'paymentlisting_export_visible_institution', 
		'paymentlisting_screen_visible_customtext1', 'paymentlisting_export_visible_customtext1', 
		'paymentlisting_screen_visible_customtext2', 'paymentlisting_export_visible_customtext2', 
		'paymentlisting_screen_visible_customtext3', 'paymentlisting_export_visible_customtext3', 
		'paymentlisting_screen_visible_customtext4', 'paymentlisting_export_visible_customtext4', 
		'paymentlisting_screen_visible_customtext5', 'paymentlisting_export_visible_customtext5', 
		'paymentlisting_screen_visible_customflag1', 'paymentlisting_export_visible_customflag1', 
		'paymentlisting_screen_visible_customflag2', 'paymentlisting_export_visible_customflag2', 
		'paymentlisting_screen_visible_customflag3', 'paymentlisting_export_visible_customflag3', 
		'paymentlisting_screen_visible_customflag4', 'paymentlisting_export_visible_customflag4', 
		'paymentlisting_screen_visible_customflag5', 'paymentlisting_export_visible_customflag5', 
		'paymentlisting_screen_visible_categorydescription', 'paymentlisting_export_visible_categorydescription', 
		'paymentlisting_screen_visible_pmtid', 'paymentlisting_export_visible_pmtid', 
		'paymentlisting_screen_visible_pmttype', 'paymentlisting_export_visible_pmttype', 
		'paymentlisting_screen_visible_pmtvaluepaid', 'paymentlisting_export_visible_pmtvaluepaid', 
		'paymentlisting_screen_visible_pmtvaluereceived', 'paymentlisting_export_visible_pmtvaluereceived', 
		'paymentlisting_screen_visible_pmtdate', 'paymentlisting_export_visible_pmtdate', 
		'paymentlisting_screen_visible_pmtnote', 'paymentlisting_export_visible_pmtnote'
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
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_name" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_name" value="1" <?php if ($settings['paymentlisting_screen_visible_name']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_name" value="0"/><input type="checkbox" name="paymentlisting_export_visible_name" value="1" <?php if ($settings['paymentlisting_export_visible_name']) echo "checked=\"checked\"";?> /></td>
	<td>Nome</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_email" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_email" value="1" <?php if ($settings['paymentlisting_screen_visible_email']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_email" value="0"/><input type="checkbox" name="paymentlisting_export_visible_email" value="1" <?php if ($settings['paymentlisting_export_visible_email']) echo "checked=\"checked\"";?> /></td>
	<td>E-mail</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_address" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_address" value="1" <?php if ($settings['paymentlisting_screen_visible_address']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_address" value="0"/><input type="checkbox" name="paymentlisting_export_visible_address" value="1" <?php if ($settings['paymentlisting_export_visible_address']) echo "checked=\"checked\"";?> /></td>
	<td>Endereço</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_city" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_city" value="1" <?php if ($settings['paymentlisting_screen_visible_city']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_city" value="0"/><input type="checkbox" name="paymentlisting_export_visible_city" value="1" <?php if ($settings['paymentlisting_export_visible_city']) echo "checked=\"checked\"";?> /></td>
	<td>Cidade</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_state" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_state" value="1" <?php if ($settings['paymentlisting_screen_visible_state']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_state" value="0"/><input type="checkbox" name="paymentlisting_export_visible_state" value="1" <?php if ($settings['paymentlisting_export_visible_state']) echo "checked=\"checked\"";?> /></td>
	<td>Estado</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_country" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_country" value="1" <?php if ($settings['paymentlisting_screen_visible_country']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_country" value="0"/><input type="checkbox" name="paymentlisting_export_visible_country" value="1" <?php if ($settings['paymentlisting_export_visible_country']) echo "checked=\"checked\"";?> /></td>
	<td>País</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_postalcode" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_postalcode" value="1" <?php if ($settings['paymentlisting_screen_visible_postalcode']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_postalcode" value="0"/><input type="checkbox" name="paymentlisting_export_visible_postalcode" value="1" <?php if ($settings['paymentlisting_export_visible_postalcode']) echo "checked=\"checked\"";?> /></td>
	<td>Cód. Postal</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_birthday" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_birthday" value="1" <?php if ($settings['paymentlisting_screen_visible_birthday']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_birthday" value="0"/><input type="checkbox" name="paymentlisting_export_visible_birthday" value="1" <?php if ($settings['paymentlisting_export_visible_birthday']) echo "checked=\"checked\"";?> /></td>
	<td>Data nasc.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_gender" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_gender" value="1" <?php if ($settings['paymentlisting_screen_visible_gender']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_gender" value="0"/><input type="checkbox" name="paymentlisting_export_visible_gender" value="1" <?php if ($settings['paymentlisting_export_visible_gender']) echo "checked=\"checked\"";?> /></td>
	<td>Gênero</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_phone1" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_phone1" value="1" <?php if ($settings['paymentlisting_screen_visible_phone1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_phone1" value="0"/><input type="checkbox" name="paymentlisting_export_visible_phone1" value="1" <?php if ($settings['paymentlisting_export_visible_phone1']) echo "checked=\"checked\"";?> /></td>
	<td>Telefone</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_phone2" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_phone2" value="1" <?php if ($settings['paymentlisting_screen_visible_phone2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_phone2" value="0"/><input type="checkbox" name="paymentlisting_export_visible_phone2" value="1" <?php if ($settings['paymentlisting_export_visible_phone2']) echo "checked=\"checked\"";?> /></td>
	<td>Telefone 2</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_institution" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_institution" value="1" <?php if ($settings['paymentlisting_screen_visible_institution']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_institution" value="0"/><input type="checkbox" name="paymentlisting_export_visible_institution" value="1" <?php if ($settings['paymentlisting_export_visible_institution']) echo "checked=\"checked\"";?> /></td>
	<td>Instituição</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customtext1" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customtext1" value="1" <?php if ($settings['paymentlisting_screen_visible_customtext1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customtext1" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customtext1" value="1" <?php if ($settings['paymentlisting_export_visible_customtext1']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext1_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customtext2" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customtext2" value="1" <?php if ($settings['paymentlisting_screen_visible_customtext2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customtext2" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customtext2" value="1" <?php if ($settings['paymentlisting_export_visible_customtext2']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext2_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customtext3" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customtext3" value="1" <?php if ($settings['paymentlisting_screen_visible_customtext3']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customtext3" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customtext3" value="1" <?php if ($settings['paymentlisting_export_visible_customtext3']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext3_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customtext4" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customtext4" value="1" <?php if ($settings['paymentlisting_screen_visible_customtext4']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customtext4" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customtext4" value="1" <?php if ($settings['paymentlisting_export_visible_customtext4']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext4_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customtext5" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customtext5" value="1" <?php if ($settings['paymentlisting_screen_visible_customtext5']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customtext5" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customtext5" value="1" <?php if ($settings['paymentlisting_export_visible_customtext5']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customtext5_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customflag1" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customflag1" value="1" <?php if ($settings['paymentlisting_screen_visible_customflag1']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customflag1" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customflag1" value="1" <?php if ($settings['paymentlisting_export_visible_customflag1']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag1_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customflag2" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customflag2" value="1" <?php if ($settings['paymentlisting_screen_visible_customflag2']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customflag2" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customflag2" value="1" <?php if ($settings['paymentlisting_export_visible_customflag2']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag2_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customflag3" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customflag3" value="1" <?php if ($settings['paymentlisting_screen_visible_customflag3']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customflag3" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customflag3" value="1" <?php if ($settings['paymentlisting_export_visible_customflag3']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag3_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customflag4" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customflag4" value="1" <?php if ($settings['paymentlisting_screen_visible_customflag4']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customflag4" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customflag4" value="1" <?php if ($settings['paymentlisting_export_visible_customflag4']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag4_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_customflag5" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_customflag5" value="1" <?php if ($settings['paymentlisting_screen_visible_customflag5']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_customflag5" value="0"/><input type="checkbox" name="paymentlisting_export_visible_customflag5" value="1" <?php if ($settings['paymentlisting_export_visible_customflag5']) echo "checked=\"checked\"";?> /></td>
	<td><?php echo $eve->getSetting("user_customflag5_label");?></td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_categorydescription" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_categorydescription" value="1" <?php if ($settings['paymentlisting_screen_visible_categorydescription']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_categorydescription" value="0"/><input type="checkbox" name="paymentlisting_export_visible_categorydescription" value="1" <?php if ($settings['paymentlisting_export_visible_categorydescription']) echo "checked=\"checked\"";?> /></td>
	<td>Categoria</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_pmtid" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_pmtid" value="1" <?php if ($settings['paymentlisting_screen_visible_pmtid']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_pmtid" value="0"/><input type="checkbox" name="paymentlisting_export_visible_pmtid" value="1" <?php if ($settings['paymentlisting_export_visible_pmtid']) echo "checked=\"checked\"";?> /></td>
	<td>Id. Pgt.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_pmttype" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_pmttype" value="1" <?php if ($settings['paymentlisting_screen_visible_pmttype']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_pmttype" value="0"/><input type="checkbox" name="paymentlisting_export_visible_pmttype" value="1" <?php if ($settings['paymentlisting_export_visible_pmttype']) echo "checked=\"checked\"";?> /></td>
	<td>Tipo Pgt.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_pmtvaluepaid" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_pmtvaluepaid" value="1" <?php if ($settings['paymentlisting_screen_visible_pmtvaluepaid']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_pmtvaluepaid" value="0"/><input type="checkbox" name="paymentlisting_export_visible_pmtvaluepaid" value="1" <?php if ($settings['paymentlisting_export_visible_pmtvaluepaid']) echo "checked=\"checked\"";?> /></td>
	<td>Valor Pago</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_pmtvaluereceived" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_pmtvaluereceived" value="1" <?php if ($settings['paymentlisting_screen_visible_pmtvaluereceived']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_pmtvaluereceived" value="0"/><input type="checkbox" name="paymentlisting_export_visible_pmtvaluereceived" value="1" <?php if ($settings['paymentlisting_export_visible_pmtvaluereceived']) echo "checked=\"checked\"";?> /></td>
	<td>Valor Recebido</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_pmtdate" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_pmtdate" value="1" <?php if ($settings['paymentlisting_screen_visible_pmtdate']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_pmtdate" value="0"/><input type="checkbox" name="paymentlisting_export_visible_pmtdate" value="1" <?php if ($settings['paymentlisting_export_visible_pmtdate']) echo "checked=\"checked\"";?> /></td>
	<td>Data Pgt.</td></tr>
	<tr>
	<!--<td><input type="hidden" name="paymentlisting_screen_visible_pmtnote" value="0"/><input type="checkbox" name="paymentlisting_screen_visible_pmtnote" value="1" <?php if ($settings['paymentlisting_screen_visible_pmtnote']) echo "checked=\"checked\"";?> /></td>-->
	<td><input type="hidden" name="paymentlisting_export_visible_pmtnote" value="0"/><input type="checkbox" name="paymentlisting_export_visible_pmtnote" value="1" <?php if ($settings['paymentlisting_export_visible_pmtnote']) echo "checked=\"checked\"";?> /></td>
	<td>Observação</td></tr>
	</table>

	
	</form>
	<?php

	$eve->output_html_footer();
}
?>
