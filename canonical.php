<?php
if(file_exists("conf/canonical.php")) {
	include_once "conf/canonical.php";

	if($_SERVER["HTTP_X_FORWARDED_HOST"]!=$canonicalHost) {
			  $destination="$canonicalProto://$canonicalHost".$_SERVER["REQUEST_URI"];
			  header("HTTP/1.1 301 Moved Permanently");
			  header("Location: $destination");
			  print "<h1>HTTP/1.1 301 Moved Permanently</h1><a href=\"$destination\">$destination</a>";
			  exit;
	}
}
