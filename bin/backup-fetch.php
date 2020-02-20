<?php

function fetchFile($url, $sec, $f, $out)
{
    $query = array("time" => time());
    $query["tok"] = hash_hmac("sha256", $f, $sec . $query["time"]);
    $query["f"] = $f;
    $data = http_build_query($query);

    $context_options = array('http' => array('method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded",
                'content' => $data));
    $context = stream_context_create($context_options);

    $fhin = fopen($url, "rb", false, $context);
    $fhout = fopen($out, "wb");
    while (!feof($fhin)) {
        fwrite($fhout, fread($fhin, 32768));
    }
    fclose($fhin);
    fclose($fhout);
}

$url = $argv[1];
$sec = $argv[2];
$nomf = $argv[3] ?? false;
$outdir = "sauve-persist";

if (!$nomf) {
    print 'fetch manifest... ';
    fetchFile($url, $sec, "@manifest", "MANIFEST.gz");
    system("gunzip MANIFEST.gz");
    rename("MANIFEST", "$outdir/MANIFEST");
    print 'ok\n';
}

$lines = file("$outdir/MANIFEST");
print $lines[0];
foreach ($lines as $l) {
    $l = trim($l);
    if (strpos($l, "#") !== 0 && strpos($l, "find") !== 0 && $l != "E") {
        $ls = explode("\t", $l);
        $mfmtime = $ls[0];
        $mffname = $ls[1];
        $mfsize = $ls[2];
        print $mffname . " : ";
        if (
            file_exists("$outdir/$mffname") &&
            filemtime("$outdir/$mffname") >= $mfmtime &&
            filesize("$outdir/$mffname") == $mfsize
        ) {
            print "skip\n";
        } else {
            @mkdir(dirname("$outdir/$mffname"), 0777, true);
            print "fetch... ";
            fetchFile($url, $sec, $mffname, "$outdir/$mffname");
            print "ok\n";
        }
    }
}
