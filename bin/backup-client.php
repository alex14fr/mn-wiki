<?php
$nonceFile='/tmp/.nonce_'.getmyuid();
$url=$argv[1];
$sec2=$argv[2];
$f=$argv[3];

if($f=='reset-nonce') {
	if(!file_put_contents($nonceFile, file_get_contents($url."?reset-nonce=1"))) die();
	chmod($nonceFile,0600);
	print "ok\n";
	exit;
}

if($f=='kill-nonce') {
	print file_get_contents($url."?kill-nonce=1")."\n";
	unlink($nonceFile);
}

else {
	$tok=hash_hmac("sha256",$f,$sec2.file_get_contents($nonceFile));
	print file_get_contents($url."?f=$f&tok=$tok");

}
