<?php
require_once "custominputhelper.class.php";
require_once "custominputitem.class.php";

class CustomInputItemFile extends CustomInputItem
{

    public static function getType()
    {
        return 'file'; 
    }

    public static function javascriptEditMethod()
    {
        return 'edt_fil';
    }

    public static function outputCustomInputStructureAddButton($html_id) 
    {
        $result = "
        <script>
        function add_fil_{$html_id}()
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
					spec:{file_types:[], max_size:0}
				});
				update_table_{$html_id}();
				update_field_{$html_id}();
			}
        }
        function edt_fil_{$html_id}(i)
        {
        ";
        $result .= "
            var filetypes = " . json_encode(array_keys(CustomInputHelper::supportedFiletypes())) . ";";
        $result .= "
            document.getElementById('fil_dlg_{$html_id}').style.display = 'block';
            document.getElementById('fil_des_{$html_id}').value = str_{$html_id}[i].description;
            document.getElementById('fil_max_{$html_id}').value = str_{$html_id}[i].spec.max_size;
            filetypes.forEach(function(element)
            {
                if (str_{$html_id}[i].spec.file_types.includes(element))
                    document.getElementById('fil_typ_'+ element +'_{$html_id}').checked = true;
                else
                    document.getElementById('fil_typ_'+ element +'_{$html_id}').checked = false;
            });
            document.getElementById('fil_unr_{$html_id}').checked = str_{$html_id}[i].unrestrict;
            document.getElementById('fil_man_{$html_id}').checked = str_{$html_id}[i].mandatory;
            document.getElementById('fil_sav_{$html_id}').onclick = function(){sav_fil_{$html_id}(i);};
        }
        function sav_fil_{$html_id}(i)
        {
        ";
        $result .= "
            var filetypes = " . json_encode(array_keys(CustomInputHelper::supportedFiletypes())) . ";";
        $result .= "
            str_{$html_id}[i].description = document.getElementById('fil_des_{$html_id}').value;
            str_{$html_id}[i].spec.max_size = document.getElementById('fil_max_{$html_id}').value;
            str_{$html_id}[i].spec.file_types = new Array();
            filetypes.forEach(function(element)
            {
                if (document.getElementById('fil_typ_'+ element +'_{$html_id}').checked)
                    str_{$html_id}[i].spec.file_types.push(element);
            });
			str_{$html_id}[i].unrestrict = document.getElementById('fil_unr_{$html_id}').checked;
			str_{$html_id}[i].mandatory = document.getElementById('fil_man_{$html_id}').checked;
			document.getElementById('fil_dlg_{$html_id}').style.display = 'none';
			update_table_{$html_id}();
			update_field_{$html_id}();
        }
        </script>

        <div id=\"fil_dlg_{$html_id}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<span><input type=\"checkbox\" id=\"fil_unr_{$html_id}\"/><label for=\"fil_unr_{$html_id}\">Visão irrestrita</label></span>
		<span><input type=\"checkbox\" id=\"fil_man_{$html_id}\"/><label for=\"fil_man_{$html_id}\">Obrigatório</label></span>
		<label for=\"fil_des_{$html_id}\">Descrição</label>
        <input  id=\"fil_des_{$html_id}\" type=\"text\"/>
        <label for=\"fil_max_{$html_id}\">Tamanho máximo (MB)</label>
		<input  id=\"fil_max_{$html_id}\" type=\"number\" min=\"0\" step=\"0.1\" value=\"0\"/>
		<label>Tipos de arquivos</label>";
		foreach (CustomInputHelper::supportedFiletypes() as $file_type => $file_extensions)
		{
            $result .= "
            <span>
            <input  id=\"fil_typ_{$file_type}_{$html_id}\" type=\"checkbox\" />
            <label for=\"fil_typ_{$file_type}_{$html_id}\">{$file_extensions[0]}</label>
            </span>";
		}
        $result .= "
        <button type=\"button\" id=\"fil_sav_{$html_id}\">Atualizar</button>
		<button type=\"button\" onclick=\"document.getElementById('fil_dlg_{$html_id}').style.display = 'none';\">Cancelar</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"add_fil_{$html_id}();\">";
        $result .= "+ File";
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        if ($this->mandatory) $result .= "&nbsp;<small>(Obrigatório)</small>";
        $result .= "</label>";
        
        if ($this->content != '') $result.= "
        <div id=\"fil_info_{$htmlName}_{$index}\">
        <a href=\"{$this->content}\">{$this->content}</a>";
        if ($this->content != '' && $active) $result.= "
        <button type=\"button\" style=\"float: right;\" onclick=\"fil_input_show_{$htmlName}_{$index}()\">Alterar</button>";
        if ($this->content != '') $result.= "
        <input type=\"hidden\" name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\" value=\"{$this->content}\"/>
        </div>
        ";

        $result .= "
        <div id=\"fil_input_{$htmlName}_{$index}\" style=\"display: none;\">
        <input type=\"file\" name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}_{$index}_input\" disabled=\"disabled\"/>
        <div style=\"font-size: 0.8rem\">Tipos de arquivos:";
        if (!empty($this->spec->file_types))
            $result .= implode(" ", CustomInputHelper::extensions($this->spec->file_types));
        else
            $result .= " Todos ";            
        if ($this->spec->max_size)
            $result .=  " - Tamanho máximo: {$this->spec->max_size} MB ";
        $result .= "</div>
        </div>
        <script>
            function fil_input_show_{$htmlName}_{$index}()
            {
                if (document.getElementById('fil_info_{$htmlName}_{$index}') != null) document.getElementById('fil_info_{$htmlName}_{$index}').remove();
                document.getElementById('fil_input_{$htmlName}_{$index}').style.display = 'initial';";
        if ($active) $result .= " 
                document.getElementById('{$htmlName}_{$index}_input').disabled = false;";
        $result .= "
            }";
        if ($this->content == '') $result.= "
            fil_input_show_{$htmlName}_{$index}();";
        $result .= "
        </script>";
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