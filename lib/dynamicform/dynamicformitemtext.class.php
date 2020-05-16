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
        return 'tex_edt';
    }

    public static function outputAddEditControls($name) 
    {
        $result = "
        <script>
        function tex_add{$name}()
		{	
			var item_description = prompt('".DynamicFormHelper::_('structure.table.message.add')."');
			if (item_description != null)
			{
				str_{$name}.push
				({
					type:'".self::getType()."',
					description:item_description,
					customattribute:'',
					mandatory:true,
					spec:null
				});
				update_table_{$name}();
				update_field_{$name}();
			}
        }
        function tex_edt_{$name}(i)
        {
            document.getElementById('tex_dlg_{$name}').style.display = 'block';
            document.getElementById('tex_des_{$name}').value = str_{$name}[i].description;
            document.getElementById('tex_cat_{$name}').value = str_{$name}[i].customattribute;
            document.getElementById('tex_man_{$name}').checked = str_{$name}[i].mandatory;
            document.getElementById('tex_sav_{$name}').onclick = function(){tex_sav_{$name}(i);};
        }
        function tex_sav_{$name}(i)
        {
            str_{$name}[i].description = document.getElementById('tex_des_{$name}').value;
            str_{$name}[i].customattribute = document.getElementById('tex_cat_{$name}').value;
			str_{$name}[i].mandatory = document.getElementById('tex_man_{$name}').checked;
			document.getElementById('tex_dlg_{$name}').style.display = 'none';
			update_table_{$name}();
			update_field_{$name}();
        }
        </script>

        <div id=\"tex_dlg_{$name}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<label for=\"tex_des_{$name}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"tex_des_{$name}\" type=\"text\"/>
        <label for=\"tex_cat_{$name}\">".DynamicFormHelper::_('item.customattribute')."</label>
        <input  id=\"tex_cat_{$name}\" type=\"text\"/>
        <label for=\"tex_man_{$name}\">
        <input  id=\"tex_man_{$name}\" type=\"checkbox\"/>".DynamicFormHelper::_('item.mandatory')."</label>
		<button type=\"button\" id=\"tex_sav_{$name}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('tex_dlg_{$name}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"tex_add{$name}();\">";
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