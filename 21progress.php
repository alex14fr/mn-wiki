<?php
session_start();
$key = ini_get("session.upload_progress.prefix") . $_POST[ini_get("session.upload_progress.name")];
print_r($_SESSION);
?>

