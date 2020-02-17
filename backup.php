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


@include "backupsecret.php";
error_reporting(E_ERROR);
if(empty($secret) || strlen($secret)<20) { die("E"); }
if(!empty($allowedIp) && $_SERVER['HTTP_X_FORWARDED_FOR']!=$allowedIp){ die("E"); }

if(!empty($_POST['f']) && !empty($_POST['tok']) && !empty($_POST['time'])) {
	$clntTime=preg_replace('/[^0-9]/','',$_POST['time']);
	if( abs($clntTime - time()) > 20 ) { die("desync"); }
	$hashok=hash_hmac("sha256", $_POST['f'], $secret.$clntTime);
	if(!hash_equals($hashok, $_POST['tok'])) { die("E"); }

	$prefix="/persist/";
	chdir($prefix);

	if($_POST['f']=='@manifest') {
		passthru("find . -type f -exec stat -c '%Y\t%n\t%s' {} \; |gzip -9c");
		exit;
	} else if($_POST['f']=='@update') {
		header("Content-type: text/plain; charset=utf8");
		passthru("./update_htdocs 2>&1");
		exit;
	} else {
		$rp=realpath($_POST['f']);
		if(strpos($rp, $prefix)!==0) { die("E3"); }
		readfile($rp);
		exit;
	}
}


print "E";
