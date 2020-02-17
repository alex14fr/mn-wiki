<?php
/**

BSD 2-Clause License

Copyright (c) 2020, Alexandre Janon <alex14fr@gmail.com>
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/


include_once "utils.php";
sendCsp();
canonical();
include_once "auth.php";
include_once "conf/conf.php";
if(!isset($_SESSION)) session_start();
if(!auth_isContrib()) {
	die("not authorized yet");
}
$ip=$clientIp." (".gethostbyaddr($clientIp).")";

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
	chk_xtok("upload");
	if(empty($desti)) { die("error : empty destination filename"); }
	if($_FILES['fich']['error']) {
		die("upload error ".$_FILES['fich']['error']);
	}
	if(file_exists("$mediaDir/$desti")) {
		$oldcnt=file_get_contents("$mediaDir/$desti");
		if(!$oldcnt) { die("error : can't read file to be overwritten - please change destination filename"); }
		if(!file_put_contents("$mediaDir/.attic/chg-".md5($oldcnt)."-$dd",$oldcnt)) {
			die("error : can't save file to be overwritten - please change destination filename");
		}
	}
	if(!move_uploaded_file($_FILES["fich"]["tmp_name"], "$mediaDir/$desti")) 
		die("move_uploaded_file error");
	sendNotify("change",'File added : '.$desti, "File\r\n\r\n   $baseUrl$mediaPrefix/$desti\r\n\r\n has been added by ".$_SESSION['auth_user']. ' ; IP='.$ip.'). ');
	$msg="File {{".$desti."}} added.";
}

if(!empty($_POST['delete'])) {
	$dd=san_filename($_POST['delete']);	
	if(empty($dd)) { die("empty filename"); }
	chk_xtok("upload");
	if(substr($dd,0,1) != '.') {
		$oldcnt=file_get_contents("$mediaDir/$dd");
		if(!$oldcnt) { die("can't read file to be deleted"); }
		$newname="$mediaDir/.attic/del-".md5($oldcnt)."-$dd";
		if(!rename("$mediaDir/$dd", $newname)) { die("error during deletion"); }
		touch($newname);
		sendNotify("change",'File deleted : '.$dd, 'File '.$dd.' has been deleted by '.$_SESSION['auth_user']. ' ; IP='.$ip.'). ');
		$msg="File $dd deleted.";
	}
}


gen_xtok("upload");
?>
<!doctype html>
<body>
<?php if(!empty($msg)) { print "<b>$msg</b>"; } ?>
<h2>File manager</h2>
<form enctype=multipart/form-data method=post>
<?php print pr_xtok("upload"); ?>
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
<form method=post>
<?php print pr_xtok("upload"); ?>
<?php
$d=scandir('data/media/');
foreach($d as $dd) {
	if(substr($dd,0,1) != '.')
		print "<li><tt>{{".$dd."}}</tt> <a href=\"$mediaDir/$dd\" target=_new>View</a> <button type=submit name=delete value=\"$dd\">Delete</button>";
}
?>
</ul>
</body>
</html>
