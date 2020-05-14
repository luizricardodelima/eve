<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>DynamicForm test</title>
</head>
<body>
<h1>DynamicForm test</h1>
<h2>Step 2 - Input controls</h2>
<?php

require_once '../dynamicform.class.php';
require_once '../dynamicformhelper.class.php';
DynamicFormHelper::$locale = "pt_BR";
$customInput = new DynamicForm($_POST['s']);

?>
<script>
function toggleShow()
{
  var x = document.getElementById("variables_area");
  if (x.style.display == "none") x.style.display = "block";
  else x.style.display = "none";
}
</script>
<div class="toolbar">
<button onclick="toggleShow()">Variables</button>
</div>
<pre id="variables_area">
<?php

echo '$_POST ';
var_dump($_POST);
echo '$customInput ';
var_dump($customInput);

?>
</pre>
<form method="post" action="step3.php" enctype="multipart/form-data">
<div id="controls">
<?php echo $customInput->outputControls('s', 'c'); ?>
<button type="submit">Enviar</button>
</div>
</form>
</body>
</html>