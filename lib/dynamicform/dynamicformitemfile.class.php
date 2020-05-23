<?php
require_once "dynamicformhelper.class.php";
require_once "dynamicformitem.class.php";
require_once "dynamicformvalidationerror.class.php";

class DynamicFormItemFile extends DynamicFormItem
{

    private $uploadedFile;

    public function getFormattedContent()
    {
        return $this->content;
    }

    public function getHtmlFormattedContent()
    {
        return "<a href=\"{$this->content}\" download>{$this->content}</a>";
    }

    public static function getType()
    {
        return 'file'; 
    }

    public static function javascriptEditMethod()
    {
        return 'fil_edt';
    }

    public static function outputAddEditControls($name) 
    {
        $result = "
        <script>
        function fil_add_{$name}()
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
					spec:{file_types:[], max_size:0}
				});
				update_table_{$name}();
				update_field_{$name}();
			}
        }
        function fil_edt_{$name}(i)
        {
        ";
        $result .= "
            var filetypes = " . json_encode(array_keys(DynamicFormHelper::supportedFiletypes())) . ";";
        $result .= "
            document.getElementById('fil_dlg_{$name}').style.display = 'block';
            document.getElementById('fil_des_{$name}').value = str_{$name}[i].description;
            document.getElementById('fil_cat_{$name}').value = str_{$name}[i].customattribute;
            document.getElementById('fil_max_{$name}').value = str_{$name}[i].spec.max_size;
            filetypes.forEach(function(element)
            {
                if (str_{$name}[i].spec.file_types.includes(element))
                    document.getElementById('fil_typ_'+ element +'_{$name}').checked = true;
                else
                    document.getElementById('fil_typ_'+ element +'_{$name}').checked = false;
            });
            document.getElementById('fil_man_{$name}').checked = str_{$name}[i].mandatory;
            document.getElementById('fil_sav_{$name}').onclick = function(){fil_sav_{$name}(i);};
        }
        function fil_sav_{$name}(i)
        {
        ";
        $result .= "
            var filetypes = " . json_encode(array_keys(DynamicFormHelper::supportedFiletypes())) . ";";
        $result .= "
            str_{$name}[i].description = document.getElementById('fil_des_{$name}').value;
            str_{$name}[i].customattribute = document.getElementById('fil_cat_{$name}').value;
            str_{$name}[i].spec.max_size = document.getElementById('fil_max_{$name}').value;
            str_{$name}[i].spec.file_types = new Array();
            filetypes.forEach(function(element)
            {
                if (document.getElementById('fil_typ_'+ element +'_{$name}').checked)
                    str_{$name}[i].spec.file_types.push(element);
            });
			str_{$name}[i].mandatory = document.getElementById('fil_man_{$name}').checked;
			document.getElementById('fil_dlg_{$name}').style.display = 'none';
			update_table_{$name}();
			update_field_{$name}();
        }
        </script>

        <div id=\"fil_dlg_{$name}\" style=\"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\">
		<div style=\"background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; 	display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;\">
		<label for=\"fil_des_{$name}\">".DynamicFormHelper::_('item.description')."</label>
        <input  id=\"fil_des_{$name}\" type=\"text\"/>
        <label for=\"fil_cat_{$name}\">".DynamicFormHelper::_('item.customattribute')."</label>
        <input  id=\"fil_cat_{$name}\" type=\"text\"/>
        <label for=\"fil_man_{$name}\">
        <input  id=\"fil_man_{$name}\" type=\"checkbox\"/>".DynamicFormHelper::_('item.mandatory')."</label>
        <label for=\"fil_max_{$name}\">".DynamicFormHelper::_('item.file.spec.maxsize')."</label>
		<input  id=\"fil_max_{$name}\" type=\"number\" min=\"0\" step=\"0.1\" value=\"0\"/>
		<label>".DynamicFormHelper::_('item.file.spec.filetypes')."</label>";
		foreach (DynamicFormHelper::supportedFiletypes() as $file_type => $file_extensions)
		{
            $result .= "
            <span>
            <input  id=\"fil_typ_{$file_type}_{$name}\" type=\"checkbox\" />
            <label for=\"fil_typ_{$file_type}_{$name}\">{$file_extensions[0]}</label>
            </span>";
		}
        $result .= "
        <button type=\"button\" id=\"fil_sav_{$name}\">".DynamicFormHelper::_('item.action.save')."</button>
		<button type=\"button\" onclick=\"document.getElementById('fil_dlg_{$name}').style.display = 'none';\">".DynamicFormHelper::_('item.action.cancel')."</button>
		</div>
		</div>
        ";
        $result .= "<button type=\"button\" onclick=\"fil_add_{$name}();\">";
        $result .= DynamicFormHelper::_('structure.table.button.add.file');
        $result .= "</button>";
        return $result;
    }

    public function outputControls($htmlName, $index, $active) {
        $result = "
        <label for=\"{$htmlName}[{$index}]\">{$this->description}";
        $requirements = array();
        if ($this->mandatory) $requirements[] = DynamicFormHelper::_('control.restriction.mandatory');
        if (!empty($this->spec->file_types)) $requirements[] = DynamicFormHelper::_('control.restriction.filetypes').implode(" ", DynamicFormHelper::extensions($this->spec->file_types));
        else $requirements[] = DynamicFormHelper::_('control.restriction.filetypes').DynamicFormHelper::_('control.restriction.filetypes.all');
        if ($this->spec->max_size) $requirements[] = DynamicFormHelper::_('control.restriction.maxsize').$this->spec->max_size.DynamicFormHelper::_('control.restriction.maxsize.megabytes');
        if (!empty($requirements)) $result .= "<small>".DynamicFormHelper::_('control.restriction.start').implode(", ", $requirements).DynamicFormHelper::_('control.restriction.end')."</small>";
        $result .= "</label>";
        
        if ($this->content != '') $result.= "
        <div id=\"fil_info_{$htmlName}_{$index}\">
        <a href=\"{$this->content}\">{$this->content}</a>";
        if ($this->content != '' && $active) $result.= "
        <button type=\"button\" style=\"float: right;\" onclick=\"fil_input_show_{$htmlName}_{$index}()\">".DynamicFormHelper::_('control.action.change')."</button>";
        if ($this->content != '') $result.= "
        <input type=\"hidden\" name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}[{$index}]\" value=\"{$this->content}\"/>
        </div>
        ";

        $result .= "
        <div id=\"fil_input_{$htmlName}_{$index}\" style=\"display: none;\">
        <input type=\"file\" name=\"{$htmlName}[{$index}]\" id=\"{$htmlName}_{$index}_input\" disabled=\"disabled\"/>
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

    public function validate()
    {
        // If a file has already been uploaded, we assume it has already been validated
        if ($this->content != '') return array();

        $validationErrors = array();

        // No file sent on a mandatory field (Error # 4 = no file specified)
        if ($this->mandatory && $this->uploadedFile['error'] == 4)
		{
			$validationErrors[] = DynamicFormValidationError::MANDATORY;
        }
        // Other server errors (Error # 0 = no error / Error # 4 = no file specified, dealt with above)
        if ($this->uploadedFile['error'] != 0 && $this->uploadedFile['error'] != 4) 
		{
			$validationErrors[] = DynamicFormValidationError::FILE_ERROR;
        }
        // Checking maximum file size (1 megabyte = 1048576 bytes)
		if ($this->uploadedFile['error'] == 0 && $this->spec->max_size && $this->uploadedFile['size'] > $this->spec->max_size * 1048576)
		{
			$validationErrors[] = DynamicFormValidationError::FILE_EXCEEDED_SIZE;
		}
		// Checking file type				
		if ($this->uploadedFile['error'] == 0 && !empty($this->spec->file_types) && !in_array($this->uploadedFile['type'], $this->spec->file_types))
		{
			$validationErrors[] = DynamicFormValidationError::FILE_WRONG_TYPE;
        }
        // No errors so far. Uploading file to server		
		if ($this->uploadedFile['error'] == 0 && empty($validationErrors))
		{
			$randomName = md5(uniqid(rand(), true)); // Generating a random filename
			$extension = pathinfo($this->uploadedFile['name'], PATHINFO_EXTENSION);
			if (move_uploaded_file($this->uploadedFile['tmp_name'], $this->uploadedFile['upload_path'].$randomName.'.'.$extension))
				$this->content = DynamicFormHelper::url().$this->uploadedFile['upload_path'].$randomName.'.'.$extension;
			else
				$validationErrors[] = DynamicFormValidationError::FILE_UPLOAD_ERROR;
		}
        return $validationErrors;
    }

    function __construct($object, $content)
	{
        parent::__construct($object, $content);
        $this->type = self::getType();
        if (is_null ($content)) {
            $this->content =  '';
        }
        else if (is_array($content)) {
            $this->uploadedFile = $content;
            $this->content = '';
        }
        else {
            $this->content = $content;
        }
	}

}

?>