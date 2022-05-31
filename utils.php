<?php

/**

BSD 2-Clause License

Copyright (c) 2020, Alexandre Janon <alex14fr@gmail.com>
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

include_once "conf/conf.php";

function canonical()
{
    global $canonicalProto, $canonicalHost;
    if (!empty($canonicalHost)) {
		  if (empty($_SERVER["HTTP_X_FORWARDED_HOST"]) && empty($_SERVER["HTTP_HOST"])) {
			  return;
		  }
        if (empty($canonicalProto)) {
            $canonicalProto = ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? $_SERVER["REQUEST_SCHEME"]);
        }
        $curHost = ($_SERVER["HTTP_X_FORWARDED_HOST"] ?? $_SERVER["HTTP_HOST"]);
        if ($curHost !== $canonicalHost) {
                  $destination = "$canonicalProto://$canonicalHost" . $_SERVER["REQUEST_URI"];
                  header("HTTP/1.1 302 Found");
                  header("Location: $destination");
                  print "<h1>302 Found</h1><a href=\"$destination\">$destination</a>";
                  exit;
        }
    }
}

function sendCsp($add = "")
{
    header("Content-security-policy: default-src 'none'; font-src 'self'; img-src 'self'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'; " . (empty($add) ? "" : $add));
    header("X-frame-options: deny");
}

function die403($s)
{
    header("403 Forbidden");
    die($s);
}

function str_replace_first($search, $replace, $subject)
{
    $pos = strpos($subject, $search);
    if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

function san_pageId($id)
{
    $id = strtolower($id);
    $id = substr($id, 0, 47);
    return preg_replace("/[^a-z0-9\-_]/", "", $id);
}

function san_pageRev($rev)
{
    return preg_replace("/[^0-9]/", "", $rev);
}

function san_filename($fn)
{
    $fn = basename($fn);
    $fn = strtolower($fn);
    $fn = substr($fn, 0, 255);
    return preg_replace("/[^a-z0-9\-_\.]/", "", $fn);
}

function san_csv($string)
{
    return preg_replace("/[^ a-zA-Z0-9\-_\.@éèçàäëüïöâêîôûùŸÿŷŶâêîôûÉÈÇÀÄËÜÏÖÂÊÎÔÛÙ]/", " ", $string);
}

function san_csv_ext($string)
{
    return preg_replace("/[^ a-zA-Z0-9\-_\.@éèçàäëüïöâêîôûùŸÿŷŶâêîôûÉÈÇÀÄËÜÏÖÂÊÎÔÛÙ&;]/", " ", $string);
}

function san_diff($string)
{
    return preg_replace("/[^ \r\na-zA-Z0-9\[\](){}\/?=\\|#~&'\"µ%§:;\^!,*+\-_\.@éèçàäëüïöûùŸÿŷŶâêîôŷûÉÈÇÀÄËÜÏÖÂÊÎÔÛÙ]/", "", $string);
}

function sendNotify($reason, $subj, $body, $hdrs="")
{
    global $mailNotify;

    foreach ($mailNotify[$reason] as $to) {
        xmail($to, $subj, $body, $hdrs);
    }

	 file_put_contents($dataDir."/maillog","To: $to\r\nSubject: $subj\r\n$body\r\n\r\n",FILE_APPEND);
}

function readtmpl($id)
{
    $id = san_pageId($id);
    $out = str_replace("\n", "", file_get_contents("conf/$id.tmpl"));
    if ($id === "htmlhead") {
        $out = str_replace("<!doctype html>", "<!doctype html>\n", $out);
		  $out = str_replace("~~CSSTS~~", filemtime("static/mstyle.css"), $out);
    }
    return $out;
}

function pageLink($id, $incbase = false)
{
    global $baseUrl,$pagePrefix,$pageSuffix;
    return  ($incbase ? $baseUrl : "") . $pagePrefix . $id . $pageSuffix;
}

function get_random()
{
    if (function_exists("openssl_random_pseudo_bytes")) {
        $r = sha1(openssl_random_pseudo_bytes(32));
    } else {
        $r = hash("sha256", $secret1 . microtime() . mt_rand());
    }
    return($r);
}

function gen_xtok($namespace = "")
{
}

function base64url_encode( $data ){
	  return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=');
}

function base64url_decode( $data ){
	  return base64_decode( strtr( $data, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $data )) % 4 ));
}

$verifiedLogin=false;
$certLogin="";

function issue_login_token($login)
{
	global $clientIp, $baseUrl, $secret3, $secret2;

	$sticky=base64url_encode(hash("sha256",$clientIp."|".$baseUrl."|".$_SERVER["HTTP_USER_AGENT"]."|".hash("sha256",$secret2,true),true));
	$mask=hash("sha512","mnwiki@baseUrl@".filemtime("conf/conf.php"),true);
	if(strlen($login)>strlen($mask)) { die("login too long"); }
	$loginn=base64url_encode($login^$mask);
	$strToSign="$sticky.$loginn.".time();
	$sign=base64url_encode(hash_hmac("sha256",$strToSign,$secret3,true));
	return "$strToSign.$sign";
}


function get_login_cookie_name() {
	global $baseUrl;
	$ret=base64url_encode(hash("md5","mnwiki@$baseUrl@".filemtime("conf/conf.php"),true));
	//print "GLCN = $ret<p>";
	return $ret;
}

function allow_login($login) 
{
	$tok=issue_login_token($login);
	setcookie(get_login_cookie_name(), $tok, array("samesite"=>"Strict","expires"=>time()+86400, "secure"=>true, "httponly"=>true));
	$verifiedLogin=true;
	$certLogin=$login;
}

function chk_login_tok($token) {
	global $clientIp, $baseUrl, $secret3, $secret2;
	$cltok=explode(".",$token);
	print "CK LOGIN TOK $token<p>";
	if(count($cltok)!==4) { 
		print " 1<p>";
		return ""; 
	}
	$signOk=base64url_encode(hash_hmac("sha256",$cltok[0].".".$cltok[1].".".$cltok[2],$secret3,true));
	if(!hash_equals($signOk,$cltok[3])) { die("login token verification failed (signature)"); }
	$sticky=base64url_encode(hash("sha256",$clientIp."|".$baseUrl."|".$_SERVER["HTTP_USER_AGENT"]."|".hash("sha256",$secret2,true),true));
	if(!hash_equals($sticky,$cltok[0])) {
		print " 2<p>";
		return ""; 
	}
	if(!is_numeric($cltok[2]) || ($cltok[2]+86400<time())) {
		print " 3<p>";
		return ""; 
	}
	$mask=hash("sha512","mnwiki@$baseUrl@".filemtime("conf/conf.php"),true);
	$login=base64url_decode($cltok[1])^$mask;
	return $login;
}

function get_login()
{
	global $verifiedLogin, $certLogin;
	if($verifiedLogin)
		return($certLogin);
	else {
		$tok=$_COOKIE[get_login_cookie_name()];
		$verifiedLogin=true;
		$certLogin=chk_login_tok($tok);
	}
}

function get_xtok($namespace = "")
{
	global $clientIp, $baseUrl, $secret2, $verifiedLogin;
	$strToSign=base64url_encode(hash("sha256",$namespace."|".$clientIp."|".$baseUrl."|".$verifiedLogin."|".$_SERVER["HTTP_USER_AGENT"]."|".hash("sha256",$secret2,true),true)).".".time();
	$sign=base64url_encode(hash_hmac("sha256",$strToSign,$secret2,true));
	$tok=$strToSign.".".$sign;
	return $tok;
}

function pr_xtok($namespace = "")
{
	$tok=get_xtok($namespace);
   return "<input type=hidden name=xtok value=$tok>";
}

function chk_xtok_tok($token, $namespace = "")
{
	global $clientIp, $baseUrl, $secret2, $verifiedLogin;
	$cltok=explode(".", $token);
	if(count($cltok)!==3) { die("xtok verification failed (format) - go back, refresh page and try again"); }
	$signOk=base64url_encode(hash_hmac("sha256",$cltok[0].".".$cltok[1],$secret2,true));
	if(!hash_equals($signOk,$cltok[2])) { die("xtok verification failed (signature) - go back, refresh page and try again"); }
	$signedStrOk=base64url_encode(hash("sha256",$namespace."|".$clientIp."|".$baseUrl."|".$verifiedLogin."|".$_SERVER["HTTP_USER_AGENT"]."|".hash("sha256",$secret2,true),true));
	if(!hash_equals($signedStrOk,$cltok[0])) { die("xtok verification failed (claim) - go back, refresh page and try again"); }
	if(!is_numeric($cltok[1]) || ($cltok[1]+3600<time())) { die("xtok verification failed (expired) - go back, refresh page and try again"); }
	return(true);
}

function chk_xtok($namespace = "") {
	return chk_xtok_tok($_REQUEST['xtok'], $namespace);
}

function contextDiff($dc) {
	$dcout=array();
	for($i=0; $i<count($dc); $i++) {
		if($dc[$i][1]==Diff::UNMODIFIED) {
			if( ($i<count($dc)-1 && $dc[$i+1][1]!=Diff::UNMODIFIED) ||
				 ($i<count($dc)-2 && $dc[$i+2][1]!=Diff::UNMODIFIED) ||
				 ($i>0 && $dc[$i-1][1]!=Diff::UNMODIFIED) ||
				 ($i>1 && $dc[$i-2][1]!=Diff::UNMODIFIED) )
						$dcout[]=$dc[$i];
		} else 
			$dcout[]=$dc[$i];
	}
	return($dcout);
}
