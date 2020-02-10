<?php
include_once "utils.php";
include_once "auth.php";
session_start();
if(!auth_isAdmin()) { die("E"); }
if(!is_readable($pwdFile) || !is_writable($pwdFile)) { die("EE"); }

if(!empty($_POST['newpf'])) {
	chk_xtok();
	$oldpf=file_get_contents($pwdFile);
	$hashold=hash("sha256",$oldpf);
	if($hashold!=$_POST['hashold']) { die("error: race condition on passwd file"); }
	$newpf=trim($_POST['newpf'])."\n";
	file_put_contents($pwdFile,$newpf);
	if(!auth_isAdmin()) {
		file_put_contents($pwdFile,$oldpf);
		die("error: lock out");
	}
	print "OK <a href=index.php>Back</a><p>";
}

gen_xtok();
print "<form method=post><input type=hidden name=hashold value=".
	hash("sha256",file_get_contents($pwdFile)).">".pr_xtok().
	"<textarea name=newpf rows=25 cols=100 wrap=soft style=white-space:pre;font-size:120%;overflow-wrap:normal;overflow-x:scroll>".
	file_get_contents($pwdFile)."</textarea><p><input type=submit></form>";

