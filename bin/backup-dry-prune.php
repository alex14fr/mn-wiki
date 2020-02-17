<?php
$outdir="sauve-persist";
chdir($outdir);
system("find . -type f > /tmp/xx");
chdir("..");
$files=file("/tmp/xx");
$mf=file("$outdir/MANIFEST");
$mffiles=array();
foreach($mf as $l) {
	$mffiles[]=explode("\t",$l)[1];
}
foreach($files as $f) {
	$f=trim($f);
	if(!in_array($f,$mffiles) && $f!="./MANIFEST") {
		print "$outdir/$f\n";
	}
}
