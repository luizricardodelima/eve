<?php
require_once 'evedbconfig.php';
require_once 'evepageservice.class.php';
class Eve
{
	public $DBPref;
	public $mysqli;
	private $settings = array();
	private $dictionary = null;
	
	/**
	 * $setupmode = true prevents this object to redirect to the setup screen when the
	 * database connection fails. It's used in setup to load all the other capabilities
	 * of Eve object even when the database connection fails.
	 */
	function __construct($setupmode = false)
	{
		if ($setupmode) error_reporting(0);

		// Establishing Database connection	
		$this->mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
		if(mysqli_connect_errno() && !$setupmode)
		{
			$this->output_redirect_page($this->sysurl().'/setup');
		}
		else
		{
			$this->mysqli->set_charset("utf8");
			$this->DBPref = EveDBConfig::$prefix;
		}
	}

	// translate key - dictionary
	function _($key, $placeholders = null)
	{
		if ($this->dictionary === null) $this->load_dictionary();
		$translation = isset($this->dictionary[$key]) ? $this->dictionary[$key] : $key;
		if (is_array($placeholders)) foreach($placeholders as $key => $value)
            $translation = str_replace($key, $value, $translation);
		return $translation;
	}

	function load_dictionary()
	{
		$lang_file = __DIR__ . '/g11n/' . $this->getSetting('system_locale') . '.json';
		// Default english dictionary
		if (!file_exists($lang_file))
		{
      			$lang_file = __DIR__ . '/g11n/' . 'en.json';
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

	/**
	 * $features is an array with additional features for page rendering. Since they usually require
	 * lots of javascript code, they don't need to be loaded for a faster, simpler code. The features
	 * supported are: 
	 * 'sort-table'; in the code, th headers need to have the property 
	 *  onclick="sortColumn(tableid,int,boolean)", where tableid is the id of the bable which will
	 *  be sorted, int is the column position (0 .. n-1) and boolean indicates whether it is numeric
	 *  or not
	 * 'wysiwyg-editor': in the code, textarea inputs need to have the property class="htmleditor"
	 */
	function output_html_header($features = []) 
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
		<script src="<?php echo $this->find_relative_path();?>lib/jquery/jquery-3.5.0.min.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo $this->find_relative_path();?>style/style.css"/>
		
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
			echo '<img src="upload/style/header.png"/>';
		echo "</div>";
		
		if ($this->getSetting('show_content_menu_and_pages'))
		{
		?>		
		<nav id="menu">
			<ul>
			<?php
			$evePageService = new EvePageService($this);
			foreach ($evePageService->page_list(true) as $page) echo "<li><a href=\"{$this->find_relative_path()}index.php?p={$page['id']}\">{$page['title']}</a></li>";
			echo "<li><a href=\"{$this->find_relative_path()}userarea.php\">{$this->getSetting('userarea_label')}</a></li>";
			?>
			</ul>
		</nav>
		<?php
		}

		if (in_array('sort-table', $features))
		{
			?>
			<script src="lib/sorttable/sorttable.js"></script>
			<?php
		}

		if (in_array('wysiwyg-editor', $features))
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
				toolbar: 'undo redo | styleselect | bold italic underline fontsizeselect superscript subscript | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | preview media | forecolor backcolor emoticons | code fullscreen',
				image_advtab: true,
				valid_elements: '*[*]'
			});
			</script>
			<?php
		}

		echo "<div id=\"main_content\">";
	}

	function output_html_footer() 
	{
		?>
		</div>
		<?php
		// TODO G11N
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
		echo "<div class=\"$class\"><button type=\"button\" class=\"msg_close\" onclick=\"this.parentNode.style.display='none';\">X</button><span class=\"msg_icon\">$icon</span><span class=\"msg_body\">$text</span></div>";
	}

	function output_error_list_message($message_array)
	{
		$error_message = $this->_('common.message.error.list');
		foreach($message_array as $message) $error_message .="<li>$message</li>";
		$this->output_message('error', $error_message);
	}
	
	/**
	 * Function for correctly displaying the messages output by services,
	 * showing error or success messages according to the case.
	 */
	function output_service_message($message)
	{
		if (strpos($message, 'success') === false)
			$this->output_error_message($message);
		else
			$this->output_success_message($message);
	}

	function output_error_message($message)
	{
		$this->output_message('error', $message);
	}

	function output_success_message($message)
	{
		$this->output_message('success', $message);
	}

	/** 
	 * This function outputs the navigation bar of the user area. It receives pairs of arguments,
	 * a label and its corresponding link. If the label does not have a link, this link should be
	 * a null argument. This throws an error if an odd number of arguments is passed.
	 * 
	 * @deprecated use output_navigation instead, because it receives an array and can be better modelled
	 */
	function output_navigation_bar()
	{
		$array = array();

		for ($i = 0, $n = 0; $i < func_num_args(); $i = $i+2, $n++)
		{
			if (is_null(func_get_arg($i+1)))
				$array[$n] = func_get_arg($i);
			else
				$array[$n] = "<a href=\"".func_get_arg($i+1)."\">".func_get_arg($i)."</a>";
		} 
		echo '<div id="navigation_bar">'.implode(' &rarr; ', $array).'</div>';
	}

	function output_navigation($navigation_array)
	{
		$items = array();		
		if (is_array($navigation_array)) foreach($navigation_array as $description => $link)
		{
			if ($link === null) $items[] = $description;
			else $items[] = "<a href=\"$link\">$description</a>";
		}
		echo '<div id="navigation_bar">'.implode(' &rarr; ', $items).'</div>';
	}

	function output_big_goto_button($label, $icon_code, $location)
	{
		echo "<button class=\"big\" type=\"button\" onclick=\"window.location.href='$location';\">";
		echo "<div class=\"buttonicon\">$icon_code</div><div class=\"buttonlabel\">{$this->_($label)}</div>";
		echo "</button>";
	}

	// TODO: output a "proper" error page
	function output_error_page($message, $show_back_button = true) 
	{
		$this->output_html_header();
		$this->output_error_message($message);		
		if ($show_back_button) 
		{
			$message = $this->_('common.action.back');
			echo "<button type=\"button\" onclick=\"history.back()\">$message</a>";
		}
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

	/**
	 * Returns the base url of system. This function relies on the fact that eve.class.php is
	 * located on the base folder of the system
	 */
	function sysurl()
	{
		$server_name = isset($_SERVER['HTTP_X_FORWARDED_SERVER']) ?  $_SERVER['HTTP_X_FORWARDED_SERVER'] : $_SERVER["SERVER_NAME"];
		$path = substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']));
		return sprintf("%s://%s/%s", isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', $server_name, $path);
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
    private function find_relative_path()
    {
		$frompath = dirname($_SERVER['SCRIPT_FILENAME']);
		$topath = dirname(__FILE__);
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
        return $relpath;
        // Strip last separator
        // return substr($relpath, 0, -1);
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
