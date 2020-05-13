<?php
require_once "custominputitem.class.php";

class CustomInputItemBigtext extends CustomInputItem
{

    public static function getType()
    {
        return 'bigtext'; 
    }

    public static function javascriptEditMethod()
    {
        return 'edt_bgt';
    }

    public static function outputCustomInputStructureAddButton($html_id) 
    {
        $result = "
        <script>
        function add_bgt_{$html_id}()
		{	
			var item_description = prompt('Digite a descrição para o novo item');
			if (item_description != null)
			{
				str_{$html_id}.push
				({
					type:'".self::getType()."',
					description:item_description,
					unrestrict:true,
					mandatory:true,
					spec:{min_words:0, max_words:0}
				});
				update_table_{$html_id}();
				update_field_{$html_id}();
			}
        }
        function edt_bgt_{$html_id}(i)
        {
            document.getElementById('bgt_dlg_{$html_id}').style.display = 'block';
            document.getElementById('bgt_des_{$html_id}').value = str_{$html_id}[i].description;
            document.getElementById('bgt_min_{$html_id}').value = str_{$html_id}[i].spec.min_words;
            document.getElementById('bgt_max_{$html_id}').value = str_{$html_id}[i].spec.max_words;
            document.getElementById('bgt_uvi_{$html_id}').checked = str_{$html_id}[i].unrestrict;
            document.getElementById('bgt_man_{$html_id}').checked = str_{$html_id}[i].mandatory;
            document.getElementById('bgt_sav_{$html_id}').onclick = function(){sav_bgt_{$html_id}(i);};
        }
        function sav_bgt_{$html_id}(i)
        {
			str_{$html_id}[i].description = document.getElementById('bgt_des_{$html_id}').value;
			str_{$html_id}[i].spec.min_words = document.getElementById('bgt_min_{$html_id}').value;
			str_{$html_id}[i].spec.max_words = document.getElementById('bgt_max_{$html_id}').value;
			str_{$html_id}[i].unrestrict = document.getElementById('bgt_uvi_{$html_id}').checked;
			str_{$html_id}[i].mandatory = document.getElementById('bgt_man_{$html_id}').checked;
			document.getElementById('bgt_dlg_{$html_id}').style.display = 'none';
			update_table_{$html_id}();
			update_field_{$html_id}();
        }
        </script>

        <div id=\"bgt_dlg_{$html_id}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<span><input type=\"checkbox\" id=\"bgt_uvi_{$html_id}\"/><label for=\"bgt_uvi_{$html_id}\">Visão irrestrita</label></span>
		<span><input type=\"checkbox\" id=\"bgt_man_{$html_id}\"/><label for=\"bgt_man_{$html_id}\">Obrigatório</label></span>
		<label for=\"bgt_des_{$html_id}\">Descrição</label>
		<input  id=\"bgt_des_{$html_id}\" type=\"text\"/>
		<label for=\"bgt_min_{$html_id}\">Mínimo de palavras</label>
		<input  id=\"bgt_min_{$html_id}\" type=\"number\" min=\"0\" step=\"1\" value=\"0\"/>
		<label for=\"bgt_max_{$html_id}\">Máximo de palavras</label>
		<input  id=\"bgt_max_{$html_id}\" type=\"number\" min=\"0\" step=\"1\" value=\"0\"/>
		<button type=\"button\" id=\"bgt_sav_{$html_id}\">Atualizar</button>
		<button type=\"button\" onclick=\"document.getElementById('bgt_dlg_{$html_id}').style.display = 'none';\">Cancelar</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"add_bgt_{$html_id}();\">";
        $result .= "+ Big Text";
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        if ($this->mandatory) $result .= "&nbsp;<small>(Obrigatório)</small>";
        $result .= "</label>
        <div>
        <textarea name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\" style=\"width: 100%;\" rows=\"15\"";
        $result .= ($this->spec->min_words || $this->spec->max_words) ? " onkeyup=\"bgt_check_{$htmlName}_{$index}()\"" : "";
        $result .= ($active) ? "" : " disabled=\"disabled\"";
        $result .= ">";
        $result .= htmlentities($this->content, ENT_QUOTES, 'utf-8');
        $result .= "</textarea>";

        if ($this->spec->min_words || $this->spec->max_words) $result .= "
        <div id=\"bgt_inf_{$htmlName}_{$index}\" style=\"font-size: 0.8rem\">
        <span id=\"bgt_war_{$htmlName}_{$index}\">&#9940;</span>
        Total de palavras: 
        <label id=\"bgt_cnt_{$htmlName}_{$index}\">0</label> 
        Mínimo: {$this->spec->min_words}
        &nbsp;-&nbsp;
        Máximo: {$this->spec->max_words}
        </div>
        </div>
        <script>
        function bgt_check_{$htmlName}_{$index}()
        {
            // Counting words regex. It will count all groups of non-whitespace chars
            var words = document.getElementById('{$htmlName}[{$index}]').value.match(/\S+/g);
            // Function max can return null if obj.value is an empty string
            if (words == null) words = [];
            document.getElementById('bgt_cnt_{$htmlName}_{$index}').innerHTML = words.length;
            if(words.length < {$this->spec->min_words} || words.length > {$this->spec->max_words})
            {
                document.getElementById('bgt_war_{$htmlName}_{$index}').style.display = 'initial';
                document.getElementById('bgt_inf_{$htmlName}_{$index}').style.fontWeight = 'bold';
            }
            else
            {
                document.getElementById('bgt_war_{$htmlName}_{$index}').style.display = 'none';
                document.getElementById('bgt_inf_{$htmlName}_{$index}').style.fontWeight = 'normal';
            }
        }
        bgt_check_{$htmlName}_{$index}();
        </script>
        ";
        return $result;
    }

    function __construct($object, $content)
	{
        parent::__construct($object, $content);
        $this->type = self::getType();
        if (is_null ($content)) {
            $this->content =  '';
        }
        else {
            $this->content = $content;
        }
	}
}

?>