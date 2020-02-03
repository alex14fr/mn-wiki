<?php
include_once "parse.php";

$fd=fopen("php://stdin","r");

while($l=fgets($fd)) {
	print parse_line($l);
}

