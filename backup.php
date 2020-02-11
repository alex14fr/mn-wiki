<?php
include_once "conf/conf.php";
//session_start();
//if(!auth_isAdmin()) { die("E"); }
$nonceFile="ephemeral/.nonce-".substr(hash('sha256',$secret1),0,20);
if(file_exists($nonceFile)) {
	$perm=stat($nonceFile)['mode'] % 01000;
	if (!is_writable($nonceFile) || $perm!=0600) { 
		file_put_contents($nonceFile,""); 
		chmod($nonceFile,0600); 
		die("Etryagain");
	}
}

if(!empty($_POST['reset-nonce'])) {
	if(function_exists("openssl_pseudo_bytes")) {
		$nonce=sha1(openssl_pseudo_bytes(32));
	} else {
		$nonce = hash("sha256",$secret1.microtime().mt_rand());
	}
	file_put_contents($nonceFile, $nonce);
	chmod($nonceFile, 0600);
	print $nonce;
	exit;
}

if(!empty($_POST['kill-nonce'])) {
	file_put_contents($nonceFile, "");
	chmod($nonceFile, 0600);
	print 'ok';
	exit;
}
if(!empty($_POST['f'])) {
	if(filemtime($nonceFile)<time()-600) { die("nonce expired"); }
	$goodtok=hash_hmac("sha256", $_POST['f'], $secret2.file_get_contents($nonceFile));
	if(!hash_equals($goodtok, $_POST['tok'])) { die("E2"); }

	$prefix="/persist/";
	chdir($prefix);

	if($_POST['f']=='@manifest') {
		header("Content-type: text/plain; charset=utf8");
		print "# mtime\tname\tsize\n";
		passthru("find . -type f -exec stat -c '%Y\t%n\t%s' {} \; ");
	} else {
		$rp=realpath($_POST['f']);
		print $rp;
		if(strpos($rp, $prefix)!==0) { die("E3"); }
		print file_get_contents($rp);
	}
}


