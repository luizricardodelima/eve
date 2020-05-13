<!DOCTYPE html>
<html>
<head>
<title>EVE Setup</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<style>
body { font-family: sans-serif; }
.error { color: red; }
.success { color: #00B030; }
</style>
</head>
<body>
<h1>EVE Setup</h1>
<p>You can use the options options below after editing <code>evedbconfig.php</code> file with the proper database settings. For security reasons, the password for database connection will be asked again on database delete and database create options.</p>
<?php
require_once '../evedbconfig.php';
require_once 'dbcreate4.php';
if (isset($_POST['action']))
{
	// TODO: Clean up redundant code. (database connection check)
	switch ($_POST['action'])
	{
		case "database_check":
			//Checking connection
			$link = mysqli_connect(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
			if (!$link)
			{
				echo "<p class=\"error\">".date("c")." ERROR: Unable to connect to MySQL. </p>";
				echo "<ul class=\"error\"><li>Debugging errno: " . mysqli_connect_errno(). "</li>";
				echo "<li>Debugging error: " . mysqli_connect_error()."</li></ul>";
			}
			else
				echo "<p class=\"success\">".date("c")." Success on establishing database connection</p>";
		break;		
		case "database_create":
			//This variable indicates if there are setup errors or not
			$setup_errors = 0;

			//Checking connection
			$link = mysqli_connect(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
			if (!$link)
			{
				echo "<p class=\"error\">".date("c")." ERROR: Unable to connect to MySQL. </p>";
				echo "<ul class=\"error\"><li>Debugging errno: " . mysqli_connect_errno(). "</li>";
				echo "<li>Debugging error: " . mysqli_connect_error()."</li></ul>";
				$setup_errors++;
			}
			else
				echo "<p class=\"success\">".date("c")." Success on establishing database connection</p>";
			
			//Checking if password provided is equal to the password stored in evedbconfig.php
			if ($_POST['database_password'] != EveDBConfig::$password)
			{
				echo "<p class=\"error\">".date("c")." ERROR: Password provided is not the same as the one provided in evedbconfig.php.</p>";
				$setup_errors++;
			}			

			//Checking username and password
			if (trim($_POST['admin_screenname']) == "") 
			{
				echo "<p class=\"error\">".date("c")." ERROR: Admin email cannot be blank.</p>";
				$setup_errors++;
			}
			else if (!filter_var($_POST['admin_screenname'], FILTER_VALIDATE_EMAIL))
			{
				echo "<p class=\"error\">".date("c")." ERROR: Admin email is invalid.</p>";
				$setup_errors++;
			}
			if (trim($_POST['admin_password']) == "")
			{
				echo "<p class=\"error\">".date("c")." ERROR: Admin password cannot be blank.</p>";
				$setup_errors++;
			}

			// If there are not errors, creating database
			if (!$setup_errors)
			{
				$errors =  create_database_4($_POST['admin_screenname'], $_POST['admin_password']);
				echo "<p class=\"success\">".date("c")." Creating database:</p>";
				if (empty($errors))
					echo "<p class=\"success\">".date("c")."&nbsp; Success on creating database.</p>";
				else
				{
					echo "<p class=\"error\">".date("c")." &nbsp;&nbsp; Errors on creating database:</p>";
					echo "<pre>"; print_r($errors); echo"</pre>";
				}
			}
				
		break;
		case "database_erase":
			$setup_errors = 0;
			//Checking connection
			$link = mysqli_connect(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
			if (!$link)
			{
				echo "<p class=\"error\">".date("c")." ERROR: Unable to connect to MySQL. </p>";
				echo "<ul class=\"error\"><li>Debugging errno: " . mysqli_connect_errno(). "</li>";
				echo "<li>Debugging error: " . mysqli_connect_error()."</li></ul>";
				$setup_errors++;
			}
			else
				echo "<p class=\"success\">".date("c")." Success on establishing database connection</p>";

			//Checking if password provided is equal to the password stored in evedbconfig.php
			if ($_POST['database_password'] != EveDBConfig::$password)
			{
				echo "<p class=\"error\">".date("c")." ERROR: Password provided is not the same as the one provided in evedbconfig.php.</p>";
				$setup_errors++;
			}
			
			// If there are not errors, erase the database
			if (!$setup_errors)
			{
				$errors = erase_database_4();
				if (empty($errors))
					echo "<p class=\"success\">".date("c")." Success on erasing database.</p>";
				else
				{
					echo "<p class=\"error\">".date("c")."Error on erasing database: </p>";
					echo "<pre>"; print_r($errors); echo"</pre>";
				}
					
				
				
			}
				
		break;
	}
}
	// No action performed. Displaying setup options
	?>
	<h2>Step 1. Check the database connection</h2>
	<form method="post">
	<input type="hidden" name="action" value="database_check">
	<button type="submit">Database check</button>
	</form>

	<script>
	function database_create()
	{
		var database_password = prompt("In order to continue, please insert the password for the database connection.");
		if (database_password != null)
		{
			document.getElementById('ipt_database_create_password').value=database_password;
			document.getElementById('frm_database_create').submit();
		}
		return false;
	}
	</script>

	<h2>Step 2. Create the database structure</h2>
	<p>Please provide a valid e-mail address, which will be used as the screenname for the system administrator. Also provide a password for this user.</p>
	<form method="post" id="frm_database_create">
	<?php
		// Retrieving values passed for displaying them again on screen
		$admin_screennname = isset($_POST['admin_screenname']) ? $_POST['admin_screenname'] : '';
		$admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
	?>
	<label for="admin_screenname_ipt">Admin email (login)</label>
	<input id="admin_screenname_ipt" type="text" name="admin_screenname" value="<?php echo $admin_screennname;?>"/>
	<label for="admin_password_ipt">Admin password</label>
	<input id="admin_password_ipt" type="text" name="admin_password" value="<?php echo $admin_password;?>"/>
	<input type="hidden" name="action" value="database_create">
	<input type="hidden" name="database_password" id="ipt_database_create_password" value="">
	<button type="button" onclick="database_create()">Database create</button>
	</form>

	
	<script>
	function database_erase()
	{
		var database_password = prompt("In order to continue, please insert the password for the database connection.");
		if (database_password != null)
		{
			document.getElementById('ipt_database_erase_password').value=database_password;
			document.getElementById('frm_database_erase').submit();
		}
		return false;
	}
	</script>
	<h2>Advanced. Delete the database structure</h2>
	<p>Use the option below if you need to delete all the tables from the database (created in the step 2).</p>
	<form method="post" id="frm_database_erase">
	<input type="hidden" name="action" value="database_erase">
	<input type="hidden" name="database_password" id="ipt_database_erase_password" value="">
	<button type="button" onclick="database_erase()">Database erase</button>
	</form>
	<?php

?>
</body>
</html>
