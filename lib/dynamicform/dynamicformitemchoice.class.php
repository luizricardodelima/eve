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
        return 'edt_cho';
    }

    public static function outputAddEditControls($html_id) 
    {
        $result = "
        <script>
        function add_cho{$html_id}()
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
        function edt_cho_{$html_id}(i)
        {
            document.getElementById('cho_dlg_{$html_id}').style.display = 'block';
            document.getElementById('cho_des_{$html_id}').value = str_{$html_id}[i].description;
            document.getElementById('cho_unr_{$html_id}').checked = str_{$html_id}[i].unrestrict;
            document.getElementById('cho_man_{$html_id}').checked = str_{$html_id}[i].mandatory;
            document.getElementById('cho_sav_{$html_id}').onclick = function(){sav_cho_{$html_id}(i);};
        }
        function sav_cho_{$html_id}(i)
        {
			str_{$html_id}[i].description = document.getElementById('cho_des_{$html_id}').value;
			str_{$html_id}[i].unrestrict = document.getElementById('cho_unr_{$html_id}').checked;
			str_{$html_id}[i].mandatory = document.getElementById('cho_man_{$html_id}').checked;
			document.getElementById('cho_dlg_{$html_id}').style.display = 'none';
			update_table_{$html_id}();
			update_field_{$html_id}();
        }
        </script>

        <div id=\"cho_dlg_{$html_id}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<span><input type=\"checkbox\" id=\"cho_unr_{$html_id}\"/><label for=\"cho_unr_{$html_id}\">".DynamicFormHelper::_('item.unrestrict')."</label></span>
		<span><input type=\"checkbox\" id=\"cho_man_{$html_id}\"/><label for=\"cho_man_{$html_id}\">".DynamicFormHelper::_('item.mandatory')."</label></span>
		<label for=\"cho_des_{$html_id}\">".DynamicFormHelper::_('item.description')."</label>
		<input  id=\"cho_des_{$html_id}\" type=\"text\"/>
		<button type=\"button\" id=\"cho_sav_{$html_id}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('cho_dlg_{$html_id}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"add_cho{$html_id}();\">";
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