<?php
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemSingleChoice extends DynamicFormItem
{

    public function getHtmlFormattedContent()
    {
        return ($this->content === '') ? '' : $this->spec->items[$this->content];
    }

    public static function getType()
    {
        return 'singlechoice'; 
    }

    public static function javascriptEditMethod()
    {
        return 'edt_sch';
    }

    public static function outputAddEditControls($html_id) 
    {
        $result = "
        <script>
        function add_sch_{$html_id}()
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
					spec:{items:[]}
				});
				update_table_{$html_id}();
				update_field_{$html_id}();
			}
        }
        function edt_sch_{$html_id}(i)
        {
            document.getElementById('sch_dlg_{$html_id}').style.display = 'block';
            document.getElementById('sch_des_{$html_id}').value = str_{$html_id}[i].description;
            document.getElementById('sch_itm_{$html_id}').value = str_{$html_id}[i].spec.items.join('\\n');
            document.getElementById('sch_unr_{$html_id}').checked = str_{$html_id}[i].unrestrict;
            document.getElementById('sch_man_{$html_id}').checked = str_{$html_id}[i].mandatory;
            document.getElementById('sch_sav_{$html_id}').onclick = function(){sav_sch_{$html_id}(i);};
        }
        function sav_sch_{$html_id}(i)
        {
            str_{$html_id}[i].description = document.getElementById('sch_des_{$html_id}').value;
            str_{$html_id}[i].spec.items = document.getElementById('sch_itm_{$html_id}').value.split('\\n');
			str_{$html_id}[i].unrestrict = document.getElementById('sch_unr_{$html_id}').checked;
			str_{$html_id}[i].mandatory = document.getElementById('sch_man_{$html_id}').checked;
			document.getElementById('sch_dlg_{$html_id}').style.display = 'none';
			update_table_{$html_id}();
			update_field_{$html_id}();
        }
        </script>

        <div id=\"sch_dlg_{$html_id}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<span><input type=\"checkbox\" id=\"sch_unr_{$html_id}\"/><label for=\"sch_unr_{$html_id}\">".DynamicFormHelper::_('item.unrestrict')."</label></span>
		<span><input type=\"checkbox\" id=\"sch_man_{$html_id}\"/><label for=\"sch_man_{$html_id}\">".DynamicFormHelper::_('item.mandatory')."</label></span>
		<label for=\"sch_des_{$html_id}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"sch_des_{$html_id}\" type=\"text\"/>
        <label for=\"sch_itm_{$html_id}\">".DynamicFormHelper::_('item.singlechoice.spec.items')." <small>".DynamicFormHelper::_('item.singlechoice.spec.items.help')."</small></label>
		<textarea id=\"sch_itm_{$html_id}\" rows=\"3\"></textarea>
		<button type=\"button\" id=\"sch_sav_{$html_id}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('sch_dlg_{$html_id}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"add_sch_{$html_id}();\">";
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
        if ($this->mandatory && $this->content == '')
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