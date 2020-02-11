<?php
@include "backupsecret.php";
if(empty($secret) || strlen($secret)<20) { die("E"); }

if(!empty($_POST['f']) && !empty($_POST['tok']) && !empty($_POST['time'])) {
	$clntTime=preg_replace('/[^0-9]/','',$_POST['time']);
	if( abs($clntTime - time()) > 20 ) { die("desync"); }
	$hashok=hash_hmac("sha256", $_POST['f'], $secret.$clntTime);
	if(!hash_equals($hashok, $_POST['tok'])) { die("E"); }

	$prefix="/persist/";
	chdir($prefix);

	if($_POST['f']=='@manifest') {
		header("Content-type: text/plain; charset=utf8");
		print "# mtime\tname\tsize\n";
		passthru("find . -type f -exec stat -c '%Y\t%n\t%s' {} \; 2>&1");
	} else if($_POST['f']=='@update') {
		header("Content-type: text/plain; charset=utf8");
		passthru("./update_htdocs 2>&1");
		exit;
	} else {
		$rp=realpath($_POST['f']);
		if(strpos($rp, $prefix)!==0) { die("E3"); }
		readfile($rp);
		exit;
	}
}


print "E";
