<?php

class DynamicFormHelper
{
    public static $locale = "en";
    public static $dictionary = null;

    // translate key - dictionary
	static function _($key, $placeholders = null)
	{
        if (self::$dictionary === null) self::load_dictionary();
        $translation = isset(self::$dictionary[$key]) ? self::$dictionary[$key] : $key;
        if (is_array($placeholders)) foreach($placeholders as $key => $value)
            $translation = str_replace($key, $value, $translation);
		return $translation;
	}

	static function load_dictionary()
	{
		$lang_file = __DIR__ . '/g11n/' . self::$locale . '.json';
		if (!file_exists($lang_file))
		{
            // Loading default english dictionary, if file is not found
      		$lang_file = __DIR__ . '/g11n/en.json';
    	}
		// Loading the language JSON file and transforming it into an associative array
        self::$dictionary = json_decode(file_get_contents($lang_file), true);
    }

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

    // This function returns the base url of the system. This works particularly for this system
	// since all its php codes are located on system's base folder. It depends strongly on the php
	// code which initially calls the function, the request URI.
    static function url()
    {
		//TODO: try to understand $SERVER variables to check if this routine works properly on different servers
		$server_name = isset($_SERVER['HTTP_X_FORWARDED_SERVER']) ?  $_SERVER['HTTP_X_FORWARDED_SERVER'] : $_SERVER["SERVER_NAME"];
		$result = sprintf("%s://%s%s", isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', $server_name, $_SERVER['REQUEST_URI']);
		if (true) 
		{
			$i = strlen($result);
			while (substr($result, $i, 1) != "/") { $i--;}
			$result = substr( $result, 0, $i + 1);
		}
		return $result;
    }
    
    /**
     * https://gist.github.com/ohaal/2936041
     * Find the relative file system path between two file system paths
     *
     * @param  string  $frompath  Path to start from
     * @param  string  $topath    Path we want to end up in
     *
     * @return string             Path leading from $frompath to $topath
     */
    function find_relative_path ( $frompath, $topath ) 
    {
        $from = explode( DIRECTORY_SEPARATOR, $frompath ); // Folders/File
        $to = explode( DIRECTORY_SEPARATOR, $topath ); // Folders/File
        $relpath = '';

        $i = 0;
        // Find how far the path is the same
        while ( isset($from[$i]) && isset($to[$i]) ) {
            if ( $from[$i] != $to[$i] ) break;
            $i++;
        }
        $j = count( $from ) - 1;
        // Add '..' until the path is the same
        while ( $i <= $j ) {
            if ( !empty($from[$j]) ) $relpath .= '..'.DIRECTORY_SEPARATOR;
            $j--;
        }
        // Go to folder from where it starts differing
        while ( isset($to[$i]) ) {
            if ( !empty($to[$i]) ) $relpath .= $to[$i].DIRECTORY_SEPARATOR;
            $i++;
        }
        
        // Strip last separator
        return substr($relpath, 0, -1);
    }
}
?>