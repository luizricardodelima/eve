<?php
require_once 'evedbconfig.php';
class Eve
{

	public $DBPref;
	public $mysqli;
	public $basepath;
	private $settings = array();
	private $dictionary = null;
	
	// $basepath is optional and is used if there are php codes in other folders (such as plugins), in that case $path needs to receive an argument
	// such as "../../"
	function __construct($basepath="")
	{
		// Establishing Database connection - mysqli		
		$this->mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
		$this->mysqli->set_charset("utf8");
		$this->DBPref = EveDBConfig::$prefix;
		$this->basepath = $basepath;
	}

	// translate key - dictionary
	function _($key)
	{
		if ($this->dictionary === null) $this->load_dictionary();
		return (isset($this->dictionary[$key])) ? $this->dictionary[$key] : $key;
	}

	function load_dictionary()
	{
		$lang_file = $this->basepath . 'g11n/' . $this->getSetting('system_locale') . '.json';
		// Default english dictionary
		if (!file_exists($lang_file))
		{
      			$lang_file = $this->basepath . 'g11n/' . 'en.json';
    		}
		$lang_file_content = file_get_contents($lang_file);
		// Load the language file as a JSON object and transform it into an associative array
		$this->dictionary = json_decode($lang_file_content, true);
	}

	// TODO RENAME TO setting_get //TODO: Prepared statement
	function getSetting($key)
	{
		if (isset($this->settings[$key]))
			return $this->settings[$key];
		else
		{
			$setting = $this->mysqli->query("select * from `{$this->DBPref}settings` where `{$this->DBPref}settings`.`key`='$key';")->fetch_assoc();
			if (!$setting)
				return null;
			else
				return $this->settings[$key] = $setting['value'];
		}
	}

 	// TODO CREATE setting_list
 	// TODO CREATE setting_save

	function output_html_header() 
	{
		?>
		<!DOCTYPE html>
		<?php
			// Displaying tag html
			echo "<html";
			if ($this->getSetting('system_locale'))
			{
				// Displaying attibute lang, if there is a corresponding system_locale set
				echo " lang=\"";
				// Changing pt_BR to pt-br, which is the pattern used in this attribute
				// TODO: pt-br is not accepted as language, make it convert pt_BR to pt
				echo strtolower(str_replace('_', '-', $this->getSetting('system_locale'))); 
				echo "\"";
			}
			echo ">";
		?>
		<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script src="<?php echo $this->basepath;?>lib/jquery/jquery-3.5.0.min.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo $this->basepath;?>style/style.css"/>
		
		<?php
		// Displaying overriding styles for custom colors
		echo "<style>";
		if ($this->getSetting('custom_border_bg'))
			echo
			("
				body { background-color: {$this->getSetting('color_border_bg')};}
			");
		if ($this->getSetting('custom_content_bg'))
			echo
			("
				#main_content { background-color: {$this->getSetting('color_content_bg')};}
				#footer { background-color: {$this->getSetting('color_content_bg')};}
				table.data_table tr:nth-child(even) { background-color: {$this->adjustBrightness($this->getSetting('color_content_bg'), 7)}; }
				table.data_table tr:nth-child(odd) { background-color: {$this->adjustBrightness($this->getSetting('color_content_bg'), -7)}; }
			");
		if ($this->getSetting('custom_content_fg'))
			echo
			("
				#main_content { color: {$this->getSetting('color_content_fg')};}
				#footer { border-color: {$this->adjustBrightness($this->getSetting('color_content_fg'), +100)};}
				#footer, #footer a { color: {$this->adjustBrightness($this->getSetting('color_content_fg'), +100)};}
			");
		if ($this->getSetting('custom_breadcrumbs_bg')) echo "#navigation_bar { background-color: {$this->getSetting('color_breadcrumbs_bg')};}";
		if ($this->getSetting('custom_breadcrumbs_fg')) echo "#navigation_bar, #navigation_bar a { color: {$this->getSetting('color_breadcrumbs_fg')};}";
		if ($this->getSetting('custom_section_bg'))
			echo
			("
				.section { background-color: {$this->getSetting('color_section_bg')};}
				table.data_table th { background-color: {$this->getSetting('color_section_bg')};} 
			");
		if ($this->getSetting('custom_section_fg'))
			echo 
			("
				.section { color: {$this->getSetting('color_section_fg')}; } 
				.section a { color: {$this->getSetting('color_section_fg')}; }
				.section button { color: {$this->getSetting('color_section_fg')}; border-color: {$this->getSetting('color_section_fg')}; background-color: transparent; }
				table.data_table th { color: {$this->getSetting('color_section_fg')};} 
				table.data_table th a { color: {$this->getSetting('color_section_fg')}; }
			");
		if ($this->getSetting('custom_button_bg'))
		{
			$brighter_bg = $this->adjustBrightness($this->getSetting('color_button_bg'), 40);
			echo "button { background-color: {$this->getSetting('color_button_bg')};}";
			echo "button:hover { background-color: $brighter_bg; }";
			echo "table.data_table tr.selected { background-color: {$this->getSetting('color_button_bg')}; }";
		}
		if ($this->getSetting('custom_button_fg'))
		{	
			echo "button { color: {$this->getSetting('color_button_fg')};} span.buttonicon { color: {$this->getSetting('color_button_fg')};} span.buttonlabel { color: {$this->getSetting('color_button_fg')}; }";
			echo "table.data_table tr.selected { color: {$this->getSetting('color_button_fg')}; }";
		}
		echo "</style>";
		?>
		<title><?php echo $this->getSetting('system_name');?></title>
		</head>
		<body><div id="header">
		<?php 

		if ($this->getSetting('show_header_text'))
			echo "<span class=\"header_text\">{$this->getSetting('system_name')}</span>";
		if ($this->getSetting('show_header_image'))
			echo '<img src="upload/header/header.png"/>';
		echo "</div>";
		
		if ($this->getSetting('show_content_menu_and_pages'))
		{
		?>		
		<nav id="menu">
			<ul>
			<?php
			$page_res = $this->mysqli->query("select `id`, `title` from `{$this->DBPref}pages` where `is_visible` != 0 order by `position`;");
			while ($page = $page_res->fetch_assoc()) echo "<li><a href=\"{$this->basepath}index.php?p={$page['id']}\">{$page['title']}</a></li>";
			echo "<li><a href=\"{$this->basepath}userarea.php\">{$this->getSetting('userarea_label')}</a></li>";
			?>
			</ul>
		</nav>
		<?php
		}
		echo "<div id=\"main_content\">";
	}

	function output_html_footer() 
	{
		?>
		</div>
		<?php
		if ($this->getSetting('show_footer'))
			echo "<div id=\"footer\"><a href=\"https://eveeventos.wordpress.com/\" target=\"_blank\">EVE | Gerenciador de eventos acad&ecirc;micos</a></div>";
		?>		
		</body>
		</html>
		<?php
	}

	function output_message($type, $message)
	{
		$class = null;
		$icon = null;
		$text = $this->_($message);
		switch ($type)
		{
			case 'error': $class = 'msg_error'; $icon = '&#249;'; break;
			case 'success': $class = 'msg_success'; $icon = '&#217;'; break;
			default: return null; break;
		}
		echo "<script>function dismiss(el){ el.parentNode.style.display='none';};</script>";
		echo "<div class=\"$class\"><span class=\"msg_icon\">$icon</span><span class=\"msg_body\">$text</span> <button class=\"msg_close\" onclick=\"dismiss(this);\">X</button></div>";
	}

	//	TODO DEPRECATED - Remove
	//  TODO RECONSIDER THE DEPRECATION. IT CAN BE USEFUL.
	function output_error_list_message($message_array)
	{
		// TODO G11n
		$error_message = "Os seguintes erros foram encontrados:";
		foreach($message_array as $message) $error_message .="<li>$message</li>";
		$this->output_message('error', $error_message);
	}
	
	function output_error_message($message)
	{
		$this->output_message('error', $message);
	}

	function output_success_message($message)
	{
		$this->output_message('success', $message);
	}

	/** This function outputs the navigation bar of the user area. It receives pairs of arguments,
	 *  a label and its corresponding link. If the label does not have a link, this link should be
	 *  a null argument. */
	// TODO: This throws an error if an odd number of arguments is passed
	function output_navigation_bar()
	{
		$array = array();
		$back_button_link = null;
		$n = 0; // Counts the number of elements in the navigation menu

		for ($i = 0; $i < func_num_args(); $i = $i+2)
		{
			if (is_null(func_get_arg($i+1)))
			{
				$array[$n] = func_get_arg($i);
			}
			else
			{
				$array[$n] = "<a href=\"".func_get_arg($i+1)."\">".func_get_arg($i)."</a>";
				$back_button_link = func_get_arg($i+1);
			}
			$n++;
		} 

		echo "<div id=\"navigation_bar\">";
		echo "<a href=\"$back_button_link\" class=\"navigation_back\">&#119;</a> ";
		echo implode(' &rarr; ', $array);
		echo "</div>";
	}

	function output_medium_generic_back_button()
	{
		$message = $this->_('common.action.back');
		echo "<button type=\"button\" class=\"medium\" onclick=\"history.back()\">$message</a>";
	}

	function output_medium_goto_button($name, $value, $location)
	{
		echo "<script> function go_to_$name() { window.location.href=\"$location\"; } </script>";
		echo "<button class=\"medium\" type=\"button\" onclick=\"go_to_$name()\">$value</button>";
	}

	function output_big_goto_button($label, $icon_code, $location)
	{
		echo "<button class=\"big\" type=\"button\" onclick=\"window.location.href='$location';\">";
		echo "<div class=\"buttonicon\">$icon_code</div><div class=\"buttonlabel\">{$this->_($label)}</div>";
		echo "</button>";
	}

	function output_error_page($message, $show_back_button = true) 
	{
		$this->output_html_header();
		$this->output_error_message($message);		
		if ($show_back_button) $this->output_medium_generic_back_button();
		$this->output_html_footer();
	}

	function output_redirect_page($url) 
	{
		?>
		<!DOCTYPE html>
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
		<meta http-equiv="REFRESH" content="0;url=<?php echo $url;?>">
		<title></title>
		</head>
		<body></body>
		</html>
		<?php
	}

	function output_wysiwig_editor_code()
	{
		?>
		<script src="lib/tinymce/tinymce.min.js"></script>
		<script>
		tinymce.init({
			selector: 'textarea.htmleditor',
			height: 350,
			menubar: false,
			statusbar: false,
			plugins: [
			'advlist autolink lists link image charmap print preview hr anchor pagebreak',
			'searchreplace wordcount visualblocks visualchars code fullscreen',
			'insertdatetime media nonbreaking save table contextmenu directionality',
			'emoticons template paste textcolor colorpicker textpattern'
			],
			toolbar: 'undo redo | styleselect | bold italic underline superscript subscript | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | preview media | forecolor backcolor emoticons | code fullscreen',
			image_advtab: true,
			valid_elements: '*[*]'
		});
		</script>
		<?php
	}

	// Returns true if a user registered with given $screenname already exists.
	function user_exists($screenname)
	{
		$stmt = $this->mysqli->prepare("SELECT * FROM `{$this->DBPref}user` WHERE `email`=?;");
		$stmt->bind_param('s', $screenname);
		$stmt->execute();
		$stmt->store_result();
		$result = ($stmt->num_rows > 0);
		$stmt->close();
		return $result;
	}

	/** Returns true if given $screenname is an admin. */
	function is_admin($screenname)
	{
		$stmt = $this->mysqli->prepare("SELECT * FROM `{$this->DBPref}userdata` WHERE `email`=? AND `admin` = 1;");
		$stmt->bind_param('s', $screenname);
		$stmt->execute();
		$stmt->store_result();		
		$result = ($stmt->num_rows > 0);
		$stmt->close();
		return $result;
	}

	// This function returns the base url of the system. This works particularly for this system
	// since all its php codes are located on system's base folder. It depends strongly on the php
	// code which initially calls the function, the request URI.
	function url($without_filename = true)
	{
		//TODO: try to understand $SERVER variables to check if this routine works properly on different servers
		$server_name = isset($_SERVER['HTTP_X_FORWARDED_SERVER']) ?  $_SERVER['HTTP_X_FORWARDED_SERVER'] : $_SERVER["SERVER_NAME"];
		$result = sprintf("%s://%s%s", isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', $server_name, $_SERVER['REQUEST_URI']);

		if ($without_filename) 
		{
			$i = strlen($result);
			while (substr($result, $i, 1) != "/") { $i--;}
			$result = substr( $result, 0, $i + 1);
		}
		return $result;
	}

	// https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php	
	function adjustBrightness($hex, $steps) {
	    // Steps should be between -255 and 255. Negative = darker, positive = lighter
	    $steps = max(-255, min(255, $steps));

	    // Normalize into a six character long hex string
	    $hex = str_replace('#', '', $hex);
	    if (strlen($hex) == 3) {
		$hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
	    }

	    // Split into three parts: R, G and B
	    $color_parts = str_split($hex, 2);
	    $return = '#';

	    foreach ($color_parts as $color) {
		$color   = hexdec($color); // Convert to decimal
		$color   = max(0,min(255,$color + $steps)); // Adjust color
		$return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
	    }

	    return $return;
	}
}
?>
