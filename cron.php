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


function lastnews2()
{
    $obj = simplexml_load_file("ephemeral/feedext.rss");
    $out = "<div class=\"rssfeed\">";
    for ($i = 0; $i < 5; $i++) {
        $a = $obj->entry[$i];
        if ($a) {
            $out .= "<div class=\"rssitem\" id=\"rssitem$i\"><div class=\"rsstitle\">" . $a->title . "</div><br/><div class=\"rssdate\">Published on: " . date(DATE_RSS, strtotime($a->published)) . " by " . $a->author->name . "</div><div id=\"desc$i\" class=\"rssdesc\">" . $a->content . "</div></div>";
        }
    }
    $out .= "</div>";
    return($out);
}

function lastshort()
{
    $obj = simplexml_load_file("ephemeral/feedext.rss");
    $out = "<ul>";
    for ($i = 0; $i < 5; $i++) {
        $a = $obj->entry[$i];
        if ($a) {
            $out .= "<li><a href=\"" . $a->link[4]->attributes()->href . "\"><strong>" . $a->title . "</strong></a>";
        }
    }
    $out .= "</ul>";
    return($out);
}

function updhal()
{
    $blacklist = file("data/Publis.Blacklist");

    /* solr query:    (structure_t:mascotnum)
                   OR (funding_t:mascotnum)
                   OR (comment_t:mascotnum)
                   OR (conference_t:mascotnum)
                   OR (collaboration_t:mascotnum) */
    $halurl = "https://haltools.archives-ouvertes.fr/Public/afficheRequetePubli.php?solrQuery=%28structure_t%3Amascotnum%29+OR+%28funding_t%3Amascotnum%29+OR+%28comment_t%3Amascotnum%29+OR+%28conference_t%3Amascotnum%29+OR+%28collaboration_t%3Amascotnum%29&CB_ref_biblio=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=date_depot&ordre_aff=TA&Fen=Aff&css=../css/VisuRubriqueEncadre.css";

    $lines = file($halurl);
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
    for ($i = 0; $i < count($lines); $i++) {
        if ((strpos($lines[$i], "<div") !== false) || (strpos($lines[$i], "<p") !== false)) {
            $lines_ok[] = $lines[$i];
        } elseif (strpos($lines[$i], "<dl") !== false) {
            $sub = $lines[$i];
            for ($i++; ($i < count($lines)) && (strpos($lines[$i], "</dl") === false); $i++) {
                $sub .= $lines[$i];
            }

            /* Gobble </dl> */
            if ($i < count($lines)) {
                $sub .= $lines[$i];
            }

            for (
                $j = 0; ($j < count($blacklist)) && (strpos($sub, trim($blacklist[$j])) === false);
                $j++
            );
            if ($j == count($blacklist)) {
                $lines_ok[] = $sub;
            }
        }
    }
    return join("\n", $lines_ok);
}


$t = file_get_contents("http://mascot-num.blogspot.com/feeds/posts/default");
file_put_contents("ephemeral/feedext.rss", $t);
file_put_contents("ephemeral/rss2.html", lastnews2());
file_put_contents("ephemeral/rssshort.html", lastshort());
file_put_contents("ephemeral/hal.html", updhal());
foreach (array("index","newsletter","documents") as $p) {
    if (file_exists("ephemeral/cache/$p")) {
        unlink("ephemeral/cache/$p");
    }
}
