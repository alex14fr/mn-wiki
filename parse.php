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
				if($l[$i+1]==$l[$i] && $parseTags) {
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
					$s.=$l[$i];
					$i+=2;
					$out.="<".$tags[$c].">".parse_inline($s)."</".$tags[$c].">";
				} else {
					$out.=$l[$i];
				}
				break;
			case "[":
			case "{":
				$c=$l[$i];
				if($l[$i+1]==$c) {
					$i+=2;
					$s="";
					if($c=="[") $clC="]";
					else if($c=="{") $clC="}";
					for(; $i<$n-1 && $l[$i]!=$clC && $l[$i+1]!=$clC; $i++) $s.=$l[$i];
					$s.=$l[$i];
					$i+=2;
					if($s[0]==":") 
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
						$wid=$mmex[1];
						$wid=preg_replace("/[^0-9]/","",$wid);
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
						$out.="<img ".($wid ? "width=$wid " : "")."src=\"".$mm[0]."\">";
					}
				} else {
					$out.=$l[$i];
				}
				break;
			case "~":
				if($l[$i]==$l[$i+1]) {
					$i+=2;
					$s="";
					for(; $i<$n && $l[$i]=="~"; $i++);
					for(; $i<$n && $l[$i]!="~"; $i++) $s.=$l[$i];
					for(; $i<$n && $l[$i]=="~"; $i++);
					if($s=="/div") {
						$out.="</div>";
					} else if(strpos($s,"div")!==false) {
						$cl=str_replace("\"","",explode(" ",$s)[1]);
						$out.="<div class=\"$cl\">";
					} else if(strpos($s,"FORM")!==false) {
						$out.="<ul><li><a href=/lib/exe/inscription.php?action=voir&id=$pageId&sectok=$sectok>List of participants";
						if(strpos($s,"expire")!==false) 
							$out.="<li><a href=/lib/exe/inscription.php?id=$pageId&sectok=$sectok>Registration form";
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
				if($l[$i+1]=="\\") $i=$i+2;
				else $out.=$l[$i];
				break;
			default:
				$out.=$l[$i];
				break;

		}

	}
	return($out);
}

function exit_li() {
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

	$l=rtrim($l);
	$n=strlen($l);

	if($n==0) {
		if($list_lvl>0 || $in_tbl) 
			return exit_li();
		else 
			return "<p>";
	}

	for($i=0; $i<$n; $i++) {
		switch($l[$i]) {
			case "=":
				$head_lvl=0;
				for(; $l[$i]=="=" && $i++<$n; $head_lvl++);
				$header="";
				for(; $l[$i]!="=" && $i<$n; $header.=$l[$i++]);
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
					return "<hr noshade>";

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
					if($l[$i+1]=="|") {
						$out.="<td colspan=2>".parse_inline($s);
						$i+=2;
					} else if($s!="")
						$out.="<td>".parse_inline($s);
				}
				return $out;

			default:
				$eli=exit_li();
				$pil=parse_inline($l);
				$br="";
				if(strlen($pil)>0) $br="<br>";
				return $eli.$pil.$br;

		}

	}

}

function render_page($page, $rev="") {
	global $list_lvl, $title, $title_lvl, $toc, $curAnchor, $pageId, $sectok;
	include_once "conf/conf.php";
	global $secret1, $secret2;
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
		$fd=fopen("data/pages/$pageId.txt","r");
		if(!$fd) 
			die("err");
		while($line=fgets($fd)) 
			$out.=parse_line($line);
		fclose($fd);
	}
	else {
		$fd=gzopen(file_get_contents("data/attic/$pageId.$rev.gz"),"a");
		if(!$fd)
			die("err");
		while($line=gzgets($fd))
			$out.=parse_line($line);
		fclose($fd);
	}
	return($out);
}

function render_page_full($page, $rev="") {
	global $title,$toc;
	$pageId=san_pageId($page);
	$rp=render_page($page,$rev);
	$rp=preg_replace("/~~TOC~~/",$toc,$rp,1);
	$rp=preg_replace("/~~TOC~~/","",$rp);
	$pgh=preg_replace("/~~ID~~/",$pageId,file_get_contents("conf/htmlhead.tmpl"));
	$pgh=preg_replace("/~~TITLE~~/",$title,$pgh);
	$pgh=preg_replace("/~~SIDEBAR~~/",render_page("sidebar"),$pgh);
	return $pgh.$rp.file_get_contents("conf/htmlfoot.tmpl");

}

print render_page_full($_GET['id'],$_GET['rev']);

/*
print parse_line("~~FORM~~");
print parse_line("~~div testcla~~ bonjour ~~/div~~");
print parse_inline("{{http://www.microsoft.com/a.png}} {{:test.png}} {{test.pdf|taiste}} {{test.txt}}");
print parse_inline("[[truc@fr.fr]] hello [[truc]] [[bouh@fr.com]]");
print parse_inline("[[http://www.google.fr\"aha|ma<chin]] hello");
print parse_inline("hello **world** truc muche *much* //machin// __souligne **gras** //ouh//__");
print parse_line("===== test =====");
print parse_line("==== test1 ====");
print parse_line("== test kikoo ==");
print parse_line("  - hello");
print parse_line("  - taiste");
print parse_line("    - imbrique");
print parse_line("    - imbrique2");
print parse_line("  - out");
print parse_line("");
print $toc;


*/
