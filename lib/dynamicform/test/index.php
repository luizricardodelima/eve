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
<h2>Step 1 - Structure creation </h2>
<?php

require_once '../dynamicform.class.php';
require_once '../dynamicformhelper.class.php';

// Test structure
$structureJSON = '
[
    {"type":"text","description":"This is a text field","unrestrict":true,"mandatory":true,"spec":null},
    {"type":"bigtext","description":"This is a big text field","unrestrict":true,"mandatory":true,"spec":{"min_words":2,"max_words":100}},
    {"type":"array","description":"This is an array field","unrestrict":true,"mandatory":true,"spec":{"items":["a","b","c"]}},
    {"type":"enum","description":"This is an enumeration field","unrestrict":true,"mandatory":true,"spec":{"items":["x","y","z"]}},
    {"type":"check","description":"This is a checkbox field","unrestrict":true,"mandatory":true,"spec":null},
    {"type":"file","description":"This is a file field","unrestrict":true,"mandatory":true,"spec":{"file_types":["application/pdf","image/jpeg","image/bmp","application/vnd.openxmlformats-officedocument.wordprocessingml.document","application/vnd.ms-powerpoint"],"max_size":42}}
]';
DynamicFormHelper::$locale = "pt_BR";
$customInput = new DynamicForm($structureJSON);

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

echo '$structureJSON ';
var_dump($structureJSON);

?>
</pre>

<form action="step2.php" method="post">
<?php echo $customInput->outputStructureTable('s', 'c'); ?>
<button>Submit</button>
</form>
</body>
</html>