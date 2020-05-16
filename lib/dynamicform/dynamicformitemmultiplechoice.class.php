<?php
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemMultipleChoice extends DynamicFormItem
{

    public function getHtmlFormattedContent()
    {
        $result = "<ul>";
        foreach ($this->spec->items as $j => $multiplechoiceItem)
        {
            $result .= "<li>{$multiplechoiceItem}: ";
            $result .= ($this->content[$j]) ? DynamicFormHelper::_('item.choice.yes') : DynamicFormHelper::_('item.choice.no');
            $result .= "</li>";
        }
        $result .= "</ul>";
        return $result;
    }

    public static function getType()
    {
        return 'multiplechoice'; 
    }

    public static function javascriptEditMethod()
    {
        return 'mch_edt';
    }

    public static function outputAddEditControls($name) 
    {
        $result = "
        <script>
        function mch_add_{$name}()
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
        function mch_edt_{$name}(i)
        {
            document.getElementById('mch_dlg_{$name}').style.display = 'block';
            document.getElementById('mch_des_{$name}').value = str_{$name}[i].description;
            document.getElementById('mch_cat_{$name}').value = str_{$name}[i].customattribute;
            document.getElementById('mch_itm_{$name}').value = str_{$name}[i].spec.items.join('\\n');
            document.getElementById('mch_man_{$name}').checked = str_{$name}[i].mandatory;
            document.getElementById('mch_sav_{$name}').onclick = function(){mch_sav_{$name}(i);};
        }
        function mch_sav_{$name}(i)
        {
            str_{$name}[i].description = document.getElementById('mch_des_{$name}').value;
            str_{$name}[i].customattribute = document.getElementById('mch_cat_{$name}').value;
            str_{$name}[i].spec.items = document.getElementById('mch_itm_{$name}').value.split('\\n');
			str_{$name}[i].mandatory = document.getElementById('mch_man_{$name}').checked;
			document.getElementById('mch_dlg_{$name}').style.display = 'none';
			update_table_{$name}();
			update_field_{$name}();
        }
        </script>

        <div id=\"mch_dlg_{$name}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<label for=\"mch_des_{$name}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"mch_des_{$name}\" type=\"text\"/>
        <label for=\"mch_cat_{$name}\">".DynamicFormHelper::_('item.customattribute')."</label>
        <input  id=\"mch_cat_{$name}\" type=\"text\"/>
        <label for=\"mch_man_{$name}\">
        <input  id=\"mch_man_{$name}\" type=\"checkbox\"/>".DynamicFormHelper::_('item.mandatory')."</label>
        <label for=\"mch_itm_{$name}\">".DynamicFormHelper::_('item.multiplechoice.spec.items')." <small>".DynamicFormHelper::_('item.multiplechoice.spec.items.help')."</small></label>
		<textarea id=\"mch_itm_{$name}\" rows=\"3\"></textarea>
		<button type=\"button\" id=\"mch_sav_{$name}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('mch_dlg_{$name}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"mch_add_{$name}();\">";
        $result .= DynamicFormHelper::_('structure.table.button.add.multiplechoice');
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        if ($this->mandatory) $result .= "<small>".DynamicFormHelper::_('control.restriction.start').DynamicFormHelper::_('control.restriction.mandatory').DynamicFormHelper::_('control.restriction.end')."</small>";
        $result .= "</label>
        <div>";
        foreach ($this->spec->items as $j => $item_item)
        {
            $result .= "
            <div>
            <input type=\"hidden\" value=\"0\" name=\"{$htmlName}[{$index}][{$j}]\"/>
            <input type=\"checkbox\" value=\"1\" name=\"{$htmlName}[{$index}][{$j}]\" id=\"{$htmlName}[{$index}][{$j}]\"";
            $result .= ($active) ? "" : " disabled=\"disabled\"";
            $result .= ($this->content[$j]) ? " checked=\"checked\"" : "";
            $result .= "/>
            <label for=\"{$htmlName}[{$index}][{$j}]\">{$item_item}</label>
            </div>";
        }
        $result .= "
        </div>";
        return $result;
    }

    public function validate()
    {
        $validationErrors = array();
        $atLeastOneChoice = false;
        foreach ($this->content as $multipleChoiceItem)
            if ($multipleChoiceItem) $atLeastOneChoice = true;
		if ($this->mandatory && !$atLeastOneChoice)
			$validationErrors[] = DynamicFormValidationError::MANDATORY;
        return $validationErrors;
    }

    function __construct($object, $content)
	{
        parent::__construct($object, $content);
        $this->type = self::getType();
        if (is_null ($content)) {
            $this->content = array();
            foreach ($this->spec->items as $item)
                $this->content[] = 0;
        }
        else {
            $this->content = $content;
        }
	}

}

?>