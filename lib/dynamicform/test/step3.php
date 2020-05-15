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
<h2>Step 3 - Input validation and display</h2>
<?php

require_once '../dynamicform.class.php';
require_once '../dynamicformhelper.class.php';
//DynamicFormHelper::$locale = "pt_BR";
$files = (isset($_FILES) && isset($_FILES['c'])) ? $_FILES['c'] : null;
$customInput = new DynamicForm($_POST['s'], $_POST['c'], $files, 'upload/');
$validationErrors = $customInput->validate();

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
echo '$_FILES ';
var_dump($_FILES);
echo '$customInput ';
var_dump($customInput);
echo '$validationErrors ';
var_dump($validationErrors);

?>
</pre>
<?php if (empty($validationErrors)) { ?>
  <h3>Inactive form</h3>
  <form method="post" action="step3.php" enctype="multipart/form-data">
  <div id="controls">
  <?php echo $customInput->outputControls('s', 'c', false); ?>
  <button type="submit">Enviar</button>
  </div>
  </form>
  <h3>Table of contents - all fields</h3>
  <?php echo $customInput->getHtmlFormattedContent(false, "tablename", "tableid"); ?>
  <h3>Table of contents - only unrestrict fields</h3>
  <?php echo $customInput->getHtmlFormattedContent(true, "tablename", "tableid"); ?>
<?php } else { ?>
  <h3>Active form - with validation error messages</h3>
  <!-- TODO Validation error messages on form -->
  <form method="post" action="step3.php" enctype="multipart/form-data">
  <div id="controls">
  <?php echo $customInput->outputControls('s', 'c'); ?>
  <button type="submit">Enviar</button>
  </div>
  </form>
<?php } ?>
</body>
</html>