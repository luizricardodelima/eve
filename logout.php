<?php
session_start();
session_destroy();
require_once 'eve.class.php';

$eve = new Eve();
$eve->output_redirect_page("userarea.php");
?>
