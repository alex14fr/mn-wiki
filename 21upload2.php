<?php 
$sec=file_get_contents("/persist/data/secret1");
$mailAdmin=trim(file_get_contents("/persist/mascot21_upload/mail"));
$mail=basename(urldecode($_GET['mail']));
$tok=$_GET['tok'];
$allowed_file="/persist/mascot21_upload/allowed";
$upload_dir="/persist/mascot21_upload/$mail/";

if(!hash_equals(sha1($sec.$mail), $tok)) {
	print 'token error '; 
	exit;
} 

if(!in_array($mail,file($allowed_file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES))) {
	print 'not allowed'; exit;
}

session_start();

if($_GET['fn']) {
	@mkdir($upload_dir);
	$str="";$str2="";
	$fnam=bin2hex(random_bytes(32));
	stream_copy_to_stream(fopen("php://input","r"),fopen($upload_dir.$fnam,"w"));
	file_put_contents($upload_dir.$fnam."_name", $_GET['fn']);
	$str.="Upload of ".$_FILES['f']['name'][$key]." succeeded. \n";
	$str2.="https://gdr-mascotnum.fr/21admin.php?f=".urlencode($_GET['mail']."/".$fnam)."\n";
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
You can prepare the video files using your favorite movie recording application, or you can use <a href="21videorec.php?mail=<?php print $_GET['mail'] ?>&tok=<?php print $_GET['tok'] ?>">this video recording web application</a>.<p>
Please use the following form to send your file:
<form>
<input id=f type=file>
<input id=submit value=Submit>
</form>
<div id=progress></div>
<script>
var sendurl="/21upload2.php?mail=<?php print urlencode($mail); ?>&tok=<?php print urlencode($tok); ?>";
function updProgress(ev) {
	document.getElementById('progress').innerHTML=ev.loaded+' / '+ev.total+' bytes';
}

function updError(ev) {
	document.getElementById('progress').innerHTML='error';
}

function updOK(ev) {
	document.getElementById('progress').innerHTML='file upload successful';
}

document.getElementById('submit').onclick=function(ev) {
	var ff=document.getElementById('f');
	var x=ff.files[0];
	var sendurl2=sendurl+'&fn='+encodeURIComponent(x.name);
	var xhr=new XMLHttpRequest();
	xhr.onprogress=updProgress;
	xhr.onerror=updError;
	xhr.onload=updOK;
	xhr.open('POST',sendurl2);
	xhr.send(x);
};
</script>

