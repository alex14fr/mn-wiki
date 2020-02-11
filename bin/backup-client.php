<?php
$url=$argv[1];
$sec=$argv[2];
$f=$argv[3];

$query=array("time" => time());
$query["tok"]=hash_hmac("sha256",$f,$sec.$query["time"]);
$query["f"]=$f;
$data=http_build_query($query);

$context_options = array('http' => array('method' => 'POST',
			'header'=> "Content-type: application/x-www-form-urlencoded",
			'content' => $data));
$context = stream_context_create($context_options);

readfile($url, false, $context);


