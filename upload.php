<?php
include_once "utils.php";
canonical();
include_once "auth.php";
include_once "conf/conf.php";
session_start();
if(!auth_isContrib()) {
	die("not authorized yet");
}
$ip=$_SERVER["REMOTE_ADDR"];
$ip=$ip." (".gethostbyaddr($ip).")";

function check_allowed($fn) {
	global $ext_ok;
	if(strpos($fn,".")===false) { die("error: empty extension not allowed"); }
	$ext=substr($fn,strrpos($fn,".")+1);
	if(!in_array($ext,$ext_ok)) {
		die("error : extension $ext not allowed");
	}
}

if(!empty($_FILES['fich'])) {
	$desti=basename($_FILES["fich"]["name"]);
	if($_POST['nom'])
		$desti=$_POST['nom'];
	$desti=san_filename($desti);
	check_allowed($desti);
	chk_xtok();
	if(!move_uploaded_file($_FILES["fich"]["tmp_name"], "$mediaDir/$desti")) 
		die("upload error");
	sendNotify("change",'File added : '.$desti, "File\r\n\r\n   $baseUrl/$mediaPrefix/$desti\r\n\r\n has been added by ".$_SESSION['auth_user']. ' ; IP='.$ip.'). ','' );
}

if(!empty($_GET['delete'])) {
	$dd=san_filename($_GET['delete']);	
	chk_xtok();
	if(substr($dd,0,1) != '.') {
		sendNotify("change",'File deleted : '.$dd, 'File '.$dd.' has been deleted by '.$_SESSION['auth_user']. ' ; IP='.$ip.'). ','');
		unlink("$mediaDir/$dd");
	}
}


gen_xtok();
?>
<!doctype html>
<body>
<h2>File manager</h2>
<form enctype='multipart/form-data' method='post'>
<?php print pr_xtok(); ?>
<label>
File :
<input type="file" name="fich">
</label>
</div>
<div>
<label>
Name (leave empty for default name) : 
<input name="nom">
</div>
<input type=submit value=Send>
</form>
<h2>File list</h2>
<ul>
<?php
$d=scandir('data/media/');
foreach($d as $dd) {
	if(substr($dd,0,1) != '.')
		print "<li><tt>{{".$dd."}}</tt> <a href=\"$mediaDir/$dd\" target=_new>View</a> <a href=\"?delete=$dd&xtok=".$_SESSION['xtok']."\">Delete</a>";
}
?>
</ul>
</body>
</html>
