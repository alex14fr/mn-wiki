<?php
include "conf/conf.php";
$pwdFile="conf/users.auth.php";

function auth_getline($login) {
	global $pwdFile;
	$fd=fopen($pwdFile,"r");
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

function auth_hashpass($login, $pass) {
		$salt=gensalt();
		return crypt($pass,$salt);
}

function auth_login($login, $pass) {
	$login=san_filename($login);
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
		return(explode(',',trim($lspl[4])));
	return(array());
}

function auth_isContrib() {
	return(in_array("contributor",auth_getgroups()) || auth_isAdmin());
}

function auth_isAdmin() {
	return(in_array("admin",auth_getgroups()));
}

function auth_logout() {
	$_SESSION['auth_user']='';
}

function gensalt() {
	global $secret1,$secret2;
	if(function_exists("openssl_random_pseudo_bytes")) {
		$rnd=openssl_random_pseudo_bytes(8);
	} else {
		mt_srand(time());
		$rnd=mt_rand();
	}
	$s="$1$".substr(md5($secret1.$rnd.$secret2.microtime()),0,8)."$";
	return $s;
}

function genrepwhash($login, $curpwd) {
	global $secret1, $secret2, $secret3;
	return hash('sha256',$secret3.$login.$secret2.$curpwd.$secret1);
}

function auth_resendpwd1($email) {
	global $secret1, $secret2, $secret3, $pwdFile;
	global $baseUrl, $mailFrom;
	$email=san_csv($email);
	$crlf="\r\n";
	$fd=fopen($pwdFile,"r");
	if(!$fd) {
		die("can't open passwd file");
	}
	while($line=fgets($fd)) {
		if(strpos('#',$line)!==false) 
			next;

		$lspl=explode(':',$line);

		if(!empty($lspl[3]) && $lspl[3]==$email) {
			$login=$lspl[0];
			$sectok=genrepwhash($login, $lspl[1]);
			$mailtext=" Someone (probably you) claimed for lost credentials for the Wiki at $baseUrl . ".$crlf.$crlf." Your username is $login. ".$crlf.$crlf." To reset your password, please visit $baseUrl/doku.php?do=resendpwd2&u=$login&tok=$sectok ".$crlf.$crlf." If not, you can safely ignore this message. ";
			mail($email, "Password reset", $mailtext, "From: $mailFrom\r\nContent-type: text/plain;charset=utf8");
			return(true);
		}
	}
	return(false);
}


function generatePassword($length = 8) {
    $password = "";
    $possible = "234678923456789%%%%!!!!!aeubcdfghjkmnpqrtvwxyzAEUBCDFGHJKLMNPQRTVWXYZ";
    $maxlength = strlen($possible);
    if ($length > $maxlength) {
      $length = $maxlength;
    }
    $i = 0; 
    while ($i < $length) { 
      $char = substr($possible, mt_rand(0, $maxlength-1), 1);
      if (!strstr($password, $char)) { 
        $password .= $char;
        $i++;
      }
    }
    return $password;
}

function auth_changeUser($login, $newline) {
	global $pwdFile;
	$newfile="";
	$fd=fopen($pwdFile,"r");
	if(!$fd) {
		die("can't open passwd file");
	}
	while($line=fgets($fd)) {
		$lspl=explode(':',$line);
		if($lspl[0]!=$login) {
			$newfile.=$line;
		}
	}
	$newfile.=$newline;
	if(!is_writable($pwdFile)) { die("can't open passwd file for writing"); }
	file_put_contents($pwdFile,$newfile);
}

function auth_changePassword($login, $newpass) {
	$lspl=auth_getline($login);
	$lspl[1]=auth_hashPass($login, $newpass);
	auth_changeUser($login, implode(':',$lspl));
}

function auth_resendpwd2($login, $tok) {
	global $mailFrom;
	$lspl=auth_getline($login);
	if(genrepwhash($login,$lspl[1])!=$tok) {
		die("resendpwd2 error 2");
	}
	$newpass=generatePassword();
	$mailto=$lspl[3];
	auth_changePassword($login, $newpass);
	mail($mailto, "Your password", "Your password is : $newpass", "From: $mailFrom");
}

function auth_register($u, $p, $p2, $n, $e) {
	global $pwdFile, $secret3, $baseUrl;
	if($p!=$p2) { die("passwords don't match"); }
	if(strlen($p)<7) { die("password is too short"); }
	if($u!=san_filename($u)) {
		die("invalid username: must be <64 chars long, lowercase, a-z 0-9 - _ .");
	}
	if($e!=san_csv($e)) {
		die("mail address contains invalid characters");
	}
	if(auth_getline($u)) {
		die("login already exists");
	}
	$n=san_csv($n);
	$hash=auth_hashPass($u, $p);
	$line="$u:$hash:$n:$e:user\n";
	if(!is_writable($pwdFile)) { die("can't open passwd file for writing"); }
	file_put_contents($pwdFile,$line,FILE_APPEND|LOCK_EX);

   $hash=hash('sha256',$secret3.$u);
   sendNotify("register","Moderation request","New user registered on wiki : \r\n
   Username:    $u
   Real name:   $n
   Email:       $e
   IP:          ".$_SERVER['REMOTE_ADDR']." (".gethostbyname($_SERVER['REMOTE_ADDR']).")\r\n
Visit the following link to grant him edit rights:\r\n 
    $baseUrl/doku.php?do=addcontributor&login=$u&mail=$e&hash=$hash","");
}


function auth_addcontributor($login,$mail,$hash) {
	global $secret3, $baseUrl, $mailFrom;
	$hashok=hash('sha256',$secret3.$login);
	if($hashok!=$hash) { die('invalid link'); }
	$lspl=auth_getline($login);
	if(strpos($lspl[4],"contributor")!==false) { die("already a contributor"); }
	$lspl[4]="contributor,".$lspl[4];
	auth_changeUser($login,implode(":",$lspl));
	sendNotify("register","Edit rights granted","To username $login","");
	$mailtxt="The administrator of the wiki at $baseUrl has accepted 
you as a contributor.

This means you can edit pages on the wiki to add any
information you think the community may be interested in.

A good way to start is to add yourself (in alphabetical order please) to the
User list. To do this, click on the following link:

        $baseUrl/doku.php?id=users

and choose 'Edit this page' on the left. If this link does not appear, use the
Login link first (your login is $login).

Yours faithfully.";
		mail($mail,"Edit rights granted",$mailtxt,"From: $mailFrom");

}



