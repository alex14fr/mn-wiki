<?php
include_once "utils.php";
include_once "auth.php";
session_start();
if(!auth_isAdmin()) { die("E"); }
if(!is_readable($pwdFile) || !is_writable($pwdFile)) { die("EE"); }

if(!empty($_POST['pass'])) {
	print password_hash($_POST['pass'],PASSWORD_DEFAULT);
	exit;
}

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
	"<textarea name=newpf wrap=soft 
	 style=white-space:pre;font-size:100%;width:100%;height:80%;overflow-wrap:normal;overflow-x:scroll>".
	file_get_contents($pwdFile).
	"</textarea><p><input type=submit></form><p>pass: <input id=pass> <button onclick=calcPass()>Calc hash</button>".
	"<input id=hash onclick=this.select() size=60><script>function calcPass() { 
			var txt=document.getElementById('pass').value;
			var xhtt=new XMLHttpRequest();
			xhtt.open('POST','admpasswd.php',true);
			xhtt.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			xhtt.send('pass='+encodeURIComponent(txt));
			xhtt.onreadystatechange = function() { 
				document.getElementById('hash').value=xhtt.responseText;
			} 
	}
		window.addEventListener('DOMContentLoaded', function() { document.getElementById('pass').addEventListener('keydown',function(e) { if(e.key=='Enter') calcPass();  }); });
	</script>";


