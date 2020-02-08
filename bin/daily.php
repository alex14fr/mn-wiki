<?php
function lastnews2() {
	$obj=simplexml_load_file("static/feedext.rss");
	$out="<div class=\"rssfeed\">";
	for($i=0;$i<5;$i++) {
		$a=$obj->entry[$i];
		if($a) {
			$out .= "<div class=\"rssitem\" id=\"rssitem$i\"><div class=\"rsstitle\" id=\"title$id\">".$a->title."</div><br/><div class=\"rssdate\">Published on: ".date(DATE_RSS,strtotime($a->published))." by ".$a->author->name."</div><div id=\"desc$id\" class=\"rssdesc\">".$a->content."</div></div>";
		}
	}
	$out.="</div>";
	return($out);
}

function lastshort() {
	$obj=simplexml_load_file("static/feedext.rss");
	$out .= "<ul>"; 
	for($i=0;$i<5;$i++) {
		$a=$obj->entry[$i];
		if($a) {
			$out .= "<li><a href=\"".$a->link[4]->attributes()->href."\"><strong>".$a->title."</strong></a>"; 
		}
	}
	$out .= "</ul>";
	return($out);
}

file_put_contents("static/feedext.rss",file_get_contents("http://mascot-num.blogspot.com/feeds/posts/default"));
file_put_contents("static/rss2.html",lastnews2());
file_put_contents("static/rssshort.html",lastshort());
unlink("ephemeral/cache/index");
unlink("ephemeral/cache/newsletter");
system("bin/updhal");

