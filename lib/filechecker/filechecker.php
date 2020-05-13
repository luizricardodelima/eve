<?php

/** $filetypes can be string containing mimetype or an array of strings containing mimetype*/
function validate_filetype($file, $filetypes)
{
	if (!is_array($filetypes))
		return (trim($filetypes) == "") || ($file['type'] == $filetypes);
	else
		return in_array($file['type'], $filetypes);
}


function validate_filesize($file, $size)
{
	return $file['size'] <= $size;	
}

function validate_filesizeMB($file, $sizeMB)
{
	return $file['size'] <= ($sizeMB * 1048576);	
}



/** $filetype can be string containing mimetype or an array of strings containing mimetype*/
function extensions($filetype)
{
	$ini_array = parse_ini_file('filechecker.ini', true);
	$filetype_array = array();
	if (!is_array($filetype))	
		$filetype_array[] = $filetype;
	else
		$filetype_array = $filetype;
	$extensions_array = array();
	foreach ($filetype_array as $filetype_) foreach ($ini_array[$filetype_]['extension'] as $extension) $extensions_array[] = $extension;
	return $extensions_array;
}


function get_supported_filetypes_and_extensions()
{
	$ini_array = parse_ini_file('filechecker.ini', true);
	$return_array = array();
	foreach ($ini_array as $key => $value)
	$return_array[$key] = $value['extension'];
	return $return_array;
}

?>
