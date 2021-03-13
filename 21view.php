<?php
$f=urldecode($_GET['f']);
if(!hash_equals(sha1(file_get_contents("/persist/data/secret1")."fil".$f),$_GET['tok'])) {
	print 'tok error';
	exit;
}
header('content-type: application/octet-stream');
header('content-disposition: attachment;filename="'.file_get_contents('/persist/mascot21_upload/'.$f.'_name').'"');
readfile('/persist/mascot21_upload/'.$f);


