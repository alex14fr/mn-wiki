<?php
if($_POST['key']!=md5(file_get_contents("/persist/data/secret1"))) {
	print "error";
	exit;
}

if($_GET['f']) {
	$f=urldecode($_GET['f']);
	header('content-type: application/octet-stream');
	header('content-disposition: attachment;filename="'.file_get_contents('/persist/mascot21_upload/'.$f.'_name'));
	readfile('/persist/mascot21_upload/'.$f);
}

if($_GET['l']) {
	passthru("ls -lhR /persist/mascot21_upload");
}

if($_GET['m']) {
	foreach(file('/persist/mascot21_upload/allowed',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $mail) {
		print "https://gdr-mascotnum.fr/21upload.php?mail=".urlencode($mail)."&tok=".sha1(file_get_contents("/persist/data/secret1").$mail)."\n";

	}
}
