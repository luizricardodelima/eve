<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamicForm test</title>
</head>
<body>
<h1>DynamicForm test</h1>
<h2>Step 1 - Structure creation </h2>
<form action="step2.php" method="post">
<?php

require_once '../custominput.class.php';

$structureJSON = '[{"type":"text","description":"This is a text field","unrestrict":true,"mandatory":true,"spec":null},{"type":"bigtext","description":"This is a big text field","unrestrict":true,"mandatory":true,"spec":{"min_words":2,"max_words":100}},{"type":"array","description":"This is an array field","unrestrict":true,"mandatory":true,"spec":{"items":["a","b","c"]}},{"type":"enum","description":"This is an enumeration field","unrestrict":true,"mandatory":true,"spec":{"items":["x","y","z"]}},{"type":"check","description":"This is a checkbox field","unrestrict":true,"mandatory":true,"spec":null},{"type":"file","description":"This is a file field","unrestrict":true,"mandatory":true,"spec":{"file_types":["application/pdf","image/jpeg","image/bmp","application/vnd.openxmlformats-officedocument.wordprocessingml.document","application/vnd.ms-powerpoint"],"max_size":42}}]';
//$structureJSON = null;

$customInput = new CustomInput($structureJSON);
echo $customInput->outputStructureTable('s', 'c');

?>
<button>Submit</button>
</form>
</body>
</html>