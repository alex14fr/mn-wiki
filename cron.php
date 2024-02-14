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

//dirty xml parser
function tagContents($ent, $tag) {
	$begin=strpos($ent,"<$tag");
	$begin2=strpos($ent,">",$begin);
	$end=strpos($ent,"</$tag>",$begin2);
	return substr($ent, $begin2+1, $end-$begin2-1)."\n";
}

function tagAttribute($ent, $tag, $attr, $which) {
	$offset=0;
	$ntries=0;
	while($offset<strlen($ent)&&$ntries<5) {
		++$ntries;
		$begin=strpos($ent,"<$tag",$offset);
		if($begin===false) return "";
		$end=strpos($ent,">",$begin);
		$offset=$end;
		$tagcnt=substr($ent,$begin,$end-$begin);
		if(strpos($tagcnt,$which)!==false) {
			$begin2=strpos($ent,"$attr=",$begin);
			$begin3=$begin2+strlen($attr)+2;
			$end=strpos($ent,"'",$begin3);
			return substr($ent,$begin3,$end-$begin3);
		}
	}
}

function catOnNFirst($str, $n, $tag, $func) {
	$out="";
	$offset=0;
	for($i=0;$i<$n;++$i) {
		$entryposBegin=strpos($str,"<$tag",$offset);
		if($entryposBegin===false) break;
		$entryposEnd=strpos($str,"</$tag>",$entryposBegin);
		$ent=substr($str,$entryposBegin,$entryposEnd-$entryposBegin);
		$out.=$func($ent);
		$offset=$entryposEnd;
	}
	return($out);
}

function lastnews2_cb($ent) {
	return "<div class=rssitem><div class=rsstitle>".tagContents($ent,"title")."</div><br><div class=rssdate>Published on ".date(DATE_RSS,strtotime(tagContents($ent,"published")))." by ".tagContents($ent,"name")."</div><div class=rssdesc>".html_entity_decode(tagContents($ent,"content"))."</div></div>";
}


function lastnews2()
{
    return "<div class=rssfeed>".catOnNFirst(file_get_contents("ephemeral/feedext.rss"), 5, "entry", "lastnews2_cb")."</div>";
}

function lastshort_cb($ent) {
	return "<li><a href=\"".tagAttribute($ent,"link","href","rel='alternate'")."\"><b>".tagContents($ent,"title")."</b></a>";
}

function lastshort()
{
    return "<ul>".catOnNFirst(file_get_contents("ephemeral/feedext.rss"), 5, "entry", "lastshort_cb")."</ul>";
}

function fetchurl($url, $gzip=false) {
#	return shell_exec("wget -O - ".escapeshellarg($url));
	if(substr($url,0,5)=="http:")
		return file_get_contents($url);
	else {
		$u=explode("//",$url);
		$uu=explode("/",$u[1]);
		$host=$uu[0];
		$path=substr($u[1],strlen($host));
		$pre=($gzip ? "ADD_HDR='Accept-encoding: gzip' " : "");
		$post=($gzip ? " | zcat" : "");
		return shell_exec($pre."httpsget ".escapeshellarg($host)." 443 ".escapeshellarg($path).$post);
	}
}

function updhal()
{
    $blacklist = file("data/Publis.Blacklist");
	 if($blacklist===false)
		 $blacklist = array();

    $solrQuery=    '(structId_i:(82792 OR 1189703))
                   OR (funding_t:(mascot-num OR rt-uq OR gdr-2172))
                   OR (comment_t:(mascot-num OR rt-uq OR gdr-2172))
                   OR (conference_t:(mascot-num OR rt-uq))
                   OR (collaboration_t:(mascot-num OR rt-uq OR gdr-2172))';

    $halurl = "https://haltools.archives-ouvertes.fr/Public/afficheRequetePubli.php?solrQuery=".urlencode($solrQuery)."&CB_ref_biblio=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=date_depot&ordre_aff=TA&Fen=Aff&css=../css/VisuRubriqueEncadre.css";

    $lines = explode("\n",fetchurl($halurl, true));
    $lines_ok = array();
    $inclure = false;
    foreach ($lines as $l) {
        if ($inclure) {
            $lines_ok[] = $l;
        }
        if (strpos($l, "<body") !== false) {
            $inclure = true;
        }
        if (strpos($l, "</body") !== false) {
            $inclure = false;
        }
    }
    array_pop($lines_ok);
    $lines = $lines_ok;
    $lines_ok = array();
    for ($i = 0; $i < count($lines); ++$i) {
        if ((strpos($lines[$i], "<div") !== false) || (strpos($lines[$i], "<p") !== false)) {
            $lines_ok[] = $lines[$i];
        } elseif (strpos($lines[$i], "<dl") !== false) {
            $sub = $lines[$i];
            for (++$i; ($i < count($lines)) && (strpos($lines[$i], "</dl") === false); ++$i) {
                $sub .= $lines[$i];
            }

            /* Gobble </dl> */
            if ($i < count($lines)) {
                $sub .= $lines[$i];
            }

            for (
                $j = 0; ($j < count($blacklist)) && (strpos($sub, trim($blacklist[$j])) === false);
                ++$j
            );
            if ($j == count($blacklist)) {
                $lines_ok[] = $sub;
            }
        }
    }
    return join("\n", $lines_ok);
}


$t = fetchurl("https://mascot-num.blogspot.com/feeds/posts/default", true);
file_put_contents("ephemeral/feedext.rss", $t);
file_put_contents("ephemeral/rss2.html", lastnews2());
file_put_contents("ephemeral/rssshort.html", lastshort());
file_put_contents("ephemeral/hal.html", updhal());
foreach (array("index","newsletter","documents") as $p) {
    if (file_exists("ephemeral/cache/$p")) {
        unlink("ephemeral/cache/$p");
    }
}
