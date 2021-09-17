<?php

$outdir = "sauve-persist";
chdir($outdir);
$tmpn=tempnam("/tmp/","xx");
system("find . -type f > $tmpn");
chdir("..");
$files = file($tmpn);
unlink($tmpn);
$mf = file("$outdir/MANIFEST");
$mffiles = array();
foreach ($mf as $l) {
    $mffiles[] = explode("\t", $l)[1];
}
foreach ($files as $f) {
    $f = trim($f);
    if (!in_array($f, $mffiles) && $f != "./MANIFEST") {
        print "$outdir/$f\n";
    }
}
