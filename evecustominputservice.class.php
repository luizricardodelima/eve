<?php
require_once 'eve.class.php';
require_once 'lib/filechecker/filechecker.php';

/**
 * A Custom Input allows an user to define a structure of fields that can be
 * sent. Given this structure, other users can input data according to it.
 * 
 * The structure is represented as an array of objects. The objects are defined as
 * follows:
 *	{
 *		type:"text"|"bigtext"|"array"|"enum"|"check"|"file",
 *		description: String,
 *		unrestrict_view: Boolean,
 *		mandatory: Boolean,
 *		spec: Object
 *	}
 *	if type=="text":	spec:null
 *	if type=="bigtext"	spec:{min_words:Integer, max_words:Integer}
 *	if type=="array"	spec:{items:String[]}
 *	if type=="enum"		spec:{items:String[]}
 *	if type=="check"	spec:null
 *	if type=="file"		spec:{file_types:String[], max_size:Integer}
 * 
 * After a structure is created, input controls can be generated according to this
 * structure so user can input data. The data inputted by the user is called
 * content. After that, the content can be validated and, for instance, stored in a
 * database. The content is represented by an array of strings, in the same order as
 * defined in the structure (the indexes in this array are numbers from zero to n-1).
 * Therefore, the structure is required in the methods that deal with content.
 * 
 * In the methods of this class, structure and content have to be passed as objects,
 * if you need to save in the database or serialize (to be displayed in html, for 
 * example) the content or structure, you will have to use json_encode() method. If
 * you need to use them again in this class, you will need to use json_decode().
 * @deprecated
 */
class EveCustomInputService
{
	private $eve;

	const CUSTOM_INPUT_VALIDATION_ERROR_MANDATORY = 7;
	const CUSTOM_INPUT_VALIDATION_ERROR_UNDER_MIN_WORDS = 8;
	const CUSTOM_INPUT_VALIDATION_ERROR_OVER_MAX_WORDS = 9;
	const CUSTOM_INPUT_VALIDATION_ERROR_FILE_ERROR = 10;
	const CUSTOM_INPUT_VALIDATION_ERROR_FILE_EXCEEDED_SIZE = 11;
	const CUSTOM_INPUT_VALIDATION_ERROR_FILE_WRONG_TYPE = 12;
	const CUSTOM_INPUT_VALIDATION_ERROR_FILE_UPLOAD_ERROR = 13;
	
	/**
	 * Exports a human-readable array of the $content.
	 */
	function custom_input_format_content($structure, $content, $only_unrestrict_view_items = false)
	{
		$result = array();
		if (empty ($structure)) return $result;
		foreach ($structure as $i => $structure_item)
		{	
			if ($only_unrestrict_view_items && !$structure_item->unrestrict_view) continue;
			switch($structure_item->type)
			{
				case 'text':
					$result[$structure_item->description] = $content[$i];
					break;
				case 'bigtext':
					$result[$structure_item->description] = $content[$i];
					break;
				case 'array':
					$array_items = array();
					foreach ($structure_item->spec->items as $j => $structure_item_item)
						$array_items[] = "$structure_item_item: {$content[$i][$j]}";
					$result[$structure_item->description] = implode("; ", $array_items); //TODO: G11n on the glue
					break;
				case 'enum':
					$result[$structure_item->description] = "{$structure_item->spec->items[$content[$i]]}";
					break;
				case 'check':
					if ($content[$i])
						$result[$structure_item->description] = $this->eve->_('common.label.yes');
					else
						$result[$structure_item->description] = $this->eve->_('common.label.no');
					break;
				case 'file':
					$result[$structure_item->description] = isset($content[$i]) ? "<a href=\"{$this->eve->url()}{$content[$i]}\">{$content[$i]}</a>" : '';
					break;
			}
		}
		return $result;
	}

	/**
	 * Exports a human-readable html code of the $content.
	 */
	function custom_input_format_content_HTML($structure, $content, $only_unrestrict_view_items = false)
	{
		$table_of_contents = $this->custom_input_format_content($structure, $content, $only_unrestrict_view_items);
		$result = "<table class=\"data_table\">"; // TODO REMOVE CLASS DEFINITION. IT HAS TO BE DEALT WITH IN THE FRONT END
		foreach ($table_of_contents as $key => $value)
			$result .= "<tr><td>$key</td><td>$value</td></tr>";
		$result .= "</table>";
		return $result;
	}

	/**
	 * The form that contains these controls must have the attribute  enctype="multipart/form-data"
	 * so it can handle files correctly.
	 */
	function custom_input_output_html_controls($structure, $html_name = "content", $content = null, $disabled = false)
	{
		$disabled_attr = ($disabled) ? "disabled=\"disabled\"" : "";
		$mandatory = $this->eve->_('common.custominput.mandatory');
		foreach ($structure as $i => $item) switch($item->type)
		{
			case 'text':
				echo "<label for=\"{$html_name}[{$i}]\">{$item->description}";
				if ($item->mandatory) echo "&nbsp;<small>$mandatory</small>";
				echo "</label>";
				echo "<input type=\"text\" name=\"{$html_name}[{$i}]\" id=\"{$html_name}[{$i}]\" $disabled_attr";
				if ($content)
					echo " value=\"".htmlentities($content[$i], ENT_QUOTES, 'utf-8')."\"";
				echo "/>";
				break;
			case 'bigtext':
				?>
				<label for="<?php echo $html_name."[$i]"; ?>">
				<?php echo $item->description; ?>
				<?php if ($item->mandatory) echo "&nbsp;<small>$mandatory</small>"; ?>
				</label>
				
				<div>
				<textarea
					id="<?php echo $html_name."[$i]";?>"
					name="<?php echo $html_name."[$i]";?>"
					<?php if ($item->spec->min_words || $item->spec->max_words) echo "onkeyup=\"bgt_check_{$html_name}_{$i}()\"";?>
					style="width: 100%;"
					rows="15"
					<?php echo $disabled_attr;?>><?php 
				
				if ($content) echo htmlentities($content[$i], ENT_QUOTES, 'utf-8');
				
				?></textarea>
				<?php 
				if ($item->spec->min_words || $item->spec->max_words)
				{
				?>
				<div id="bgt_inf_<?php echo $html_name."_".$i;?>" style="font-size: 0.8rem">
				<img id="bgt_war_<?php echo $html_name."_".$i;?>" src="<?php echo $this->eve->basepath;?>style/icons/warning.png"/>
				<?php echo $this->eve->_('common.custominput.wordcount');?>
				&nbsp;<label id="bgt_cnt_<?php echo $html_name."_".$i;?>">0</label>&nbsp;
				<?php echo $this->eve->_('common.custominput.wordcount.minimum');?>
				<?php echo $item->spec->min_words;?>
				&nbsp;-&nbsp;
				<?php echo $this->eve->_('common.custominput.wordcount.maximum');?>
				<?php echo $item->spec->max_words;?>
				</div>
				<script>
				function bgt_check_<?php echo $html_name."_".$i;?>()
				{
					// Counting words regex. It will count all groups of non-whitespace chars
					var words = document.getElementById('<?php echo $html_name."[$i]";?>').value.match(/\S+/g);
					// Function max can return null if obj.value is an empty string
					if (words == null) words = [];

					$("#bgt_cnt_<?php echo $html_name."_".$i;?>").html(words.length);

					if(words.length < <?php echo $item->spec->min_words;?> || words.length > <?php echo $item->spec->max_words;?>)
					{
						$("#bgt_war_<?php echo $html_name."_".$i;?>").show();
						$("#bgt_inf_<?php echo $html_name."_".$i;?>").css("font-weight","bold");
					}
					else
					{
						$("#bgt_war_<?php echo $html_name."_".$i;?>").hide();
						$("#bgt_inf_<?php echo $html_name."_".$i;?>").css("font-weight","normal");
					}
				}
				bgt_check_<?php echo $html_name."_".$i;?>();
				</script>
				<?php
				}
				?>
				</div>
				<?php
				break;
			case 'array':
				echo "<span>{$item->description}";
				if ($item->mandatory) echo "&nbsp;<small>$mandatory</small>";
				echo "</span>";
				echo "<div style=\"display: grid; grid-template-columns:";
				for ($k = 0; $k < sizeof($item->spec->items); $k++) echo " 2fr 5fr";
				echo ";\">";
				foreach ($item->spec->items as $j => $item_item)
				{
					echo "<label for=\"{$html_name}[{$i}][{$j}]\" style=\"text-align: right;\">$item_item:&nbsp;</label>";
					echo "<input type=\"text\" name=\"{$html_name}[{$i}][{$j}]\" id=\"{$html_name}[{$i}][{$j}]\" $disabled_attr";
					if ($content) echo " value=\"".htmlentities($content[$i][$j], ENT_QUOTES, 'utf-8')."\"";
					echo "/>";
				}				
				echo "</div>";
				break;
			case 'enum':
				echo "<span>";
				echo "<label for=\"{$html_name}[{$i}]\">{$item->description}</label>";
				if ($item->mandatory) echo "&nbsp;<small>$mandatory</small>";
				echo "</span>";
				echo "<select id=\"{$html_name}[{$i}]\" name=\"{$html_name}[{$i}]\" $disabled_attr>";
				echo "<option";
				if ($content && $content[$i]=="") echo " selected=\"selected\"";
				echo " value=\"\">{$this->eve->_('common.select.null')}</option>";
				foreach ($item->spec->items as $j => $item_item)
				{
					echo "<option value=\"$j\"";
					if ($content && $content[$i]==$j) echo " selected=\"selected\"";
					echo">$item_item</option>";
				}				
				echo "</select>";
				break;
			case 'check':
				echo "<span>";
				echo "<input type=\"hidden\" value=\"0\" name=\"{$html_name}[{$i}]\"/>";
				echo "<input type=\"checkbox\" value=\"1\" name=\"{$html_name}[{$i}]\" id=\"{$html_name}[{$i}]\" $disabled_attr";
				if ($content && $content[$i]) echo " checked=\"checked\"";
				echo "/>";
				echo "<label for=\"{$html_name}[{$i}]\">{$item->description}</label>";
				echo "</span>";
				break;
			case 'file':
				?>
				<label for="<?php echo $html_name."[$i]"; ?>">
				<?php echo $item->description; ?>
				<?php if ($item->mandatory) echo "&nbsp;<small>$mandatory</small>"; ?>
				</label>
				
				<?php if(isset($content) && isset($content[$i]) && $content[$i] != '') { ?>
				<div id="fil_info_<?php echo $html_name."_".$i;?>">
				<a href="<?php echo $content[$i];?>"><?php echo $content[$i];?></a>
				<?php if (!$disabled) { ?>
				<button type="button" style="float: right;" onclick="fil_input_show_<?php echo $html_name.'_'.$i;?>()">Alterar</button>
				<?php } ?>
				<input type="hidden" name="<?php echo $html_name."[$i]";?>" value="<?php echo $content[$i];?>"/>
				</div>
				<?php } ?>
				
				<div id="fil_input_<?php echo $html_name."_".$i;?>" style="display: none;">
				<input type="file" name="<?php echo $html_name."[$i]";?>" id="<?php echo "{$html_name}_{$i}_input";?>" disabled="disabled"/>
				<div style="font-size: 0.8rem">
				<?php
					echo $this->eve->_('common.custominput.filetypes');
					if (!empty($item->spec->file_types))
						echo implode(" ", extensions($item->spec->file_types));
					else
						echo $this->eve->_('common.custominput.filetypes.all');
					if ($item->spec->max_size)
						echo " - ".$this->eve->_('common.custominput.maxfilesize')." {$item->spec->max_size} ".$this->eve->_('common.custominput.megabytes');
				?>
				</div>
				</div>
				<script>
					function fil_input_show_<?php echo $html_name."_".$i;?>()
					{
						$('#fil_info_<?php echo $html_name."_".$i;?>').remove();
						$('#fil_input_<?php echo $html_name."_".$i;?>').show();
						<?php if(!$disabled) { ?> 
							$( "#<?php echo "{$html_name}_{$i}_input";?>" ).prop( "disabled", false );
						<?php }?>
					}
					<?php if(!isset($content) || !isset($content[$i]) || $content[$i] == '') echo "fil_input_show_{$html_name}_{$i}();";?>
				</script>

				<?php
				break;			
		}
	}

	// TODO TO MANTAIN THE CONSISTENCY, STRUCTURE SHOULD BE AN ARRAY OF OBJECTS NOT A SERIALIZED VERSION
	/** $structure is a serialized (text version) json array of objects */
	function custom_input_output_structure_table($name, $structure, $label = null) 
	{
		?>
		<!-- Code automatically generated - begin -->
		<script>
		var str_<?php echo $name;?> = <?php if($structure) echo $structure; else echo "[]";?>;
		
		function add_item_<?php echo $name;?>(item_type)
		{	
			var item_description = prompt("Digite a descrição para o novo item");
			if (item_description != null)
			{
				var item_spec = null;
				switch (item_type)
				{
					case 'text':
						item_spec = null;
						break;
					case 'bigtext':
						item_spec = {min_words:0, max_words:0};
						break;
					case 'array':
						item_spec = {items:[]};
						break;
					case 'enum':
						item_spec = {items:[]};
						break;
					case 'check':
						item_spec = null;
						break;
					case 'file':
						item_spec = {file_types:[], max_size:0};
						break;
				}
				str_<?php echo $name;?>.push
				({
					type:item_type,
					description:item_description,
					unrestrict_view:true,
					mandatory:true,
					spec:item_spec
				});
				update_table_<?php echo $name;?>();
				update_field_<?php echo $name;?>();
			}
		}
		
		function move_item_<?php echo $name;?>(i, offset)
		{
			if (i+offset >= str_<?php echo $name;?>.length || i+offset < 0) return;
			current_item = str_<?php echo $name;?>[i];
			moved_item = str_<?php echo $name;?>[i+offset];
			str_<?php echo $name;?>[i+offset] = current_item;
			str_<?php echo $name;?>[i] = moved_item;
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function delete_item_<?php echo $name;?>(i)
		{
			if(confirm("Você realmente deseja apagar o item na posição: " + (i+1) + "?"))
			{
				str_<?php echo $name;?>.splice(i, 1);
				update_table_<?php echo $name;?>();
				update_field_<?php echo $name;?>();
			}
		}

		function edit_item_<?php echo $name;?>(i)
		{
			switch (str_<?php echo $name;?>[i].type)
			{
				case 'text':
					$('#txt_dlg_<?php echo $name;?>').show();
					$('#txt_des_<?php echo $name;?>').val(str_<?php echo $name;?>[i].description);
					$('#txt_uvi_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].unrestrict_view);
					$('#txt_man_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].mandatory);
					$('#txt_sav_<?php echo $name;?>').off("click");
					$('#txt_sav_<?php echo $name;?>').click(function (){save_text_item_<?php echo $name;?>(i);});
					break;
				case 'bigtext':
					$('#bgt_dlg_<?php echo $name;?>').show();
					$('#bgt_des_<?php echo $name;?>').val(str_<?php echo $name;?>[i].description);
					$('#bgt_min_<?php echo $name;?>').val(str_<?php echo $name;?>[i].spec.min_words);
					$('#bgt_max_<?php echo $name;?>').val(str_<?php echo $name;?>[i].spec.max_words);
					$('#bgt_uvi_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].unrestrict_view);
					$('#bgt_man_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].mandatory);
					$('#bgt_sav_<?php echo $name;?>').off("click");
					$('#bgt_sav_<?php echo $name;?>').click(function (){save_bigtext_item_<?php echo $name;?>(i);});
					break;
				case 'array':
					$('#arr_dlg_<?php echo $name;?>').show();
					$('#arr_des_<?php echo $name;?>').val(str_<?php echo $name;?>[i].description);
					$('#arr_itm_<?php echo $name;?>').val(str_<?php echo $name;?>[i].spec.items.join('\n'));
					$('#arr_uvi_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].unrestrict_view);
					$('#arr_man_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].mandatory);
					$('#arr_sav_<?php echo $name;?>').off("click");
					$('#arr_sav_<?php echo $name;?>').click(function (){save_array_item_<?php echo $name;?>(i);});
					break;
				case 'enum':
					$('#enu_dlg_<?php echo $name;?>').show();
					$('#enu_des_<?php echo $name;?>').val(str_<?php echo $name;?>[i].description);
					$('#enu_itm_<?php echo $name;?>').val(str_<?php echo $name;?>[i].spec.items.join('\n'));
					$('#enu_uvi_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].unrestrict_view);
					$('#enu_man_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].mandatory);
					$('#enu_sav_<?php echo $name;?>').off("click");
					$('#enu_sav_<?php echo $name;?>').click(function (){save_enum_item_<?php echo $name;?>(i);});
					break;
				case 'check':
					$('#chk_dlg_<?php echo $name;?>').show();
					$('#chk_des_<?php echo $name;?>').val(str_<?php echo $name;?>[i].description);
					$('#chk_uvi_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].unrestrict_view);
					$('#chk_man_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].mandatory);
					$('#chk_sav_<?php echo $name;?>').off("click");
					$('#chk_sav_<?php echo $name;?>').click(function (){save_check_item_<?php echo $name;?>(i);});
					break;
				case 'file':
					$('#fil_dlg_<?php echo $name;?>').show();
					$('#fil_des_<?php echo $name;?>').val(str_<?php echo $name;?>[i].description);
					$('#fil_max_<?php echo $name;?>').val(str_<?php echo $name;?>[i].spec.max_size);
					<?php foreach (get_supported_filetypes_and_extensions() as $file_type => $file_extensions)
					{
						?>
						if (str_<?php echo $name;?>[i].spec.file_types.includes('<?php echo $file_type;?>'))
							document.getElementById('fil_typ_<?php echo $file_type.'_'.$name;?>').checked = true;
						else
							document.getElementById('fil_typ_<?php echo $file_type.'_'.$name;?>').checked = false;
						<?php
					}
					?>
					$('#fil_uvi_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].unrestrict_view);
					$('#fil_man_<?php echo $name;?>').prop('checked', str_<?php echo $name;?>[i].mandatory);
					$('#fil_sav_<?php echo $name;?>').off("click");
					$('#fil_sav_<?php echo $name;?>').click(function (){save_file_item_<?php echo $name;?>(i);});
					break;
			}
		}

		function save_text_item_<?php echo $name;?>(i)
		{
			str_<?php echo $name;?>[i].description = document.getElementById('txt_des_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].unrestrict_view = document.getElementById('txt_uvi_<?php echo $name;?>').checked;
			str_<?php echo $name;?>[i].mandatory = document.getElementById('txt_man_<?php echo $name;?>').checked;
			document.getElementById('txt_dlg_<?php echo $name;?>').style.display = 'none';
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function save_bigtext_item_<?php echo $name;?>(i)
		{
			str_<?php echo $name;?>[i].description = document.getElementById('bgt_des_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].spec.min_words = document.getElementById('bgt_min_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].spec.max_words = document.getElementById('bgt_max_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].unrestrict_view = document.getElementById('bgt_uvi_<?php echo $name;?>').checked;
			str_<?php echo $name;?>[i].mandatory = document.getElementById('bgt_man_<?php echo $name;?>').checked;
			document.getElementById('bgt_dlg_<?php echo $name;?>').style.display = 'none';
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function save_array_item_<?php echo $name;?>(i)
		{
			str_<?php echo $name;?>[i].description = document.getElementById('arr_des_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].spec.items = document.getElementById('arr_itm_<?php echo $name;?>').value.split('\n');
			str_<?php echo $name;?>[i].unrestrict_view = document.getElementById('arr_uvi_<?php echo $name;?>').checked;
			str_<?php echo $name;?>[i].mandatory = document.getElementById('arr_man_<?php echo $name;?>').checked;
			document.getElementById('arr_dlg_<?php echo $name;?>').style.display = 'none';
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function save_enum_item_<?php echo $name;?>(i)
		{
			str_<?php echo $name;?>[i].description = document.getElementById('enu_des_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].spec.items = document.getElementById('enu_itm_<?php echo $name;?>').value.split('\n');
			str_<?php echo $name;?>[i].unrestrict_view = document.getElementById('enu_uvi_<?php echo $name;?>').checked;
			str_<?php echo $name;?>[i].mandatory = document.getElementById('enu_man_<?php echo $name;?>').checked;
			document.getElementById('enu_dlg_<?php echo $name;?>').style.display = 'none';
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function save_check_item_<?php echo $name;?>(i)
		{
			str_<?php echo $name;?>[i].description = document.getElementById('chk_des_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].unrestrict_view = document.getElementById('chk_uvi_<?php echo $name;?>').checked;
			str_<?php echo $name;?>[i].mandatory = document.getElementById('chk_man_<?php echo $name;?>').checked;
			document.getElementById('chk_dlg_<?php echo $name;?>').style.display = 'none';
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function save_file_item_<?php echo $name;?>(i)
		{
			str_<?php echo $name;?>[i].description = document.getElementById('fil_des_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].spec.max_size = document.getElementById('fil_max_<?php echo $name;?>').value;
			str_<?php echo $name;?>[i].spec.file_types = new Array();
			<?php foreach (get_supported_filetypes_and_extensions() as $file_type => $file_extensions)
			echo
			("
				if (document.getElementById('fil_typ_{$file_type}_{$name}').checked) str_{$name}[i].spec.file_types.push('{$file_type}');
			");
			?>
			str_<?php echo $name;?>[i].unrestrict_view = document.getElementById('fil_uvi_<?php echo $name;?>').checked;
			str_<?php echo $name;?>[i].mandatory = document.getElementById('fil_man_<?php echo $name;?>').checked;
			document.getElementById('fil_dlg_<?php echo $name;?>').style.display = 'none';
			update_table_<?php echo $name;?>();
			update_field_<?php echo $name;?>();
		}

		function update_field_<?php echo $name;?>()
		{
			document.getElementById('field_<?php echo $name;?>').value = JSON.stringify(str_<?php echo $name;?>);
		}

		function update_table_<?php echo $name;?>()
		{
			var table = document.getElementById("structure_table_<?php echo $name;?>");
			while (table.rows.length > 1) table.deleteRow(-1);
			for (j = 0; j < str_<?php echo $name;?>.length; j++)
			{ 
				var tr = table.insertRow(-1);
				tr.insertCell(-1).innerHTML = j+1;
				tr.insertCell(-1).innerHTML = str_<?php echo $name;?>[j].type;
				tr.insertCell(-1).innerHTML = str_<?php echo $name;?>[j].description;
				tr.insertCell(-1).innerHTML = (str_<?php echo $name;?>[j].unrestrict_view) ? "&#8226;" : "";
				tr.insertCell(-1).innerHTML = (str_<?php echo $name;?>[j].mandatory) ? "&#8226;" : "";
				tr.insertCell(-1).innerHTML = '<button type="button" onclick="move_item_<?php echo $name;?>('+j+', +1)"><img src="<?php echo $this->eve->basepath;?>style/icons/arrow_down.png"></button></td>';
				tr.insertCell(-1).innerHTML = '<button type="button" onclick="move_item_<?php echo $name;?>('+j+', -1)"><img src="<?php echo $this->eve->basepath;?>style/icons/arrow_up.png"></button>';
				tr.insertCell(-1).innerHTML = '<button type="button" onclick="edit_item_<?php echo $name;?>('+j+')"><img src="<?php echo $this->eve->basepath;?>style/icons/edit.png"/></button>';
				tr.insertCell(-1).innerHTML = '<button type="button" onclick="delete_item_<?php echo $name;?>('+j+')"><img src="<?php echo $this->eve->basepath;?>style/icons/delete.png"/></button>';
				tr.childNodes[3].style.textAlign = 'center';
				tr.childNodes[4].style.textAlign = 'center';
			}
		}
		</script>

		<div id="txt_dlg_<?php echo $name;?>" class="modal">
		<div class="user_dialog_panel modal-content">
		<span><input type="checkbox" id="txt_uvi_<?php echo $name;?>"/><label for="txt_uvi_<?php echo $name;?>">Visão irrestrita</label></span>
		<span><input type="checkbox" id="txt_man_<?php echo $name;?>"/><label for="txt_man_<?php echo $name;?>">Obrigatório</label></span>
		<label for="txt_des_<?php echo $name;?>">Descrição</label>
		<input  id="txt_des_<?php echo $name;?>" type="text"/>
		<button type="button" id="txt_sav_<?php echo $name;?>">Atualizar</button>
		<button type="button" onclick="document.getElementById('txt_dlg_<?php echo $name;?>').style.display = 'none';">Cancelar</button>
		</div>
		</div>

		<div id="bgt_dlg_<?php echo $name;?>" class="modal">
		<div class="user_dialog_panel modal-content">
		<span><input type="checkbox" id="bgt_uvi_<?php echo $name;?>"/><label for="bgt_uvi_<?php echo $name;?>">Visão irrestrita</label></span>
		<span><input type="checkbox" id="bgt_man_<?php echo $name;?>"/><label for="bgt_man_<?php echo $name;?>">Obrigatório</label></span>
		<label for="bgt_des_<?php echo $name;?>">Descrição</label>
		<input  id="bgt_des_<?php echo $name;?>" type="text"/>
		<label for="bgt_min_<?php echo $name;?>">Mínimo de palavras</label>
		<input  id="bgt_min_<?php echo $name;?>" type="number" min="0" step="1" value="0"/>
		<label for="bgt_max_<?php echo $name;?>">Máximo de palavras</label>
		<input  id="bgt_max_<?php echo $name;?>" type="number" min="0" step="1" value="0"/>
		<button type="button" id="bgt_sav_<?php echo $name;?>">Atualizar</button>
		<button type="button" onclick="document.getElementById('bgt_dlg_<?php echo $name;?>').style.display = 'none';">Cancelar</button>
		</div>
		</div>

		<div id="arr_dlg_<?php echo $name;?>" class="modal">
		<div class="user_dialog_panel modal-content">
		<span><input type="checkbox" id="arr_uvi_<?php echo $name;?>"/><label for="arr_uvi_<?php echo $name;?>">Visão irrestrita</label></span>
		<span><input type="checkbox" id="arr_man_<?php echo $name;?>"/><label for="arr_man_<?php echo $name;?>">Obrigatório</label></span>
		<label for="arr_des_<?php echo $name;?>">Descrição</label>
		<input  id="arr_des_<?php echo $name;?>" type="text"/>
		<label for="arr_itm_<?php echo $name;?>">Ítens <small>(um por linha)</small></label>
		<textarea id="arr_itm_<?php echo $name;?>" rows="3"></textarea>
		<button type="button" id="arr_sav_<?php echo $name;?>">Atualizar</button>
		<button type="button" onclick="document.getElementById('arr_dlg_<?php echo $name;?>').style.display = 'none';">Cancelar</button>
		</div>
		</div>

		<div id="enu_dlg_<?php echo $name;?>" class="modal">
		<div class="user_dialog_panel modal-content">
		<span><input type="checkbox" id="enu_uvi_<?php echo $name;?>"/><label for="enu_uvi_<?php echo $name;?>">Visão irrestrita</label></span>
		<span><input type="checkbox" id="enu_man_<?php echo $name;?>"/><label for="enu_man_<?php echo $name;?>">Obrigatório</label></span>
		<label for="enu_des_<?php echo $name;?>">Descrição</label>
		<input  id="enu_des_<?php echo $name;?>" type="text"/>
		<label for="enu_itm_<?php echo $name;?>">Ítens <small>(um por linha)</small></label>
		<textarea id="enu_itm_<?php echo $name;?>" rows="3"></textarea>
		<button type="button" id="enu_sav_<?php echo $name;?>">Atualizar</button>
		<button type="button" onclick="document.getElementById('enu_dlg_<?php echo $name;?>').style.display = 'none';">Cancelar</button>
		</div>
		</div>

		<div id="chk_dlg_<?php echo $name;?>" class="modal">
		<div class="user_dialog_panel modal-content">
		<span><input type="checkbox" id="chk_uvi_<?php echo $name;?>"/><label for="chk_uvi_<?php echo $name;?>">Visão irrestrita</label></span>
		<span><input type="checkbox" id="chk_man_<?php echo $name;?>"/><label for="chk_man_<?php echo $name;?>">Obrigatório</label></span>
		<label for="chk_des_<?php echo $name;?>">Descrição</label>
		<input  id="chk_des_<?php echo $name;?>" type="text"/>
		<button type="button" id="chk_sav_<?php echo $name;?>">Atualizar</button>
		<button type="button" onclick="document.getElementById('chk_dlg_<?php echo $name;?>').style.display = 'none';">Cancelar</button>
		</div>
		</div>
		
		<div id="fil_dlg_<?php echo $name;?>" class="modal">
		<div class="user_dialog_panel modal-content">
		<span><input type="checkbox" id="fil_uvi_<?php echo $name;?>"/><label for="fil_uvi_<?php echo $name;?>">Visão irrestrita</label></span>
		<span><input type="checkbox" id="fil_man_<?php echo $name;?>"/><label for="fil_man_<?php echo $name;?>">Obrigatório</label></span>
		<label for="fil_des_<?php echo $name;?>">Descrição</label>
		<input  id="fil_des_<?php echo $name;?>" type="text"/>
		<label for="fil_max_<?php echo $name;?>">Tamanho máximo (MB)</label>
		<input  id="fil_max_<?php echo $name;?>" type="number" min="0" step="0.1" value="0"/>
		<label>Tipos de arquivos</label>
		<?php 
			foreach (get_supported_filetypes_and_extensions() as $file_type => $file_extensions)
			{
				echo "<span>";
				echo "<input  id=\"fil_typ_{$file_type}_{$name}\" type=\"checkbox\" />";
				echo "<label for=\"fil_typ_{$file_type}_{$name}\">{$file_extensions[0]}</label>";
				echo "</span>";
			}
		?>
		<button type="button" id="fil_sav_<?php echo $name;?>">Atualizar</button>
		<button type="button" onclick="document.getElementById('fil_dlg_<?php echo $name;?>').style.display = 'none';">Cancelar</button>
		</div>
		</div>
		
		<div class="section">
			<?php echo $label;?>
			&nbsp;&nbsp;
			<button type="button" onclick="add_item_<?php echo $name;?>('text');">+ Text</button>
			<button type="button" onclick="add_item_<?php echo $name;?>('bigtext');">+ Big Text</button>
			<button type="button" onclick="add_item_<?php echo $name;?>('array');">+ Array</button>
			<button type="button" onclick="add_item_<?php echo $name;?>('enum');">+ Enum</button>
			<button type="button" onclick="add_item_<?php echo $name;?>('check');">+ Check</button>
			<button type="button" onclick="add_item_<?php echo $name;?>('file');">+ File</button>
		</div>			
		<table class="data_table" id="structure_table_<?php echo $name;?>">
			<thead><th>Posição</th><th>Tipo</th><th>Descrição</th><th>Visão irrestrita</th><th>Obrigatório</th><th colspan="4"><?php echo $this->eve->_('common.table.header.options');?></th></thead>
			<tbody></tbody>
		</table>
		<input type="hidden" name="<?php echo $name;?>" id="field_<?php echo $name;?>"/>
		<script>update_table_<?php echo $name;?>();update_field_<?php echo $name;?>();</script>		
		<!-- Code automatically generated - end -->
		<?php
	}

	/** 
	 * Validate custom input and move files to server if validation is successful.
	 * The array $content will be modified if a file get successfully uploaded to server.
	 */
	function custom_input_validate($structure, &$content, $files, $upload_directory)
	{
		// TODO: Check if it is null
		if (substr($upload_directory, -1) != "/") $upload_directory .= "/";

		$validation_errors = array();
		foreach($structure as $i => $item)
		{
			switch($item->type)
			{
				case 'bigtext':
					if ($item->mandatory && trim($content[$i]) == "")
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_MANDATORY;
					if ($item->spec->min_words && preg_match_all("/\S+/", $content[$i]) < $item->spec->min_words)
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_UNDER_MIN_WORDS;
					if ($item->spec->max_words && preg_match_all("/\S+/", $content[$i]) > $item->spec->max_words)
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_OVER_MAX_WORDS;
					break;
				case 'text':
				case 'enum':
					if ($item->mandatory && trim($content[$i]) == "")
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_MANDATORY;
					break;									
				case 'array':
					$some_array_items_are_empty = 0;
					foreach ($content[$i] as $arr_item) if (trim($arr_item) == "") $some_array_items_are_empty = true;
					if ($item->mandatory && $some_array_items_are_empty)
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_MANDATORY;
					break;
				case 'file':
					// A file has already been uploaded, so it's assumed it has already been validated
					if (isset($content[$i])) break;
					
					$file_error = false;
					// Validation - No file sent on mandatory fields (Error code 4 = no file specified)
					if ($item->mandatory && $files['error'][$i] == 4)
					{
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_MANDATORY;
						$file_error = true;
					}
					// Validation - Other server errors (Error code 0 = no error / Error code 4 = no file specified, dealed with above)
					if ($files['error'][$i] != 0 && $files['error'][$i] != 4) 
					{
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_FILE_ERROR;
						$file_error = true;
					}
					// Validation - Maximum file size (1 megabyte = 1048576 bytes)
					if ($files['error'][$i] == 0 && $item->spec->max_size && $files['size'][$i] > $item->spec->max_size * 1048576)
					{
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_FILE_EXCEEDED_SIZE;
						$file_error = true;
					}
					// Validation - File type				
					if ($files['error'][$i] == 0 && !empty($item->spec->file_types) && !in_array($files['type'][$i], $item->spec->file_types))
					{
						$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_FILE_WRONG_TYPE;					
						$file_error = true;
					}
					if ($files['error'][$i] == 0 && $file_error == false)
					{
						$random_filename = md5(uniqid(rand(), true)); // Generating a random filename
						$extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
						if (move_uploaded_file($files['tmp_name'][$i], "$upload_directory$random_filename.$extension"))
							$content[$i] = "$upload_directory$random_filename.$extension";
						else
							$validation_errors[$i] = self::CUSTOM_INPUT_VALIDATION_ERROR_FILE_UPLOAD_ERROR;
						break;
					}
					break;
			}
		}
		return $validation_errors;
	}

	/** @deprecated */
	function __construct(Eve $eve)
	{
		$this->eve = $eve;
	}
}