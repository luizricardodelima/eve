<?php

abstract class DynamicFormItem
{
    protected $content;
    protected $type;
    public $spec;
    public $description;
    public $unrestrict;
    public $mandatory;
    
    public function getJSONStructure() {
        return json_encode(
            array(
            'type' => $this->type,
            'description' => $this->description,
            'unrestrict' => $this->unrestrict,
            'mandatory' => $this->mandatory,
            'spec' => $this->spec
        ));
    }

    public abstract function getHtmlFormattedContent();
    public abstract static function getType();
    public abstract static function javascriptEditMethod();
    public abstract static function outputDynamicFormStructureAddButton($html_id);
    public abstract function outputControls($htmlName, $index, $active);
    public abstract function validate();
    
    function __construct($object, $content = null)
	{
        $this->description = $object->description;
        $this->unrestrict = $object->unrestrict;
        $this->mandatory = $object->mandatory;
        $this->spec = $object->spec;
    }
    

}

?>