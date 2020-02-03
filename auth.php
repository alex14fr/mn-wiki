<?php
include "conf/conf.php";

function auth_getline($login) {
	$fd=fopen("conf/users.auth.php","r");
	if(!$fd) {
		die("can't open passwd file");
	}
	while($line=fgets($fd)) {
		if(strpos('#',$line)) 
			next;

		$lspl=explode(':',$line);

		if($lspl[0]==$login)
			return $lspl;
	}
	return false;
}

function auth_login($login, $pass) {
	$lspl=auth_getline($login);
	if($lspl) {
		$hashpass=$lspl[1];
		$salt=substr($hashpass,0,12);
		$cryp=crypt($pass,$salt);
		if($cryp==$hashpass) {
			$_SESSION['auth_user']=$login;
			return(true);
		} else {
			$_SESSION['auth_user']='';
			return(false);
		}
	}
	$_SESSION['auth_user']='';
	return(false);
}

function auth_getgroups() {
	$lspl=auth_getline($_SESSION['auth_user']);
	if($lspl)
		return(explode(',',$lspl[4]));
	return(array());
}

function auth_isContrib() {
	return(in_array("contributor",auth_getgroups()));
}

function auth_isAdmin() {
	return(in_array("admin",auth_getgroups()));
}

function auth_logout() {
	$_SESSION['auth_user']='';
}

function gensalt() {
	if(function_exists("openssl_random_pseudo_bytes")) {
		$rnd=openssl_random_pseudo_bytes(8,true);
	} else {
		mt_srand(time());
		$rnd=mt_rand();
	}
	$s="$1$".substr(md5($secret1.$rnd.$secret2.microtime()),0,8)."$";
	return $s;
}

function doLogin() {
	session_set_cookie_params(array("httponly"=>TRUE));
	session_start();
	auth_login($_POST['login'],$_POST['passw']);
}


