<?php 
$sec=file_get_contents("/persist/data/secret1");
$mailAdmin=trim(file_get_contents("/persist/mascot21_upload/mail"));
$mail=basename(urldecode($_GET['mail']));
$tok=$_GET['tok'];
$allowed_file="/persist/mascot21_upload/allowed";
$upload_dir="/persist/mascot21_upload/$mail/";
mkdir($upload_dir);

if(!hash_equals(sha1($sec.$mail), $tok)) {
	print 'token error '; 
	exit;
} 

if(!in_array($mail,file($allowed_file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES))) {
	print 'not allowed'; exit;
}

if($_GET['fn']) {
	@mkdir($upload_dir);
	$str="";$str2="";
	$fnam=bin2hex(random_bytes(32));
	stream_copy_to_stream(fopen("php://input","r"),fopen($upload_dir.$fnam,"w"));
	file_put_contents($upload_dir.$fnam."_name", $_GET['fn']);
	$tok2=sha1($sec.'fil'.$_GET['mail'].'/'.$fnam);
	$str.="Upload of ".$_GET['fn'].". \n";
	$str2.="https://gdr-mascotnum.fr/21view.php?tok=".$tok2."&f=".urlencode($_GET['mail']."/".$fnam)."\n";
	print 'ok';
	$ff=popen("/usr/sbin/sendmail $mailAdmin","w");
	fwrite($ff,"subject: mascot21 upload ".$_GET['mail']."\n\n".$str."\n".$str2."\n");
	pclose($ff);
	exit;
}
?>
<!doctype html>
<meta name=viewport value="width=device-width">
<style>body { line-height: 1.7; font-size: 18px;  font-family:sans-serif;}</style>
<h2>MASCOT21 Days - Poster session - Upload files</h2>
The session poster of the MASCOT21 conference is shared in two parallel sessions on April, the 28th, organized as follows:
<ul>
<li>4:00-4:45 pm a poster blitz during which a video with all recorded presentations of the session will be presented,
<li>4:45-5:45 pm a 1 hour question and answer session during which, based on the a pdf support, you will defend your poster work.
</ul>

To prepare the session for the poster session of the MASCOT21 conference, we ask you to send to us a video recording containing your presentation (of 5 minutes maximum) before <b>April 20th</b>. You can send to us <b>one video file</b> (of you commenting your presentation, or a screen presentation with aural comments, or both), or <b>two synchronized video files</b> (one with you, the other one for your screen presentation). <p>

You can prepare the video files using your favorite movie recording application, or you can use <a href="21videorec.php?mail=<?php print $_GET['mail'] ?>&tok=<?php print $_GET['tok'] ?>">this video recording web application</a>.<p>

<b>We also need a PDF file</b> of your presentation that you will probably share as a support during audience's question session. <p>

Please use the following form to send each of your files (please keep each file under 80 MB) :
<form>
<input id=f type=file>
<input id=submit type=button value=Submit>
</form>
<p>
<div id=progress></div>
<p>
<h3>Your files</h3>
<ul>
<?php
$dr=opendir("/persist/mascot21_upload/$mail");
while($f=readdir($dr)) {
	if(! ($f=='.' || $f=='..' || strpos($f,"_name")!==false)) {
		$o=true;
		$tok2=sha1($sec."fil".urldecode("$mail%2F$f"));
		print "<li> <a href=21view.php?f=$mail%2F$f&tok=$tok2>".file_get_contents("/persist/mascot21_upload/$mail/$f"."_name")."</a> [<a href=21view.php?f=$mail%2F$f&tok=$tok2&del=1&mail=".urlencode($mail)."&backtok=$tok>Delete</a>]";
	}
}
if(!$o) { print "No file uploaded yet. "; }
?>
</ul>
<script>
var sendurl="21upload.php?mail=<?php print urlencode($mail); ?>&tok=<?php print urlencode($tok); ?>";
var xhr;

function updProgress(ev) {
	document.getElementById('progress').innerHTML='uploaded '+ev.loaded+' / '+ev.total+' bytes ('+Math.round(100*ev.loaded/ev.total)+' %)';
}

function updError(ev) {
	document.getElementById('progress').innerHTML='error : '+xhr.status;
}

function updOK(ev) {
	document.getElementById('progress').innerHTML='file upload successful';
	location.reload();
}

document.getElementById('submit').onclick=function(ev) {
	document.getElementById('progress').innerHTML='uploading...';
	var ff=document.getElementById('f');
	var x=ff.files[0];
	var sendurl2=sendurl+'&fn='+encodeURIComponent(x.name);
	var xhr=new XMLHttpRequest();
	xhr.upload.onprogress=updProgress;
	xhr.upload.onerror=updError;
	xhr.upload.onload=updOK;
	xhr.open('POST',sendurl2);
	xhr.send(x);
};
</script>

