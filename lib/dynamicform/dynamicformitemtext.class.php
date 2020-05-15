<?php
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemText extends DynamicFormItem
{

    public function getHtmlFormattedContent()
    {
        return htmlentities($this->content, ENT_QUOTES, 'utf-8');
    }

    public static function getType()
    {
        return 'text'; 
    }

    public static function javascriptEditMethod()
    {
        return 'edt_tex';
    }

    public static function outputAddEditControls($html_id) 
    {
        $result = "
        <script>
        function add_tex{$html_id}()
		{	
			var item_description = prompt('".DynamicFormHelper::_('structure.table.message.add')."');
			if (item_description != null)
			{
				str_{$html_id}.push
				({
					type:'".self::getType()."',
					description:item_description,
					unrestrict:true,
					mandatory:true,
					spec:null
				});
				update_table_{$html_id}();
				update_field_{$html_id}();
			}
        }
        function edt_tex_{$html_id}(i)
        {
            document.getElementById('tex_dlg_{$html_id}').style.display = 'block';
            document.getElementById('tex_des_{$html_id}').value = str_{$html_id}[i].description;
            document.getElementById('tex_unr_{$html_id}').checked = str_{$html_id}[i].unrestrict;
            document.getElementById('tex_man_{$html_id}').checked = str_{$html_id}[i].mandatory;
            document.getElementById('tex_sav_{$html_id}').onclick = function(){sav_tex_{$html_id}(i);};
        }
        function sav_tex_{$html_id}(i)
        {
			str_{$html_id}[i].description = document.getElementById('tex_des_{$html_id}').value;
			str_{$html_id}[i].unrestrict = document.getElementById('tex_unr_{$html_id}').checked;
			str_{$html_id}[i].mandatory = document.getElementById('tex_man_{$html_id}').checked;
			document.getElementById('tex_dlg_{$html_id}').style.display = 'none';
			update_table_{$html_id}();
			update_field_{$html_id}();
        }
        </script>

        <div id=\"tex_dlg_{$html_id}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<span><input type=\"checkbox\" id=\"tex_unr_{$html_id}\"/><label for=\"tex_unr_{$html_id}\">".DynamicFormHelper::_('item.unrestrict')."</label></span>
		<span><input type=\"checkbox\" id=\"tex_man_{$html_id}\"/><label for=\"tex_man_{$html_id}\">".DynamicFormHelper::_('item.mandatory')."</label></span>
		<label for=\"tex_des_{$html_id}\">".DynamicFormHelper::_('item.description')."</label>
		<input  id=\"tex_des_{$html_id}\" type=\"text\"/>
		<button type=\"button\" id=\"tex_sav_{$html_id}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('tex_dlg_{$html_id}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"add_tex{$html_id}();\">";
        $result .= DynamicFormHelper::_('structure.table.button.add.text');
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        if ($this->mandatory) $result .= "<small>".DynamicFormHelper::_('control.restriction.start').DynamicFormHelper::_('control.restriction.mandatory').DynamicFormHelper::_('control.restriction.end')."</small>";
        $result .= "</label>
        <input type=\"text\" name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\"";
        $result .= "value=\"".htmlentities($this->content, ENT_QUOTES, 'utf-8')."\"";
        $result .= ($active) ? "" : " disabled=\"disabled\"";
        $result .= " />";
        return $result;
    }

    public function validate()
    {
        $validationErrors = array();
        if ($this->mandatory && $this->content == '')
            $validationErrors[] = DynamicFormValidationError::MANDATORY;
        return $validationErrors;
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