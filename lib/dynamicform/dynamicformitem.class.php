<?php

abstract class DynamicFormItem
{
    public $content;
    public $type;
    public $spec;
    public $description;
    public $customattribute;
    public $mandatory;
    
    public function getJSONStructure() {
        return json_encode(
            array(
            'type' => $this->type,
            'description' => $this->description,
            'customattribute' => $this->customattribute,
            'mandatory' => $this->mandatory,
            'spec' => $this->spec
        ));
    }

    public abstract function getFormattedContent();
    public abstract function getHtmlFormattedContent();
    public abstract static function getType();
    public abstract static function javascriptEditMethod();
    public abstract static function outputAddEditControls($html_id);
    public abstract function outputControls($htmlName, $index, $active);
    public abstract function validate();
    
    function __construct($object, $content = null)
	{
        $this->description = $object->description;
        $this->customattribute = $object->customattribute;
        $this->mandatory = $object->mandatory;
        $this->spec = $object->spec;
    }
    

}

?>