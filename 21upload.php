<?php 
$sec=file_get_contents("/persist/data/secret1");
$mailAdmin=trim(file_get_contents("/persist/mascot21_upload/mail"));
$mail=basename(urldecode($_GET['mail']));
$tok=$_GET['tok'];
$allowed_file="/persist/mascot21_upload/allowed";
$upload_dir="/persist/mascot21_upload/$mail/";

if(!hash_equals(sha1($sec.$mail), $tok)) {
	print 'token error '; 
//	print sha1($sec.$mail);
	exit;
} 

if(!in_array($mail,file($allowed_file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES))) {
	print 'not allowed'; exit;
}

session_start();

if($_FILES['f']) {
	@mkdir($upload_dir);
	$str="";$str2="";
	foreach($_FILES['f']['error'] as $key=>$err) {
		if($err==UPLOAD_ERR_OK) {
			$fnam=bin2hex(random_bytes(32));
			file_put_contents($upload_dir.$fnam."_name", $_FILES['f']['name'][$key]);
			$muf=move_uploaded_file($_FILES['f']['tmp_name'][$key],$upload_dir.$fnam);
			if(!$muf) 
				$str.="Move error during upload of ".$_FILES['f']['name'][$key]."\n";
			else {
				$str.="Upload of ".$_FILES['f']['name'][$key]." succeeded. \n";
				$str2.="https://gdr-mascotnum.fr/21admin.php?f=".urlencode($_GET['mail']."/".$fnam)."\n";
			}
		} else if($err != 4)
			$str.="Error during upload of ".$_FILES['f']['name'][$key]." : ".$err."\n";
	}
	print "<b>".nl2br($str)."</b><hr>";
	$ff=popen("/usr/sbin/sendmail $mailAdmin","w");
	fwrite($ff,"subject: mascot21 upload ".$_GET['mail']."\n\n".$str."\n".$str2."\n");
	pclose($ff);

}


?>
<!doctype html>
<meta name=viewport value="width=device-width">
<style>body { line-height: 1.7; font-size: 18px;  font-family:sans-serif;}</style>
<h2>MASCOT21 Days - Poster session - Upload files</h2>
To prepare for the poster session of the MASCOT21 conference, we ask you to send to us a video recording containing your presentation (of <i>5 minutes maximum</i>) before <b>XXXX</b>.<p>
You can send to us one video file (of you presenting your poster, or a screen presentation with aural comments, or both), or two synchronized video files (one with you, the other one for your screen presentation). You can <b>(must ?)</b> also send a PDF file of your poster that will serve as a support during audience's questions session.<p>
You can prepare the video files using your favorite movie recording application, or you can use <a href="21record.php?mail=<?php print $_GET['mail'] ?>&tok=<?php print $_GET['tok'] ?>">this video recording web application</a>.<p>
Please use the following form to send your file(s):
<form id=form enctype=multipart/form-data method=post>
<input type=file name=f[]><br>
<input type=file name=f[]><br>
<input type=file name=f[]><br>
<input type=submit value=Upload>
<input type="hidden" name="<?php echo ini_get("session.upload_progress.name"); ?>" value="1">
</form>
<i>You may send multiple files at once provided the overall size of the files is not too large; if so, you can use this form multiple times for each file.</i>
<div id=progress></div>
<script>
/*
document.getElementById('form').onsubmit=function(ev) {
	setInterval( function() {  
		fetch('/21progress.php').then(resp=>resp.text())
										.then(data=>document.getElementById('progress').innerHTML=data);
		}, 2000); 
} */
</script>

