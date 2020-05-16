<?php
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemChoice extends DynamicFormItem
{
    public function getHtmlFormattedContent()
    {
        return ($this->content) ? DynamicFormHelper::_('item.choice.yes') : DynamicFormHelper::_('item.choice.no');
    }

    public static function getType()
    {
        return 'choice'; 
    }

    public static function javascriptEditMethod()
    {
        return 'cho_edt';
    }

    public static function outputAddEditControls($name) 
    {
        $result = "
        <script>
        function cho_add{$name}()
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
        function cho_edt_{$name}(i)
        {
            document.getElementById('cho_dlg_{$name}').style.display = 'block';
            document.getElementById('cho_des_{$name}').value = str_{$name}[i].description;
            document.getElementById('cho_cat_{$name}').value = str_{$name}[i].customattribute;
            document.getElementById('cho_man_{$name}').checked = str_{$name}[i].mandatory;
            document.getElementById('cho_sav_{$name}').onclick = function(){cho_sav_{$name}(i);};
        }
        function cho_sav_{$name}(i)
        {
            str_{$name}[i].description = document.getElementById('cho_des_{$name}').value;
            str_{$name}[i].customattribute = document.getElementById('cho_cat_{$name}').value;
			str_{$name}[i].mandatory = document.getElementById('cho_man_{$name}').checked;
			document.getElementById('cho_dlg_{$name}').style.display = 'none';
			update_table_{$name}();
			update_field_{$name}();
        }
        </script>

        <div id=\"cho_dlg_{$name}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<label for=\"cho_des_{$name}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"cho_des_{$name}\" type=\"text\"/>
        <label for=\"cho_cat_{$name}\">".DynamicFormHelper::_('item.customattribute')."</label>
        <input  id=\"cho_cat_{$name}\" type=\"text\"/>
        <label for=\"cho_man_{$name}\">
        <input  id=\"cho_man_{$name}\" type=\"checkbox\"/>".DynamicFormHelper::_('item.mandatory')."</label>
        <button type=\"button\" id=\"cho_sav_{$name}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('cho_dlg_{$name}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"cho_add{$name}();\">";
        $result .= DynamicFormHelper::_('structure.table.button.add.choice');
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <span>
        <input type=\"hidden\" value=\"0\" name=\"{$htmlName}[{$index}]\"/>
        <input type=\"checkbox\" value=\"1\" name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\"";
        $result .= ($active) ? "" : " disabled=\"disabled\"";
        $result .= ($this->content) ? " checked=\"checked\"" : "";
        $result .= "/>
        <label for=\"{$htmlName}[{$index}]\">{$this->description}</label>
		</span>";
        return $result;
    }

    public function validate()
    {
        $validationErrors = array();
        // No validations so far for this item
        return $validationErrors;
    }

    function __construct($object, $content)
	{
        parent::__construct($object, $content);
        $this->type = self::getType();
        if (is_null ($content)) {
            $this->content =  0;
        }
        else {
            $this->content = intval($content);
        }
	}

}

?>