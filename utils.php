<?php
include_once "conf/conf.php";

function canonical() {
	if(!empty($canonicalHost)) {
		if(empty($canonicalProto)) $canonicalProto=($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? $_SERVER["REQUEST_SCHEME"]);
		$curHost=($_SERVER["HTTP_X_FORWARDED_HOST"] ?? $_SERVER["HTTP_HOST"]);
		if($curHost!=$canonicalHost) {
				  $destination="$canonicalProto://$canonicalHost".$_SERVER["REQUEST_URI"];
				  header("HTTP/1.1 302 Found");
				  header("Location: $destination");
				  print "<h1>HTTP/1.1 302 Found</h1><a href=\"$destination\">$destination</a>";
				  exit;
		}
	}
}

function str_replace_first($search,$replace,$subject) {
	$pos = strpos($subject, $search);
	if ($pos !== false) {
		    return substr_replace($subject, $replace, $pos, strlen($search));
	}
	return $subject;
}

function san_pageId($id) {
	$id=strtolower($id);
	$id=substr($id,0,47);
	return preg_replace("/[^a-z0-9\-_]/","",$id);
}

function san_pageRev($rev) {
	return preg_replace("/[^0-9]/","",$rev);
}

function san_filename($fn) {
	$fn=basename($fn);
	$fn=strtolower($fn);
	$fn=substr($fn,0,255);
	return preg_replace("/[^a-z0-9\-_\.]/","",$fn);
}

function san_csv($string) {
	return preg_replace("/[^ a-zA-Z0-9\-_\.@éèçàäëüïöâêîôûùŸÿŷŶâêîôûÉÈÇÀÄËÜÏÖÂÊÎÔÛÙ]/","",$string);
}

function san_diff($string) {
	return preg_replace("/[^ \r\na-zA-Z0-9\[\](){}\/?=\\|#~&'\"µ%§:;\^!,*+\-_\.@éèçàäëüïöûùŸÿŷŶâêîôŷûÉÈÇÀÄËÜÏÖÂÊÎÔÛÙ]/","",$string);
}

function sendNotify($reason, $subj, $body) {
	global $mailNotify;
	foreach($mailNotify[$reason] as $to) {
		xmail($to, $subj, $body);
	}
}

function readtmpl($id) {
	$id=san_pageId($id);
	$out=str_replace("\n","",file_get_contents("conf/$id.tmpl"));
	if($id=="htmlhead")
		$out=str_replace("<!doctype html>","<!doctype html>\n",$out);
	return $out;
}

function pageLink($id,$incbase=false) {
	global $baseUrl,$pagePrefix,$pageSuffix; 
	return  ($incbase ? $baseUrl : "").$pagePrefix.$id.$pageSuffix;
}

function gen_xtok($namespace="") {
	if(function_exists("openssl_random_pseudo_bytes")) {
		$tok=sha1(openssl_random_pseudo_bytes(32));
	} else {
		$tok = hash("sha256",$secret1.microtime().mt_rand());
	}
	$_SESSION["xtok-$namespace"] = $tok;
}

function pr_xtok($namespace="") {
	return "<input type=hidden name=xtok value=".$_SESSION["xtok-$namespace"].">";
}

function chk_xtok($namespace="") {
	$k="xtok-$namespace";
	if(empty($_REQUEST["xtok"])||empty($_SESSION[$k])||!hash_equals($_SESSION[$k],$_REQUEST["xtok"])) {
		die('xtok verification failed');
	}
}




/*
    Paul's Simple Diff Algorithm v 0.1
    (C) Paul Butler 2007 <http://www.paulbutler.org/>
    May be used and distributed under the zlib/libpng license.
    
    This code is intended for learning purposes; it was written with short
    code taking priority over performance. It could be used in a practical
    application, but there are a few ways it could be optimized.
    
    Given two arrays, the function diff will return an array of the changes.
    I won't describe the format of the array, but it will be obvious
    if you use print_r() on the result of a diff on some test data.
    
    htmlDiff is a wrapper for the diff command, it takes two strings and
    returns the differences in HTML. The tags used are <ins> and <del>,
    which can easily be styled with CSS.  
*/

function diff($old, $new){
    $matrix = array();
    $maxlen = 0;
    foreach($old as $oindex => $ovalue){
        $nkeys = array_keys($new, $ovalue);
        foreach($nkeys as $nindex){
            $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            if($matrix[$oindex][$nindex] > $maxlen){
                $maxlen = $matrix[$oindex][$nindex];
                $omax = $oindex + 1 - $maxlen;
                $nmax = $nindex + 1 - $maxlen;
            }
        }   
    }
    if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
    return array_merge(
        diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

function textDiff($old, $new){
    $ret = '';
    $diff = diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
    foreach($diff as $k){
        if(is_array($k))
            $ret .= (!empty($k['d'])?"- ".implode(' ',$k['d'])."\n":'').
                (!empty($k['i'])?"+ ".implode(' ',$k['i'])."\n":'');
        /* else  $ret .= $k . ' ' ; */
    }
    return $ret;
}

/**************************************/

