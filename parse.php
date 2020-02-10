<?php
include_once "utils.php";
include_once "conf/conf.php";

$tags=array("*"=>"b","/"=>"i","_"=>"u");
function parse_inline($l, $parseTags=true) {
	global $tags, $pageId, $sectok;
	global $mediaPrefix,$pagePrefix,$pageSuffix;
	$out="";
	$n=strlen($l);
	for($i=0;$i<$n;$i++) {
		switch($l[$i]) {
			case "<":
				$out.="&lt;";
				break;
			case ">":
				$out.="&gt;";
				break;
			case "\"":
				$out.="&quot;";
				break;
			case "*":
			case "_":
			case "/":
				if($i+1<$n && $l[$i+1]==$l[$i] && $parseTags) {
					$c=$l[$i];
					$i+=2;
					$s="";
					$inlink=false;
					for(; $i<$n-1 && ($inlink || ($l[$i]!=$c && $l[$i+1]!=$c)); $i++) { 
						if($l[$i]=="{"||$l[$i]=="[")
							$inlink=true;
						if($l[$i]=="]"||$l[$i]=="}")
							$inlink=false;
						$s.=$l[$i];
					}
					if($i<$n) $s.=$l[$i];
					$i+=2;
					$out.="<".$tags[$c].">".parse_inline($s)."</".$tags[$c].">";
				} else {
					$out.=$l[$i];
				}
				break;
			case "[":
			case "{":
				$c=$l[$i];
				if($i+1<$n && $l[$i+1]==$c) {
					$i+=2;
					$s="";
					if($c=="[") $clC="]";
					else if($c=="{") $clC="}";
					for(; $i<$n-1 && $l[$i]!=$clC && $l[$i+1]!=$clC; $i++) $s.=$l[$i];
					if($i<$n) $s.=$l[$i];
					$i+=2;
					if(!empty($s) && $s[0]==":") 
						$s=substr($s,1);
					$mm=explode("|",$s);
					$ptags=true;
					if(count($mm)==1) {
						$mm[1]=$mm[0];
						$ptags=false;
					}
					$asimg=false;
					$mm[0]=str_replace("\"","",$mm[0]);
					if($c=="{") {
						$mmex=explode("?",$mm[0]);
						$baseurl=$mmex[0];
						if(count($mmex)>1) {
							$wid=$mmex[1];
							$wid=preg_replace("/[^0-9]/","",$wid);
						}
						$u=strlen($baseurl)-4;
						if(strripos($baseurl,".png")==$u || 
							strripos($baseurl,".gif")==$u || 
							strripos($baseurl,".jpg")==$u || 
							strripos($baseurl,".webp")==$u-1) 
								$asimg=true;
					}
					if(substr($mm[0],0,7)!="mailto:" && strpos($mm[0],"@")>0) 
						$mm[0]="mailto:".$mm[0];
					else if(substr($mm[0],0,4)!="http" && substr($mm[0],0,3)!="ftp") {
						if($c=="{")
							$mm[0]=$mediaPrefix.san_filename($baseurl);
						else if($c=="[")
							$mm[0]=$pagePrefix.san_pageId($mm[0]).$pageSuffix;
					}
					if(!$asimg) {
						$out.="<a href=\"".$mm[0]."\">".parse_inline($mm[1],$ptags)."</a>";
					}
					else {
						$out.="<img ".(!empty($wid) ? "width=$wid " : "")."src=\"".$mm[0]."\">";
					}
				} else {
					$out.=$l[$i];
				}
				break;
			case "~":
				if($i+1<$n && $l[$i]==$l[$i+1]) {
					$i+=2;
					$s="";
					for(; $i<$n && $l[$i]=="~"; $i++);
					for(; $i<$n && $l[$i]!="~"; $i++) $s.=$l[$i];
					for(; $i<$n && $l[$i]=="~"; $i++);
					if($s=="/div") {
						$out.="</div>";
					} else if(strpos($s,"div")!==false) {
						$sxplod=explode(" ",$s);
						$cl=str_replace("\"","",$sxplod[1]);
						$out.="<div class=\"$cl\">";
					} else if(strpos($s,"FORM")!==false) {
						$out.="<ul><li><a href=event.php?action=view&id=$pageId&sectok=$sectok>List of participants</a>";
						if($s!="FORM expire")
							$out.="<li><a href=event.php?id=$pageId&sectok=$sectok>Registration form</a>";
						$out.="</ul>";
					} else if($s=="HAL") {
						$out.=file_get_contents("static/hal.html");
					} else if($s=="rss2") {
						$out.=file_get_contents("static/rss2.html");
					} else if($s=="rssshort") {
						$out.=file_get_contents("static/rssshort.html");
					}
				} else {
					$out.=$l[$i];
				}
				break;
			case "\\":
				if($i+1<$n && $l[$i+1]=="\\") $i=$i+2;
				else $out.=$l[$i];
				break;
			default:
				$out.=$l[$i];
				break;

		}

	}
	return($out);
}

function exit_par() {
	global $list_lvl, $in_tbl;
	$out="";
	for(; $list_lvl>0; $list_lvl--) 
		$out.="</ul>";
	if($in_tbl)
		$out.="</table>";
	$in_tbl=false;
	return $out;
}

function parse_line($l) {
	global $list_lvl, $title, $toc, $curAnchor, $in_tbl, $title_lvl, $head_lvl;

	$l=rtrim(htmlspecialchars($l));
	$n=strlen($l);

	if($n==0) {
		if($list_lvl>0 || $in_tbl) 
			return exit_par();
		else 
			return "<p>";
	}

	if(substr($l,0,10)=="/* toc */ ") {
		$l=substr($l,10);
		$n=strlen($l);
	}

	for($i=0; $i<$n; $i++) {
		switch($l[$i]) {
			case "=":
				$head_lvl=0;
				for(; $l[$i]=="=" && $i++<$n; $head_lvl++);
				$header="";
				for(; $i<$n && $l[$i]!="=" ; $header.=$l[$i++]);
				if($head_lvl>=5) $head_lvl=1;
				else $head_lvl=6-$head_lvl;
				$txt=parse_inline($header);
				if($head_lvl<$title_lvl) {
					$title=$txt;
					$title_lvl=$head_lvl;
				}
				else if($head_lvl==2) {
					$curAnchor++;
					$toc.="<a href=#a".$curAnchor.">".$txt."</a><br>";
					$txt="<a name=a".$curAnchor.">".$txt;
				}
				return "<h".$head_lvl.">".$txt."</h".$head_lvl.">".($head_lvl==1 ? "~~TOC~~<p>" : "");

			case "-":
				if($i++<$n && $l[$i]=="-")
					return "<hr>";

			case " ":
				$spc_cnt=0;
				$out="";
				for(; $l[$i]==" " && $i++<$n; $spc_cnt++);
				if($l[$i]=="-" || $l[$i]=="*" || $l[$i]==".") {
					$list_gap=($spc_cnt-2*$list_lvl)/2;
					for(; $list_gap>0; $list_gap--) $out.="<ul>";
					for(; $list_gap<0; $list_gap++) $out.="</ul>";
					$list_lvl=$spc_cnt/2;
					if($list_lvl>0) $out.="<li>";
					return $out.parse_inline(substr($l,$i+1));
				} else {
					return parse_inline($l);
				}

			case "^":
				$out="";
				if(!$in_tbl) {
					$in_tbl=true;
					$out.="<table border=1>";
				}
				$out.="<tr>";
				$in_link=false;
				$i++;
				for(; $i<$n; $i++) {
					$s="";
					for(; $i<$n && ($in_link || $l[$i]!="|"); $i++) {
						if($l[$i]=="["||$l[$i]=="{")
							$in_link=true;
						else if($l[$i]=="]"||$l[$i]=="}")
							$in_link=false;
						$s.=$l[$i];
					}
					if($i+1<$n && $l[$i+1]=="|") {
						$out.="<td colspan=2>".parse_inline($s);
						$i+=2;
					} else if($s!="")
						$out.="<td>".parse_inline($s);
				}
				return $out;

			default:
				$eli=exit_par();
				$pil=parse_inline($l);
				$br="";
				if(strlen($pil)>0) $br="<br>";
				return $eli.$pil.$br;

		}

	}

}

function render_str($str) {
	global $list_lvl, $title, $title_lvl, $toc, $curAnchor;
	$list_lvl=0;
	$toc="";
	$curAnchor=0;
	$out="";
	foreach(explode("\n",$str) as $l) 
		$out.=parse_line($l);
	$out=str_replace_first("~~TOC~~",$toc,$out);
	$out=str_replace("~~TOC~~","",$out);
	return($out);
}

function render_page($page, $rev="") {
	global $list_lvl, $title, $title_lvl, $toc, $curAnchor, $pageId, $sectok;
	include_once "conf/conf.php";
	global $secret1, $secret2, $pageDir, $atticDir;
	$list_lvl=0;
	$pageId=san_pageId($page);
	$title=$pageId;
	$title_lvl=4;
	$toc="";
	$curAnchor=0;
	$rev=san_pageRev($rev);
	$out="";
	$sectok=md5($secret1.$pageId.$secret2);
	if(empty($rev)) {
		$fnam="$pageDir/$pageId.txt";
		if(!is_readable($fnam)) {
			header("HTTP/1.1 404 Not found");
			print "Page $pageId not found. ";
			if(!empty($_SESSION['auth_user'])) {
				print "<a href=\"doku.php?do=edit&id=$pageId\">Create this page</a>";
			}
			exit;
		}
		$fd=fopen($fnam,"r");
		if(!$fd) die("err");
		while($line=fgets($fd)) 
			$out.=parse_line($line);
		fclose($fd);
	}
	else {
		$fd=gzopen("$atticDir/$pageId.$rev.txt.gz","r");
		if(!$fd) die("err");
		while($line=gzgets($fd))
			$out.=parse_line($line);
		fclose($fd);
	}
	$out.=exit_par();
	$out=str_replace_first("~~TOC~~",$toc,$out);
	$out=str_replace("~~TOC~~","",$out);
	return($out);
}

function render_page_cache($page, $rev="") {
	global $pageDir, $cacheDir;
	$pageId=san_pageId($page);
	if(!empty($rev)) {
		$rev=preg_replace("/[^0-9]/","",$rev);
	}
	if(!empty($rev) || 
		$rev!="" ||
		!file_exists("$cacheDir/$pageId") || 
		filemtime("$cacheDir/$pageId")<filemtime("$pageDir/$pageId.txt")) {
			$rp=render_page($pageId,$rev);
			if((empty($rev)||$rev=="") && !empty($cacheDir)) {
				file_put_contents("$cacheDir/$pageId",$rp);
			}
			return($rp);
	} else {
			return(file_get_contents("$cacheDir/$pageId"));
	}
}

function render_html($str,$pageId="",$title="") {
	$actions="";
	if(!empty($pageId)) {
		if(!empty($_SESSION['auth_user'])) {
			$actions="<a href=\"doku.php?do=edit&id=$pageId\">Edit this page</a>".
						"<a href=\"doku.php?do=revisions&id=$pageId\">Old revisions</a>".
						(auth_isAdmin() ? "<a href=\"doku.php?do=edit&id=sidebar\">Edit sidebar</a><a href=admpasswd.php>Edit passwd</a>" : "").
						"<a href=\"doku.php?do=logout&id=$pageId\">Logout ".$_SESSION['auth_user']."</a>";
		} else {
			$actions="<a href=\"doku.php?do=login&id=$pageId\">Login / Register</a>";
		}
	}
	$pgh=str_replace(array("~~ACTIONS~~","~~TITLE~~","~~SIDEBAR~~"),
						  array($actions, $title, render_page_cache("sidebar")),
						  readtmpl("htmlhead"));
	return $pgh.$str.readtmpl("htmlfoot");
}

function render_page_full($page, $rev="") {
	global $title;
	$pageId=san_pageId($page);
	$rp=render_page_cache($pageId,$rev);
	return render_html($rp, $page, $title);
}


