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
	print "OK<p>";
}

gen_xtok();
print "<form method=post><input type=hidden name=hashold value=".hash("sha256",file_get_contents($pwdFile)).">".pr_xtok()."<textarea name=newpf rows=25 cols=90>".file_get_contents($pwdFile)."</textarea><p><input type=submit></form>";

