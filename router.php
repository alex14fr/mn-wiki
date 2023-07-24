<?php
$req=$_SERVER['REQUEST_URI'];
$p=strpos($req, '?');
if($p!==false)
	$req=substr($req, 0, $p);
if($req=='/github' || $req=='/github/') {
	header('Status: 301');
	header('Location: https://github.com/MASCOTNUM');
	exit;
}
$id=false;
$n=strlen($req);
if($n==5 && substr_compare($req,'/20',0,3)===0 && $req[3]>=0 && $req[3]<=9 && $req[4]>=0 && $req[4]<=9) {
	$id='mascot'.substr($req,3);
} else if($req=='/dam.incertitudes') {
	$id='forumincertitudes';
} else {
	if(substr_compare($req, '.html', -5, 5)===0) {
		$req=substr($req, 0, $n-5);
		$n-=5;
	}
	for($i=1; $i<$n; ++$i) 
		if(!($req[$i]>='a' && $req[$i]<='z') && 
			!($req[$i]>='A' && $req[$i]<='Z') &&
			!($req[$i]>='0' && $req[$i]<='9') &&
			   $req[$i]!='_' && $req[$i]!='-' ) 
			break;
	if($i==$n)
		$id=substr($req,1);
}
if($id===false) {
	header('Status: 404');
	print 'The requested resource has not been found. ';
	exit;
} else {
	$req=$_SERVER['REQUEST_URI'];
	if($p!==false) 
		$qs=substr($req,$p+1);
	parse_str($qs, $tab);
	foreach($tab as $k=>$v) {
		$_GET[$k]=$_REQUEST[$k]=$v;
	}
	$_GET['id']=$id;
	include 'index.php';
}

