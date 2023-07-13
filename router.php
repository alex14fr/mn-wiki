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
} else {
	$n=strlen($req);
	for($i=1; $i<$n; $i++) 
		if(! ($req[$i]>='a' && $req[$i]<='z') || 
			  ($req[$i]>='A' && $req[$i]<='Z') ||
			  ($req[$i]>='0' && $req[$i]<='9') ||
			   $req[$i]=='_' || $req[$i]=='-' ) 
			break;
	if($i==$n)
		$id=substr($req,1);
}
if($id===false) {
	header('Status: 404');
	print 'The requested resource has not been found. ';
	exit;
} else {
	$_GET['id']=$id;
	include 'index.php';
}

