<?php
session_start();
require_once '../../eve.class.php';
require_once '../../evepaymentservice.php';
require_once 'lib/config/PagSeguroConfig.php';

$eve = new Eve("../../");
$evePaymentService = new EvePaymentService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("../../userarea.php?sessionexpired=1");
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
else if (isset($_POST['action']))switch ($_POST['action'])
{
	case 'save_payment_information':
		// Saving information
		// TODO PREPARED STATEMENTS
		// TODO DISPLAY MESSAGE THROWN
		$eve->mysqli->query
		("
			delete from	`{$eve->DBPref}settings`
			where 		`key` = 'plugin_pagseguro_paymentinformation';
		");
		$eve->mysqli->query
		("
			insert into	`{$eve->DBPref}settings` (`key`, `value`)
			values		('plugin_pagseguro_paymentinformation', '{$_POST['structure']}');
		");
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&success=1");
		break;
	case 'save_purchase_paymenttype_id':
		$eve->mysqli->query
		("
			delete from	`{$eve->DBPref}settings`
			where 		`key` = 'plugin_pagseguro_paymenttypeid';
		");
		$eve->mysqli->query
		("
			insert into	`{$eve->DBPref}settings` (`key`, `value`)
			values		('plugin_pagseguro_paymenttypeid', '{$_POST['paymenttype_id']}');
		");
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&success=2");
		break;
}
else
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "../../userarea.php", "Ajustes do sistema", "../../settings.php", "Pag seguro", null);
	?>
	<div class="section">Passo 1 - Edite o arquivo <strong>/plugins/pagseguro/lib/config/PagSeguroConfig.php</strong> com as informações de sua conta PagSeguro e parâmetros de execução</div>
	<table class="data_table">
	<thead><th>Variável</th><th>Valor</th></thead>
	<tr><td>$PagSeguroConfig['environment']</td><td><?php echo $PagSeguroConfig['environment'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['email']</td><td><?php echo $PagSeguroConfig['credentials']['email'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['token']['production']</td><td><?php echo $PagSeguroConfig['credentials']['token']['production'];?></td></tr>
	<tr><td>$PagSeguroConfig['credentials']['token']['sandbox']</td><td><?php echo $PagSeguroConfig['credentials']['token']['sandbox'];?></td></tr>
	<tr><td>$PagSeguroConfig['application']['charset']</td><td><?php echo $PagSeguroConfig['application']['charset'];?></td></tr>
	<tr><td>$PagSeguroConfig['log']['active']</td><td><?php var_dump($PagSeguroConfig['log']['active']);?></td></tr>
	<tr><td>$PagSeguroConfig['log']['fileLocation']</td><td><?php echo $PagSeguroConfig['log']['fileLocation'];?></td></tr>
	</table>
	
	<div class="section">Passo 2 - Selecione o tipo de pagamento que corresponde ao pagamento com PagSeguro e clique em Salvar
	<button type="button" onclick="document.forms['save_purchase_paymenttype_id_form'].submit();">Salvar</button>
	</div>
	<form method="post" id="save_purchase_paymenttype_id_form">
	<input type="hidden" name="action" value="save_purchase_paymenttype_id"/>
	<select name="paymenttype_id">
	<option value="" <?php if ($eve->getSetting('plugin_pagseguro_paymenttypeid') == "") echo "selected=\"selected\"";?>><?php echo $eve->_('common.select.null');?></option>
	<?php
	foreach ($evePaymentService->paymenttype_list() as $paymenttype)
	{	
		?>
		<option value="<?php echo $paymenttype['id'];?>" <?php if ($eve->getSetting('plugin_pagseguro_paymenttypeid') == $paymenttype['id']) echo "selected=\"selected\"";?>><?php echo $paymenttype['name'];?></option>
		<?php
	}
	?>
	?>
	</select>
	</form>

	<script>
	// Payment information structure: Array of objects
	// {specification:Integer, start_date:String, end_date:String, price:Double, description:String, code:String}
	// update_structure_field() and update_structure_table() are called after table is created

	var structure = <?php if($eve->getSetting('plugin_pagseguro_paymentinformation')) echo $eve->getSetting('plugin_pagseguro_paymentinformation'); else echo "[]";?>;
	var positionBeingEdited = -1;

	function add_structure_item(type)
	{	
		structure.push({type:type, specification:null, start_date:'', end_date:'', price:0.0, description:'', code:''});
		update_structure_table();
		update_structure_field();
	}

	function edit_structure_item(position)
	{
		hide_structure_item_pane();
		positionBeingEdited = position;
		document.getElementById('edit_structure_item').style.display = 'block';
		document.getElementById('structure_item_type').value= structure[position].type;
		document.getElementById('structure_item_specification').value = structure[position].specification;
		document.getElementById('structure_item_start_date').value = structure[position].start_date;
		document.getElementById('structure_item_end_date').value = structure[position].end_date;
		document.getElementById('structure_item_price').value = structure[position].price;
		document.getElementById('structure_description').value = structure[position].description;
		document.getElementById('structure_code').value = structure[position].code;
	}

	function delete_structure_item(position)
	{
		structure.splice(position, 1);
		update_structure_table();
		update_structure_field();
	}

	function save_structure_item()
	{	
		var e = document.getElementById('structure_item_type');
		structure[positionBeingEdited].type = e.options[e.selectedIndex].value;	
		structure[positionBeingEdited].specification = document.getElementById('structure_item_specification').value;
		structure[positionBeingEdited].start_date = document.getElementById('structure_item_start_date').value;
		structure[positionBeingEdited].end_date = document.getElementById('structure_item_end_date').value;
		structure[positionBeingEdited].price = document.getElementById('structure_item_price').value;
		structure[positionBeingEdited].description = document.getElementById('structure_description').value;
		structure[positionBeingEdited].code = document.getElementById('structure_code').value;
		hide_structure_item_pane();
		update_structure_table();
		update_structure_field();
	}

	function hide_structure_item_pane()
	{
		document.getElementById('edit_structure_item').style.display = 'none';
	}

	function clear_structure_table()
	{
		while (document.getElementById("structure_table").rows.length > 1)
			document.getElementById("structure_table").deleteRow(-1);
	}

	function update_structure_table()
	{
		clear_structure_table();
		for (i = 0; i < structure.length; i++)
		{ 
			var table = document.getElementById("structure_table");
			var row = table.insertRow(-1);
			var cell_pos = row.insertCell(-1);
			var cell_type = row.insertCell(-1);
			var cell_specification = row.insertCell(-1);
			var cell_start_date = row.insertCell(-1);
			var cell_end_date = row.insertCell(-1);
			var cell_price = row.insertCell(-1);
			var cell_description = row.insertCell(-1);
			var cell_code = row.insertCell(-1);
			var cell_edit = row.insertCell(-1);
			var cell_remove = row.insertCell(-1);
			cell_pos.innerHTML = i+1;
			cell_type.innerHTML = structure[i].type;
			cell_specification.innerHTML = structure[i].specification;
			cell_start_date.innerHTML = structure[i].start_date;
			cell_end_date.innerHTML = structure[i].end_date;
			cell_price.innerHTML = structure[i].price;
			cell_description.innerHTML = structure[i].description;
			cell_code.innerHTML = structure[i].code;
			cell_edit.innerHTML = "<button type=\"button\" onclick=\"edit_structure_item("+i+")\"><img src=\"../../style/icons/edit.png\"/></button>";
			cell_remove.innerHTML = "<button type=\"button\" onclick=\"delete_structure_item("+i+")\"><img src=\"../../style/icons/delete.png\"/></button>";
		}
	}

	function update_structure_field()
	{
		document.getElementById("structure").value = JSON.stringify(structure);
	}
	</script>
	
	<form method="post" id="save_payment_information_form">
		<input type="hidden" name="structure" id="structure" value=""/>
		<input type="hidden" name="action" value="save_payment_information"/>
	</form>
	
	<div class="section">Passo 3 - Insira os itens de compra referentes às informações passadas pelo usuário. Ao finalizar, clique em salvar.
	<button type="button" onclick="add_structure_item();">Novo item de compra</button>
	<button type="button" onclick="document.forms['save_payment_information_form'].submit();">Salvar</button>
	</div>
	<div class="section" style="display:none;" id="edit_structure_item">
	<form style="display: grid; grid-gap: 0.5em; grid-template-columns: 2fr 3fr 2fr 3fr 2fr 3fr">
	<label style="text-align:right;">Tipo</label><select id="structure_item_type"><option value="user_category">Categoria de usuário</option><option value="user_custom_flag">Opção personalizada de usuário</option></select>
	<label style="text-align:right;">Especificação</label><input type="number" id="structure_item_specification"/>
	<label style="text-align:right;">Início</label><input type="datetime-local"id="structure_item_start_date"/>
	<label style="text-align:right;">Fim</label><input type="datetime-local"id="structure_item_end_date"/>
	<label style="text-align:right;">Preço</label><input type="number" step="0.01"id="structure_item_price"/>
	<label style="text-align:right;">Descrição</label><input type="text"id="structure_description"/>
	<label style="text-align:right;">Código</label><input type="text"id="structure_code"/>
	<label></label><!--filler-->
	<button type="button" onclick="save_structure_item();">Atualizar</button>
	<label></label><!--filler-->
	<button type="button" onclick="hide_structure_item_pane();">Cancelar</button>	
	</form>
	</div>
	
	<table class="data_table" id="structure_table">
	<thead>
	<th></th>
	<th>Tipo</th>
	<th>Especificação</th>
	<th>Início</th>
	<th>Fim</th>
	<th>Preço</th>
	<th>Descrição</th>
	<th>Código</th>
	<th colspan="2"><?php echo $eve->_('common.table.header.options');?></th>
	</thead>
	<tbody id="structure_tablebody"></tbody>
	</table>
	<script>update_structure_table();update_structure_field();</script>

	<div class="section">Passo 4 - Instale o link na página de pagamentos</div>
	<p>Para chamar o plugin, basta uma chamada para a página <strong>plugins/pagseguro/payment.php</strong> na página de pagamentos. Insira um link para a página citada nas configurações do pagamento. Abaixo uma dica de uso com o botão no estilo dos botões da interface do usuário.</p>
	
	<pre>
	<?php
	echo(htmlentities
	("
		<form action=\"plugins/pagseguro/payment.php\" method=\"post\">
		<button class=\"big\" type=\"submit\">
		<span class=\"buttonicon\">[</span><!-- Do not change this symbol -->
		<span class=\"buttonlabel\">Pagamento via PagSeguro</span>
		</button>
	"));
	?>
	</pre>
	
	<p>Caso queira uma opção mais simples:</p>
	<pre>
	<?php
	echo(htmlentities
	("
		<a href=\"plugins/pagseguro/payment.php\">Pagamento via PagSeguro</a>
	"));
	?>
	</pre>
	<?php
	$eve->output_html_footer();
}
?>
