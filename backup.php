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
if (empty($secret) || strlen($secret) < 20) {
    die("E");
}
/*
if (!empty($allowedIp) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== $allowedIp) {
    die("E");
} */

if (!empty($_POST['f']) && !empty($_POST['tok']) && !empty($_POST['time'])) {
    $clntTime = preg_replace('/[^0-9]/', '', $_POST['time']);
	 /*
    if (abs($clntTime - time()) > 90) {
        die("desync");
    }
	 */
    $hashok = hash_hmac("sha256", $_POST['f'], $secret . $clntTime);
    if (!hash_equals($hashok, $_POST['tok'])) {
        die("E");
    }

    $prefix = "/persist/";
    chdir($prefix);

    if ($_POST['f'] === '@manifest') {
		  $dirs=['.'];
		  while(count($dirs)>0) {
				$d=array_pop($dirs);
				$dd=opendir($d);
				while($dde=readdir($dd)) {
					if($dde==='.' || $dde==='..') continue;
					$ddee=$d.'/'.$dde;
					if(is_dir($ddee)) {
						array_push($dirs, $ddee);
					} else if(is_file($ddee)) {
						print filemtime($ddee)."\t".$ddee."\t".filesize($ddee)."\n";
					}
				}
		  }
        exit;
    } elseif (substr($_POST['f'],0,5) === "@tar@") {
		 	include "libtar.php";
			$flist=substr($_POST['f'],5);
			header("Content-type: application/x-tar");
			header("Content-disposition: attachment; filename=backup-mnwiki-".date("YmdHis").".tar");
			$fstdout=fopen("php://output","w");
			foreach(explode("\n", $flist) as $ff) {
				tarf($fstdout, $ff);
			}
			tarend($fstdout);
			exit;
	 } else {
        $rp = realpath($_POST['f']);
        //if (strpos($rp, $prefix) !== 0) {
        //    die("E3");
        //}
        readfile($rp);
        exit;
    }
}


print "E";
