<?php

require_once 'dynamicformhelper.class.php';
// TODO DO I HAVE TO LIST THEM ALL?
require_once 'dynamicformitem.class.php';
require_once 'dynamicformitemarray.class.php';
require_once 'dynamicformitembigtext.class.php';
require_once 'dynamicformitemcheck.class.php';
require_once 'dynamicformitemenum.class.php';
require_once 'dynamicformitemfile.class.php';
require_once 'dynamicformitemtext.class.php';

class DynamicForm
{
    private $structure;
    private $customInputItemClasses;

    /**
     * Returns a human-readable HTML table with the fields and their contents
     * 
     * If $unrestrictOnly == true, only the unrestrict items will be shown
     * 
     * Optionally the parameters $htmlClass and/or $htmlId can be passed, if the table
     * needs to be styled with css or modified with javascript. As can be deduced, if
     * these parameters are not null, the table will be output with class="$htmlClass"
     * and/or id="$htmlId" attributes.
     */
    function getHtmlFormattedContent($unrestrictOnly = false, $htmlClass = null, $htmlId = null)
    {
        $result = "<table";
        if ($htmlClass !== null) $result .= " class=\"$htmlClass\"";
        if ($htmlId !== null) $result .= " id=\"$htmlId\"";
        $result .= ">";
        foreach ($this->structure as $structureItem)
            if ($structureItem->unrestrict || !$unrestrictOnly)
                $result .= $structureItem->getHtmlFormattedContent();
        $result .= "</table>";
        return $result;
    }

    function getJSONStructure() {
        $result = '[';
        foreach ($this->structure as $structureItem)
            $result .= $structureItem->getJSONStructure().",";
        $result = rtrim($result,',');
        $result .= ']';
        return $result;
    }

    function getJSONContent() {
        $result = '[';
        foreach ($this->structure as $structureItem)
            $result .= "'".$structureItem->content."',";
        $result = rtrim($result,',');
        $result .= ']';
        return $result;
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
        $result  = "
        <!-- Begin of DynamicForm's automatically generated code-->
        <script>
        var str_{$strName} = {$this->getJSONStructure()};
        var typesNames_{$strName} = {";
        $temp = array();
        foreach ($this->customInputItemClasses as $customInputItemClass)
            $temp[] = $customInputItemClass::getType().': \''.DynamicFormHelper::_('item.'.$customInputItemClass::getType()).'\'';
        $result .= implode(", ", $temp);
        //    if (str_{$strName}[j].type == '{}'
            //a: 200, b: 300
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
			if(confirm('".DynamicFormHelper::_('structure.table.confirm.delete.1')."' + (i+1) + '".DynamicFormHelper::_('structure.table.confirm.delete.2')."'))
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
				tr.insertCell(-1).innerHTML = (str_{$strName}[j].unrestrict) ? '&#8226;' : '';
				tr.insertCell(-1).innerHTML = (str_{$strName}[j].mandatory) ? '&#8226;' : '';
				tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"move_item_{$strName}('+j+', -1)\"><img src=\"".substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']))."/icons/up.png\"/></button></td>';
				tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"move_item_{$strName}('+j+', +1)\"><img src=\"".substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']))."/icons/down.png\"/></button>';
        ";

        // CustomItems edit functions
        foreach ($this->customInputItemClasses as $customInputItemClass)
            $result .= "
                if (str_{$strName}[j].type == '{$customInputItemClass::getType()}') tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"{$customInputItemClass::javascriptEditMethod()}_{$strName}('+j+')\"><img src=\"".substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']))."/icons/edit.png\"/></button>';";
        
        $result .= "
                tr.insertCell(-1).innerHTML = '<button type=\"button\" onclick=\"delete_item_{$strName}('+j+')\"><img src=\"".substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']))."/icons/delete.png\"/></button>';
				tr.childNodes[3].style.textAlign = 'center';
				tr.childNodes[4].style.textAlign = 'center';
			}
        }
        </script>
        <div";
        if (!is_null($toolbarClass)) $result .= " class=\"$toolbarClass\"";
        $result .= ">";

        // CustomItems add Buttons
        foreach ($this->customInputItemClasses as $customInputItemClass)
            $result .= $customInputItemClass::outputDynamicFormStructureAddButton($strName);
        
        $result .= "
        </div>
        <table id=\"structure_table_{$strName}\"";
        if (!is_null($tableClass)) $result .= " class=\"$tableClass\"";
        $result .= ">
        <thead>
        <th>".DynamicFormHelper::_('structure.table.header.position')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.type')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.description')."</th>
        <th>".DynamicFormHelper::_('structure.table.header.unrestrict')."</th>
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
    function validate()
    {
        $validationErrors = array();
        foreach($this->structure as $i => $structureItem)
        {
            $validationMessages = $structureItem->validate();
            if (!empty($validationMessages)) $validationErrors[$i] = $validationMessages;
        }
        return $validationErrors;
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
        $this->customInputItemClasses = array();
        foreach( get_declared_classes() as $class ){
          if( is_subclass_of( $class, 'DynamicFormItem' ) )
            $this->customInputItemClasses[] = $class;
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
                foreach ($this->customInputItemClasses as $customInputItemClass) {
                    if ($customInputItemClass::getType() == $structureItem->type) {
                        $content_item = null;
                        if (isset($content[$i])) $content_item = $content[$i];
                        else if (isset($newFiles[$i])) $content_item = $newFiles[$i];
                        $this->structure[] = new $customInputItemClass($structureItem, $content_item);                        
                    }
                }
            }
        }   
    }
}

?>