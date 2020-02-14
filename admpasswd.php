<?php
include_once "utils.php";
include_once "auth.php";
canonical();
sendCsp();
header("Content-security-policy: connect-src 'self'");
if(!isset($_SESSION)) session_start();
if(!auth_isAdmin()) { die("E"); }
if(!is_readable($pwdFile) || !is_writable($pwdFile)) { die("EE"); }

if(!empty($_POST['pass'])) {
	print password_hash($_POST['pass'],PASSWORD_DEFAULT);
	exit;
}

	print "<!doctype html>\r\n<html><head><link rel=stylesheet href=static/style.css></head><body><article>";
if(!empty($_POST['newpf'])) {
	chk_xtok("admpasswd");
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

gen_xtok("admpasswd");
	print "<form method=post><input type=hidden name=hashold value=".
	hash("sha256",file_get_contents($pwdFile)).">".pr_xtok("admpasswd").
	"<textarea id=newpf name=newpf wrap=soft>".file_get_contents($pwdFile).
	"</textarea><p><input type=submit></form><p>pass: <input id=pass> <button onclick=calcPass()>Calc hash</button>".
	"<input id=hash size=60></body></article><script src=static/crypt.js></script></html>";


