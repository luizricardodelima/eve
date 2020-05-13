<?php

class CustomInputHelper
{
    public static function supportedFiletypes()
    {
        $ini_array = parse_ini_file('filetypes.ini', true);
        $return_array = array();
        foreach ($ini_array as $key => $value)
        $return_array[$key] = $value['extension'];
        return $return_array;
    }

    /** $filetype can be string containing mimetype or an array of strings containing mimetype*/
    public static function extensions($filetype)
    {
        $ini_array = parse_ini_file('filetypes.ini', true);
        $filetype_array = array();
        if (!is_array($filetype))	
            $filetype_array[] = $filetype;
        else
            $filetype_array = $filetype;
        $extensions_array = array();
        foreach ($filetype_array as $filetype_) foreach ($ini_array[$filetype_]['extension'] as $extension) $extensions_array[] = $extension;
        return $extensions_array;
    }
}
?>