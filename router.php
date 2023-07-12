<?php
$req=$_SERVER['REQUEST_URI'];
if($req=='/github' || $req=='/github/') {
	header('Status: 301');
	header('Location: https://github.com/MASCOTNUM');
	exit;
}
$id=false;
if(strlen($req)==5 && substr($req,0,3)=='/20' && $req[3]>=0 && $req[3]<=9 && $req[4]>=0 && $req[4]<=9) {
	$id='mascot'.substr($req,1);
} else if($req=='/dam.incertitudes') {
	$id='forumincertitudes';
} else if(strpos($req,'/',1)===false && strpos($req,'?')===false && strpos($req,'.')===false) {
	$id=substr($req,1);
} else {
	header('Status: 404');
	print 'The requested resource has not been found. ';
	exit;
}
$_GET['id']=$id;
include 'index.php';

