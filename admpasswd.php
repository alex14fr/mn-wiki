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
include_once "auth.php";
canonical();
sendCsp("script-src 'self'; connect-src 'self'");
if (!auth_isAdmin()) {
	die("E");
}
if (!is_readable($pwdFile) || !is_writable($pwdFile)) {
	die("EE");
}

if (!empty($_POST['pass'])) {
	print password_hash($_POST['pass'], PASSWORD_DEFAULT);
	exit;
}

if(empty($_POST['newpf']) && empty($_POST['newperm'])) {
	head_xtok();
}

print "<!doctype html>\r\n<html><head><link rel=stylesheet href=static/mstyle.css?".filemtime("static/mstyle.css")."></head><body><article>";
if (!empty($_POST['newpf'])) {
	chk_xtok("admpasswd");
	$oldpf = file_get_contents($pwdFile);
	$hashold = hash("sha256", $oldpf);
	if ($hashold !== $_POST['hashold']) {
		die("error: race condition on passwd file");
	}
	$newpf = trim($_POST['newpf']) . "\n";
	auth_lockPasswd();
	auth_rewriteLockedPasswd($newpf);
	auth_releaseLockPasswd();
	if (!auth_isAdmin()) {
		auth_lockPasswd();
		auth_rewriteLockedPasswd($oldpf);
		auth_releaseLockPasswd();
		die("error: lock out");
	}
	print "OK <a href=index.php>Back</a>";
	exit;
}

if(!empty($_POST['newperm'])) {
	chk_xtok("admpasswd");
	file_put_contents($permFile, $_POST['newperm']);
	print "OK <a href=index.php>Back</a>";
	exit;
}

gen_xtok("admpasswd");
print "<h2>Passwd file</h2><form method=post><input type=hidden name=hashold value=" .
hash("sha256", file_get_contents($pwdFile)) . ">" . pr_xtok("admpasswd") .
"<textarea id=newpf name=newpf wrap=soft>" . file_get_contents($pwdFile) .
"</textarea><p><input type=submit></form><p>pass: <input id=pass> <button id=hashbtn>Calc hash</button>" .
"hash: <input id=hash size=60><p>" .
"<h2>Edit permissions</h2><form method=post><textarea id=newperm name=newperm wrap=soft>" . file_get_contents($permFile) . "</textarea>" .
pr_xtok("admpasswd") .
"<input type=submit></form>" .
"<h3>Contrib-writable pages:</h3>";
$wd=opendir($editableDir);
while(($d=readdir($wd))!==false) {
	if ($d != "." && $d != "..") {
		print "<a href=" . $pagePrefix . $d . $pageSuffix . " target=_blank>$d</a> ";
	}
}
closedir($wd);
print "</article></body><script src=static/crypt.js?v></script></html>";
