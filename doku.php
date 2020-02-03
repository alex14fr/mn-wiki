<?php
error_reporting(E_STRICT|E_ALL);
include_once "parse.php";

if(!empty($_GET['do']) && $_GET['do']=="login") {
}


if(empty($_GET['id'])) $_GET['id']='index';
print render_page_full($_GET['id']);

