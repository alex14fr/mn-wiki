<?php
$mailAdmin=trim(file_get_contents("/persist/mascot21_upload/mail"));
$f=urldecode($_GET['f']);
if(!hash_equals(sha1(file_get_contents("/persist/data/secret1")."fil".$f),$_GET['tok'])) {
	print 'tok error';
	exit;
}
if(strpos($f,"..")!==false) { print 'path error'; exit; }
if($_GET['del']) { 
	unlink('/persist/mascot21_upload/'.$f.'_name'); 
	unlink('/persist/mascot21_upload/'.$f); 
	$ff=popen("/usr/sbin/sendmail $mailAdmin","w");
	fwrite($ff,"subject: mascot21 delete ".$_GET['mail']."\n\nDeleted $f.\n");
	pclose($ff);
	header("Location: 21upload.php?mail=".$_GET['mail']."&tok=".$_GET['backtok']); 
	print 'ok deleted'; 
	exit; 
}
header('content-type: application/octet-stream');
header('content-disposition: attachment;filename="'.file_get_contents('/persist/mascot21_upload/'.$f.'_name').'"');
readfile('/persist/mascot21_upload/'.$f);


