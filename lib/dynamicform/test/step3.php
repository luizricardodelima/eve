<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamicForm test</title>
</head>
<body>
<h1>DynamicForm test</h1>
<h2>Step 3 - Input validation and display</h2>
<pre>
<?php
require_once '../custominput.class.php';

var_dump($_POST);
$customInput = new CustomInput($_POST['s'], $_POST['c']);
var_dump($customInput);

?>
</pre>
<form method="post" action="step3.php" enctype="multipart/form-data">
<div style="background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #333; width: 80%; display: grid; grid-gap: 0.5em; grid-template-columns: 1fr;">
<?php
echo $customInput->outputControls('s', 'c');
?>
</div>
<button type="submit">Enviar</button>
</form>
</body>
</html>