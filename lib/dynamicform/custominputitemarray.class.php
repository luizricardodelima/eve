<?php
require_once "custominputitem.class.php";

class CustomInputItemArray extends CustomInputItem
{

    public static function getType()
    {
        return 'array'; 
    }

    public static function javascriptEditMethod()
    {
        return 'edt_arr';
    }

    public static function outputCustomInputStructureAddButton($html_id) 
    {
        $result = "
        <script>
        function add_arr_{$html_id}()
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
					spec:{items:[]}
				});
				update_table_{$html_id}();
				update_field_{$html_id}();
			}
        }
        function edt_arr_{$html_id}(i)
        {
            document.getElementById('arr_dlg_{$html_id}').style.display = 'block';
            document.getElementById('arr_des_{$html_id}').value = str_{$html_id}[i].description;
            document.getElementById('arr_itm_{$html_id}').value = str_{$html_id}[i].spec.items.join('\\n');
            document.getElementById('arr_unr_{$html_id}').checked = str_{$html_id}[i].unrestrict;
            document.getElementById('arr_man_{$html_id}').checked = str_{$html_id}[i].mandatory;
            document.getElementById('arr_sav_{$html_id}').onclick = function(){sav_arr_{$html_id}(i);};
        }
        function sav_arr_{$html_id}(i)
        {
            str_{$html_id}[i].description = document.getElementById('arr_des_{$html_id}').value;
            str_{$html_id}[i].spec.items = document.getElementById('arr_itm_{$html_id}').value.split('\\n');
			str_{$html_id}[i].unrestrict = document.getElementById('arr_unr_{$html_id}').checked;
			str_{$html_id}[i].mandatory = document.getElementById('arr_man_{$html_id}').checked;
			document.getElementById('arr_dlg_{$html_id}').style.display = 'none';
			update_table_{$html_id}();
			update_field_{$html_id}();
        }
        </script>

        <div id=\"arr_dlg_{$html_id}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<span><input type=\"checkbox\" id=\"arr_unr_{$html_id}\"/><label for=\"arr_unr_{$html_id}\">Visão irrestrita</label></span>
		<span><input type=\"checkbox\" id=\"arr_man_{$html_id}\"/><label for=\"arr_man_{$html_id}\">Obrigatório</label></span>
		<label for=\"arr_des_{$html_id}\">Descrição</label>
        <input  id=\"arr_des_{$html_id}\" type=\"text\"/>
        <label for=\"arr_itm_{$html_id}\">Ítens <small>(um por linha)</small></label>
		<textarea id=\"arr_itm_{$html_id}\" rows=\"3\"></textarea>
		<button type=\"button\" id=\"arr_sav_{$html_id}\">Atualizar</button>
		<button type=\"button\" onclick=\"document.getElementById('arr_dlg_{$html_id}').style.display = 'none';\">Cancelar</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"add_arr_{$html_id}();\">";
        $result .= "+ Array";
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        
        $result = "
        <label>{$this->description}";
        if ($this->mandatory) $result .= "&nbsp;<small>(Obrigatório)</small>";
        $result .= "</label>
        <div style=\"display: grid; grid-template-columns:";
        for ($k = 0; $k < sizeof($this->spec->items); $k++) $result .= " 2fr 5fr";
        $result .= ";\">";
        foreach ($this->spec->items as $j => $arrayItem)
		{
            $result .= "
            <label for=\"{$htmlName}[{$index}][{$j}]\" style=\"text-align: right;\">$arrayItem:&nbsp;</label>
            <input type=\"text\" name=\"{$htmlName}[{$index}][{$j}]\" id=\"{$htmlName}[{$index}][{$j}]\"";
            $result .= " value=\"".htmlentities($this->content[$j], ENT_QUOTES, 'utf-8')."\"";
            $result .= ($active) ? "" : " disabled=\"disabled\"";
            $result .= "/>";
		}
        $result .= "</div>";
        return $result;
    }

    function __construct($object, $content)
	{
        parent::__construct($object, $content);
        $this->type = self::getType();
        if (is_null ($content)) {
            $this->content = array();
            foreach ($this->spec->items as $item)
                $this->content[] = '';
        }
        else {
            $this->content = $content;
        }
	}
}

?>