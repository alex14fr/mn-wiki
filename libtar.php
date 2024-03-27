<?php

/**

BSD 2-Clause License

Copyright (c) 2024, Alexandre Janon <alex14fr@gmail.com>
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

function repl(&$s, $s2, $o) {
	$n=strlen($s2);
	for($i=0; $i<$n; ++$i) $s[$o+$i]=$s2[$i];
}

function tarhdr($fn, $fperm, $fsz, $fmtime, $ftype) {
	if(strlen($fn)>100) return;
	$hdr=str_repeat("\0", 512);
	repl($hdr, $fn, 0);
	repl($hdr, sprintf("%07o", $fperm), 100);
	repl($hdr, sprintf("%07o", 0), 108);
	repl($hdr, sprintf("%07o", 0), 116);
	repl($hdr, sprintf("%011o", $fsz), 124);
	repl($hdr, sprintf("%011o", $fmtime), 136);
	repl($hdr, str_repeat(" ", 8), 148);
	$hdr[156]=$ftype;
	repl($hdr, "ustar\00000", 257);
	$sum=0;
	for($i=0; $i<512; $i++) $sum+=ord($hdr[$i]);
	repl($hdr, sprintf("%06o", $sum)."\0", 148);
	return $hdr;
}

function tarf($fd, $fn) {
	if(!is_file($fn)) {
		return;
	}
	$fsz=filesize($fn);
	if(strlen($fn)>100) {
		$xattr=sprintf("%04d", strlen($fn)+11)." path=".$fn."\n";
		fwrite($fd, tarhdr('pax-lfn-xattr', 0644, strlen($xattr), 0, 'x'));
		fwrite($fd, $xattr);
		fwrite($fd, str_repeat("\0", 512-strlen($xattr)));
		fwrite($fd, tarhdr('pax-lfn', fileperms($fn), $fsz, filemtime($fn), '0'));
	}
	fwrite($fd, tarhdr($fn, fileperms($fn), $fsz, filemtime($fn), '0'));
	$fsz=$fsz % 512;
	fwrite($fd, file_get_contents($fn));
	if($fsz>0) fwrite($fd, str_repeat("\0", 512-$fsz));
}

function tarend($fd) {
	fwrite($fd, str_repeat("\0", 1024));
}

