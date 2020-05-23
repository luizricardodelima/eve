<?php
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemBigtext extends DynamicFormItem
{
    public function getFormattedContent()
    {
        return $this->content;
    }

    public function getHtmlFormattedContent()
    {
        return htmlentities($this->content, ENT_QUOTES, 'utf-8');
    }
    
    public static function getType()
    {
        return 'bigtext'; 
    }

    public static function javascriptEditMethod()
    {
        return 'bgt_edt';
    }

    public static function outputAddEditControls($name) 
    {
        $result = "
        <script>
        function bgt_add_{$name}()
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
					spec:{min_words:0, max_words:0}
				});
				update_table_{$name}();
				update_field_{$name}();
			}
        }
        function bgt_edt_{$name}(i)
        {
            document.getElementById('bgt_dlg_{$name}').style.display = 'block';
            document.getElementById('bgt_des_{$name}').value = str_{$name}[i].description;
            document.getElementById('bgt_cat_{$name}').value = str_{$name}[i].customattribute;
            document.getElementById('bgt_min_{$name}').value = str_{$name}[i].spec.min_words;
            document.getElementById('bgt_max_{$name}').value = str_{$name}[i].spec.max_words;
            document.getElementById('bgt_man_{$name}').checked = str_{$name}[i].mandatory;
            document.getElementById('bgt_sav_{$name}').onclick = function(){bgt_sav_{$name}(i);};
        }
        function bgt_sav_{$name}(i)
        {
            str_{$name}[i].description = document.getElementById('bgt_des_{$name}').value;
            str_{$name}[i].customattribute = document.getElementById('bgt_cat_{$name}').value;
			str_{$name}[i].spec.min_words = document.getElementById('bgt_min_{$name}').value;
			str_{$name}[i].spec.max_words = document.getElementById('bgt_max_{$name}').value;
			str_{$name}[i].mandatory = document.getElementById('bgt_man_{$name}').checked;
			document.getElementById('bgt_dlg_{$name}').style.display = 'none';
			update_table_{$name}();
			update_field_{$name}();
        }
        </script>

        <div id=\"bgt_dlg_{$name}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<label for=\"bgt_des_{$name}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"bgt_des_{$name}\" type=\"text\"/>
        <label for=\"bgt_cat_{$name}\">".DynamicFormHelper::_('item.customattribute')."</label>
        <input  id=\"bgt_cat_{$name}\" type=\"text\"/>
        <label for=\"bgt_man_{$name}\">
        <input  id=\"bgt_man_{$name}\" type=\"checkbox\"/>".DynamicFormHelper::_('item.mandatory')."</label>
        <label for=\"bgt_min_{$name}\">".DynamicFormHelper::_('item.bigtext.spec.minwords')."</label>
		<input  id=\"bgt_min_{$name}\" type=\"number\" min=\"0\" step=\"1\" value=\"0\"/>
		<label for=\"bgt_max_{$name}\">".DynamicFormHelper::_('item.bigtext.spec.maxwords')."</label>
		<input  id=\"bgt_max_{$name}\" type=\"number\" min=\"0\" step=\"1\" value=\"0\"/>
		<button type=\"button\" id=\"bgt_sav_{$name}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('bgt_dlg_{$name}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"bgt_add_{$name}();\">";
        $result .= DynamicFormHelper::_('structure.table.button.add.bigtext');
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <span>
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        $requirements = array();
        if ($this->mandatory) $requirements[] = DynamicFormHelper::_('control.restriction.mandatory');
        if ($this->spec->min_words) $requirements[] = DynamicFormHelper::_('control.restriction.minwords').$this->spec->min_words;
        if ($this->spec->max_words) $requirements[] = DynamicFormHelper::_('control.restriction.maxwords').$this->spec->max_words;
        if (!empty($requirements)) $result .= "<small>".DynamicFormHelper::_('control.restriction.start').implode(", ", $requirements).DynamicFormHelper::_('control.restriction.end')."</small>";
        $result .= "</label>
        <span id=\"bgt_inf_{$htmlName}_{$index}\" style=\"float: right; font-size: 0.8rem\">
        <label id=\"bgt_cnt_{$htmlName}_{$index}\"></label>
        </span>
        </span>
    
        <textarea name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\" style=\"width: 100%;\" rows=\"15\"";
        $result .= ($this->spec->min_words || $this->spec->max_words) ? " onkeyup=\"bgt_check_{$htmlName}_{$index}()\"" : "";
        $result .= ($active) ? "" : " disabled=\"disabled\"";
        $result .= ">";
        $result .= htmlentities($this->content, ENT_QUOTES, 'utf-8');
        $result .= "</textarea>";

        if ($this->spec->min_words || $this->spec->max_words) $result .= "
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
                document.getElementById('bgt_inf_{$htmlName}_{$index}').style.fontWeight = 'bold';
            }
            else
            {
                document.getElementById('bgt_inf_{$htmlName}_{$index}').style.fontWeight = 'normal';
            }
        }
        bgt_check_{$htmlName}_{$index}();
        </script>
        ";
        return $result;
    }

    public function validate()
    {
        $validationErrors = array();
        if ($this->mandatory && $this->content == '')
            $validationErrors[] = DynamicFormValidationError::MANDATORY;
		if ($this->spec->min_words && preg_match_all("/\S+/", $this->content) < $this->spec->min_words)
			$validationErrors[] = DynamicFormValidationError::UNDER_MIN_WORDS;
		if ($this->spec->max_words && preg_match_all("/\S+/", $this->content) > $this->spec->max_words)
			$validationErrors[] = DynamicFormValidationError::OVER_MAX_WORDS;
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