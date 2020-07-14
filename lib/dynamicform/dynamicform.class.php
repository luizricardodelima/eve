<?php

require_once 'dynamicformhelper.class.php';
// Dynamically loading all DynamicFormItems. Its file names follow a pattern
foreach (scandir(dirname(__FILE__)) as $file)
    if (preg_match('/^dynamicformitem.*\.php$/', $file)) require_once dirname(__FILE__).'/'.$file;

/**
 * A DynamicForm allows an user to dynamically define a structure of a form.
 * The structure of a DynamicForm is composed of DynamicFormItems. A DynamicFormItem
 * contains the information of a "field" of the form, such as its type and content.
 * 
 * The structure of DynamicFormItems is an ordered array, so the form information 
 * (such as structure table, controls and formatted content) will be displayed in
 * the same order of this array. 
 * 
 * @see DynamicFormItem
 */
class DynamicForm
{
    public  $structure;
    private $dynamicFormItemClasses;

    /**
     * Returns a human-readable HTML <table> with the fields and their contents
     * 
     * Optionally the parameters $htmlClass and/or $htmlId can be passed, if the table
     * needs to be styled with css or modified with javascript. As can be deduced, if
     * these parameters are not null, the table will be output with class="$htmlClass"
     * and/or id="$htmlId" attributes.
     * 
     */
    function getHtmlFormattedContent($htmlClass = null, $htmlId = null)
    {
        $result = "<table";
        if ($htmlClass !== null) $result .= " class=\"$htmlClass\"";
        if ($htmlId !== null) $result .= " id=\"$htmlId\"";
        $result .= ">";
        foreach ($this->structure as $structureItem)
        {
            $result .= "<tr><td>{$structureItem->description}</td><td>";
            $result .= $structureItem->getHtmlFormattedContent();
            $result .= "</td></tr>";
        }                
        $result .= "</table>";
        return $result;
    }

    function getJSONStructure() {
        $contents = array();
        foreach ($this->structure as $structureItem)
            $contents[] = $structureItem->getJSONStructure();
        return "[" . implode(", ", $contents) . "]";
    }

    function getJSONContent() {
        $contents = array();
        foreach ($this->structure as $structureItem)
            $contents[] = json_encode($structureItem->content);
        return "[" . implode(", ", $contents) . "]";
    }

    function outputControls($strName, $cntName, $active = true)
    {
        $result  = "
        <!-- Begin of DynamicForm's automatically generated code-->
        <input type=\"hidden\" name=\"$strName\" value=\"".htmlentities($this->getJSONStructure())."\" />";
        foreach ($this->structure as $index => $structureItem)
            $result .= $structureItem->outputControls($cntName, $index, $active);
        $result .= "
        <!-- End of DynamicForm's automatically generated code-->";
        return $result;
    }

    function outputStructureTable($strName, $tableClass = null, $toolbarClass = null)
    {
        $path = DynamicFormHelper::find_relative_path(dirname($_SERVER['SCRIPT_FILENAME']),dirname(__FILE__));
        $result = "
        <!-- Begin of DynamicForm's automatically generated code-->
        <script>
        var str_{$strName} = {$this->getJSONStructure()};
        var typesNames_{$strName} = {";
        $temp = array();
        foreach ($this->dynamicFormItemClasses as $dynamicFormItemClass)
            $temp[] = $dynamicFormItemClass::getType().': \''.DynamicFormHelper::_('item.'.$dynamicFormItemClass::getType()).'\'';
        $result .= implode(", ", $temp);
        $result  .= "};

        function move_item_{$strName}(i, offset)
		{
			if (i+offset >= str_{$strName}.length || i+offset < 0) return;
			current_item = str_{$strName}[i];
			moved_item = str_{$strName}[i+offset];
			str_{$strName}[i+offset] = current_item;
			str_{$strName}[i] = moved_item;
			update_table_{$strName}();
			update_field_{$strName}();
		}

		function delete_item_{$strName}(i)
		{
			if(confirm('".DynamicFormHelper::_('structure.table.message.delete.1')."' + (i+1) + '".DynamicFormHelper::_('structure.table.message.delete.2')."'))
			{
				str_{$strName}.splice(i, 1);
				update_table_{$strName}();
				update_field_{$strName}();
			}
        }
        
        function update_field_{$strName}()
		{
			document.getElementById('field_{$strName}').value = JSON.stringify(str_{$strName});
		}

		function update_table_{$strName}()
		{
			var table = document.getElementById('structure_table_body_{$strName}');
			while (table.rows.length > 0) table.deleteRow(-1);
			for (j = 0; j < str_{$strName}.length; j++)
			{ 
				var tr = table.insertRow(-1);
				tr.insertCell(-1).innerHTML = j+1;
				tr.insertCell(-1).innerHTML = typesNames_{$strName}[str_{$strName}[j].type];
                tr.insertCell(-1).innerHTML = str_{$strName}[j].description;
                tr.insertCell(-1).innerHTML = str_{$strName}[j].customattribute;
				tr.insertCell(-1).innerHTML = (str_{$strName}[j].mandatory) ? '&#8226;' : '';
				tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"move_item_{$strName}('+j+', -1)\"><img src=\"$path/icons/up.png\"/></button></td>';
				tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"move_item_{$strName}('+j+', +1)\"><img src=\"$path/icons/down.png\"/></button>';
        ";

        // DynamicInputItems edit functions
        foreach ($this->dynamicFormItemClasses as $dynamicFormItemClass)
            $result .= "
                if (str_{$strName}[j].type == '{$dynamicFormItemClass::getType()}') tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"{$dynamicFormItemClass::javascriptEditMethod()}_{$strName}('+j+')\"><img src=\"$path/icons/edit.png\"/></button>';";
        
        $result .= "
                tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"delete_item_{$strName}('+j+')\"><img src=\"$path/icons/delete.png\"/></button>';
				tr.childNodes[3].style.textAlign = 'center';
				tr.childNodes[4].style.textAlign = 'center';
			}
        }
        </script>
        <div";
        if (!is_null($toolbarClass)) $result .= " class=\"$toolbarClass\"";
        $result .= ">";

        // CustomItems add buttons and edit controls
        foreach ($this->dynamicFormItemClasses as $dynamicFormItemClass)
            $result .= $dynamicFormItemClass::outputAddEditControls($strName);
        
        $result .= "
        </div>
        <table id=\"structure_table_{$strName}\"";
        if (!is_null($tableClass)) $result .= " class=\"$tableClass\"";
        $result .= ">
        <thead>
        <th>".DynamicFormHelper::_('structure.table.header.position')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.type')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.description')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.customattribute')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.mandatory')."</th>
        <th colspan=\"4\">".DynamicFormHelper::_('structure.table.header.options')."</th>
        </thead>
        <tbody id=\"structure_table_body_{$strName}\"></tbody>
        </table>
        <input type=\"hidden\" name=\"{$strName}\" id=\"field_{$strName}\"/>
        <script>
        update_table_{$strName}();
        update_field_{$strName}();
        </script>
        <!-- End of DynamicForm's automatically generated code -->";
        return $result;        
    }

    /** 
     * Performs the validation of the content on Dynamic Form. If there is file information, 
     * it will try to upload the files to upload folder and update the content with the
     * uploaded file path.
     * 
     * This function returns an array with pairs of indexes and error codes. The indexes are
     * in the order of the structure elements (from 0 to n-1). The error codes are constants
     * defined in the class DynamicFormValidationError.
     * 
     * Notice that this function can upload a file to server if all the validations for this
     * field are successful and still return validation errors for other fields.
     * 
     * If the validation is successful, an empty array is returned.
     */
    function validate($humanReadableOutput = true)
    {
        $validationErrors = array();
        foreach($this->structure as $i => $structureItem)
        {
            $validationMessages = $structureItem->validate();
            if (!empty($validationMessages)) $validationErrors[$i] = $validationMessages;
        }
        if ($humanReadableOutput)
        {
            $humanReadableMessages = array();
            foreach ($validationErrors as $i => $validationMessages) foreach ($validationMessages as $validationMessage) 
            {
                $placeholder =  ["<field>" => $this->structure[$i]->description];
                $humanReadableMessages[] = DynamicFormHelper::_($validationMessage, $placeholder);
            }
            return $humanReadableMessages;
        }
        else 
        {
            return $validationErrors;
        }
    }

    /**
     * DynamicForm constructor
     * 
     * This is the start point for constructing DynamicForms
     * 
     * $structure - String - A JSON Description of the structure
     * $content - Array - An ordered array with the content
     * $files - The $_FILES['$contentName'] containing the uploaded files
     * $uploadPath - The upload path for files, if $files is not null
     */
    function __construct($structure = null, $content = null, $files = null, $uploadPath = null)
	{
        // Loading all Declared Classes that are of type DynamicFormItem
        $this->dynamicFormItemClasses = array();
        foreach( get_declared_classes() as $class ){
          if( is_subclass_of( $class, 'DynamicFormItem' ) )
            $this->dynamicFormItemClasses[] = $class;
        }

        // Creating DynamicFormItem's array
        $this->structure = array();

        // Reading $files and organizing its information
        $newFiles = null;
        if (is_array($files))
        {
            $newFiles = array();
            foreach ($files as $file_field => $file_info)
                foreach ($file_info as $index => $info) {
                    $newFiles[$index][$file_field] = $info;
                    $newFiles[$index]['upload_path'] = $uploadPath;
                }
        }
        
        // Populating DynamicFormItems array if a structure is passed
        if (!empty($structure)) {
            foreach (json_decode($structure) as $i => $structureItem) {
                foreach ($this->dynamicFormItemClasses as $dynamicFormItemClass) {
                    if ($dynamicFormItemClass::getType() == $structureItem->type) {
                        $content_item = null;
                        if (isset($content[$i])) $content_item = $content[$i];
                        else if (isset($newFiles[$i])) $content_item = $newFiles[$i];
                        $this->structure[] = new $dynamicFormItemClass($structureItem, $content_item);                        
                    }
                }
            }
        }   
    }
}

?>