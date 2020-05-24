<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVE Setup</title>
    <link rel="stylesheet" href="../style/style.css">
    <style>
    /*body { font-family: sans-serif; }*/
    .pnl { position: absolute; z-index: 0; left: 0; top: 0; width: 100%; min-height: 100%; display: flex; flex-wrap: wrap; justify-content: center;  align-items: center; padding: 1rem;}
    .opt { min-height: 16rem; width: 17em; overflow: visible; box-sizing: border-box; margin: 1rem;}
    .opt img {display: block; height: 50%; width: 50%; text-align: center; margin: 5% auto;}
    .opt label {font-size: 2em;}
    .modal {display: none; position: fixed; z-index: 2; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);}
    .modal_close_button {background-color:#333; color: white; float: right; border: 0; border-radius: 0; margin: 0 0 0 -1.6rem; width: 1.6rem; height: 1.6rem; padding: 0; font-size: 1.3rem; line-height: 1.3rem; }
    .modal_container {background-color: white; margin: 15% auto; border: 2px solid #333; width: 80%;}
    .modal_content {border: 20px auto; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr; max-width: 50em;}
    #db_check_info ul {list-style: none; padding: 0; font-weight: bold; }
    @media screen and (max-width: 975px) {.pnl {display: flex; flex-direction: column; flex-wrap: nowrap;}}
    </style>
</head>
<body>
    <div class="pnl">
    <div class="opt">
        <h1>Eve Setup</h1>
        <p>First edit <code>evedbconfig.php</code> file with the database connection settings.
        After, you can use database create and delete options. For security reasons, the
        database password will be asked again. </p>
        <div id="db_check_info"></div>
    </div>
    <button type="button" class="opt" onclick="database_create_dialog()"><img src="create.svg"/><label>Database create</label></button>
    <button type="button" class="opt" onclick="database_delete_dialog()"><img src="delete.svg"/><label>Database delete</label></button>
    </div>
    
    <!-- Database create options -->
	<div id="database_create_dialog" class="modal"><div class="modal_container">
	<button type="button" class="modal_close_button" onclick="this.parentNode.parentNode.style.display = 'none';">&times;</button>
    <!-- Begin of dialog content -->
    <div id="create_content_1" class="dialog_panel">
    <label for="db_password">Database password</label>
    <input  id="db_password" type="password"/>
    <label for="su_email">Superuser e-mail</label>
    <input  id="su_email" type="text"/>
    <label for="su_password">Superuser password</label>
    <input  id="su_password" type="text"/>
    <button onclick="database_create()" class="submit">Create</button>
    </div>
    <div id="create_content_2" style="display: none;" class="dialog_panel">
    <span style="width: 100%; text-align: center;"><img style="height: 10rem; width: 10rem;" src="../style/icons/loading.gif"/></span>
    </div>
    <div id="create_content_3" style="display: none;" class="dialog_panel">
    </div>
    <script>
        function database_create_dialog()
        {
            document.getElementById('create_content_1').style.display = 'grid';
            document.getElementById('create_content_2').style.display = 'none';
            document.getElementById('create_content_3').style.display = 'none';
            document.getElementById('database_create_dialog').style.display = 'block';
        }

        function database_create()
        {
            var data = new FormData();
            data.append('action', 'create');
            data.append('db_password', document.getElementById('db_password').value);
            data.append('su_email', document.getElementById('su_email').value);
            data.append('su_password', document.getElementById('su_password').value);
            document.getElementById('create_content_2').style.display = 'grid';
            document.getElementById('create_content_1').style.display = 'none';
            var xhr = new XMLHttpRequest();
			xhr.open('POST', 'service.php');
			xhr.onload = function() {
                if (xhr.status === 200)
					document.getElementById('create_content_3').innerHTML = xhr.responseText;
                else
					document.getElementById('create_content_3').innerHTML = '<p>HTTP Error ' + xhr.status + '</p>';
                document.getElementById('create_content_3').style.display = 'grid';
                document.getElementById('create_content_2').style.display = 'none';
                update_db_info();
            };
            xhr.send(data);
        }
    </script>
    <!-- End of dialog content -->    
    </div></div>
    
    <!-- Database delete options -->
	<div id="database_delete_dialog" class="modal"><div class="modal_container">
	<button type="button" class="modal_close_button" onclick="this.parentNode.parentNode.style.display = 'none';">&times;</button>
    <!-- Begin of dialog content -->
    <div id="delete_content_1" class="dialog_panel">
    <label for="db_password_del">Database password</label>
    <input  id="db_password_del" type="password"/>
    <button onclick="database_delete()" class="submit">Delete</button>
    </div>
    <div id="delete_content_2" style="display: none;" class="dialog_panel">
    <span style="width: 100%; text-align: center;"><img style="height: 10rem; width: 10rem;" src="../style/icons/loading.gif"/></span>
    </div>
    <div id="delete_content_3" style="display: none;" class="dialog_panel">
    <p>Fim!!!</p>
    </div>
    <script>
        function database_delete_dialog()
        {
            document.getElementById('delete_content_1').style.display = 'grid';
            document.getElementById('delete_content_2').style.display = 'none';
            document.getElementById('delete_content_3').style.display = 'none';
            document.getElementById('database_delete_dialog').style.display = 'block';
        }

        function database_delete()
        {
            var data = new FormData();
            data.append('action', 'delete');
            data.append('db_password', document.getElementById('db_password_del').value);
            document.getElementById('delete_content_2').style.display = 'grid';
            document.getElementById('delete_content_1').style.display = 'none';
            var xhr = new XMLHttpRequest();
			xhr.open('POST', 'service.php');
			xhr.onload = function() {
                if (xhr.status === 200)
					document.getElementById('delete_content_3').innerHTML = xhr.responseText;
                else
					document.getElementById('delete_content_3').innerHTML = '<p>HTTP Error ' + xhr.status + '</p>';
                document.getElementById('delete_content_3').style.display = 'grid';
                document.getElementById('delete_content_2').style.display = 'none';
                update_db_info();
            };
            xhr.send(data);
        }
    </script>
    <!-- End of dialog content -->    
    </div></div>

    <script>
    function update_db_info()
    {
        var data = new FormData();
        data.append('action', 'check');
        var xhr = new XMLHttpRequest();
    	xhr.open('POST', 'service.php');
		xhr.onload = function() {
            if (xhr.status === 200)
				document.getElementById('db_check_info').innerHTML = xhr.responseText;
            else
				document.getElementById('db_check_info').innerHTML = '<p>HTTP Error on checking database</p>';
        };
        xhr.send(data);
    }
    update_db_info();
    </script>
</body>
</html>