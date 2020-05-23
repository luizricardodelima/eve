<?php
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemSingleChoice extends DynamicFormItem
{
    public function getFormattedContent()
    {
        return ($this->content === '') ? '' : $this->spec->items[$this->content];
    }

    public function getHtmlFormattedContent()
    {
        return $this->getFormattedContent();
    }

    public static function getType()
    {
        return 'singlechoice'; 
    }

    public static function javascriptEditMethod()
    {
        return 'sch_edt';
    }

    public static function outputAddEditControls($name) 
    {
        $result = "
        <script>
        function sch_add_{$name}()
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
					spec:{items:[]}
				});
				update_table_{$name}();
				update_field_{$name}();
			}
        }
        function sch_edt_{$name}(i)
        {
            document.getElementById('sch_dlg_{$name}').style.display = 'block';
            document.getElementById('sch_des_{$name}').value = str_{$name}[i].description;
            document.getElementById('sch_cat_{$name}').value = str_{$name}[i].customattribute;
            document.getElementById('sch_itm_{$name}').value = str_{$name}[i].spec.items.join('\\n');
            document.getElementById('sch_man_{$name}').checked = str_{$name}[i].mandatory;
            document.getElementById('sch_sav_{$name}').onclick = function(){sch_sav_{$name}(i);};
        }
        function sch_sav_{$name}(i)
        {
            str_{$name}[i].description = document.getElementById('sch_des_{$name}').value;
            str_{$name}[i].customattribute = document.getElementById('sch_cat_{$name}').value;
            str_{$name}[i].spec.items = document.getElementById('sch_itm_{$name}').value.split('\\n');
			str_{$name}[i].mandatory = document.getElementById('sch_man_{$name}').checked;
			document.getElementById('sch_dlg_{$name}').style.display = 'none';
			update_table_{$name}();
			update_field_{$name}();
        }
        </script>

        <div id=\"sch_dlg_{$name}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<label for=\"sch_des_{$name}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"sch_des_{$name}\" type=\"text\"/>
        <label for=\"sch_cat_{$name}\">".DynamicFormHelper::_('item.customattribute')."</label>
        <input  id=\"sch_cat_{$name}\" type=\"text\"/>
        <label for=\"sch_man_{$name}\">
        <input  id=\"sch_man_{$name}\" type=\"checkbox\"/>".DynamicFormHelper::_('item.mandatory')."</label>
        <label for=\"sch_itm_{$name}\">".DynamicFormHelper::_('item.singlechoice.spec.items')." <small>".DynamicFormHelper::_('item.singlechoice.spec.items.help')."</small></label>
		<textarea id=\"sch_itm_{$name}\" rows=\"3\"></textarea>
		<button type=\"button\" id=\"sch_sav_{$name}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('sch_dlg_{$name}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"sch_add_{$name}();\">";
        $result .= DynamicFormHelper::_('structure.table.button.add.singlechoice');
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        if ($this->mandatory) $result .= "<small>".DynamicFormHelper::_('control.restriction.start').DynamicFormHelper::_('control.restriction.mandatory').DynamicFormHelper::_('control.restriction.end')."</small>";
        $result .= "</label>
        <select name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\"";
        $result .= ($active) ? "" : " disabled=\"disabled\"";
        $result .= ">
            <option valule=\"\"";
        $result .= ($this->content === '') ? " selected=\"selected\"" : "";
        $result .= ">".DynamicFormHelper::_('control.singlechoice.null')."</option>";
        foreach ($this->spec->items as $j => $item_item)
        {
            $result .= "
            <option value=\"$j\"";
            $result .= ($this->content === $j) ? " selected=\"selected\"" : "";
            $result .= ">$item_item</option>";
        }
        $result .= "
        </select>";
        return $result;
    }

    public function validate()
    {
        $validationErrors = array();
        if ($this->mandatory && $this->content === '')
            $validationErrors[] = DynamicFormValidationError::MANDATORY;
        return $validationErrors;
    }

    function __construct($object, $content)
	{
        parent::__construct($object, $content);
        $this->type = self::getType();
        if (is_null($content) || !is_numeric($content)) {
            $this->content =  '';
        }
        else {
            $this->content = intval($content);
        }
	}

}

?>