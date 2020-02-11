<?php
function lastnews2() {
	$obj=simplexml_load_file("static/feedext.rss");
	$out="<div class=\"rssfeed\">";
	for($i=0;$i<5;$i++) {
		$a=$obj->entry[$i];
		if($a) {
			$out .= "<div class=\"rssitem\" id=\"rssitem$i\"><div class=\"rsstitle\">".$a->title."</div><br/><div class=\"rssdate\">Published on: ".date(DATE_RSS,strtotime($a->published))." by ".$a->author->name."</div><div id=\"desc$id\" class=\"rssdesc\">".$a->content."</div></div>";
		}
	}
	$out.="</div>";
	return($out);
}

function lastshort() {
	$obj=simplexml_load_file("static/feedext.rss");
	$out = "<ul>"; 
	for($i=0;$i<5;$i++) {
		$a=$obj->entry[$i];
		if($a) {
			$out .= "<li><a href=\"".$a->link[4]->attributes()->href."\"><strong>".$a->title."</strong></a>"; 
		}
	}
	$out .= "</ul>";
	return($out);
}

function updhal() {
	$blacklist=file("data/Publis.Blacklist");
	$halurl="https://haltools.archives-ouvertes.fr/Public/afficheRequetePubli.php?labos_exp=gdr+mascot-num&CB_ref_biblio=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=date_depot&ordre_aff=TA&Fen=Aff&css=../css/VisuRubriqueEncadre.css";
	$lines=file($halurl);
	$lines_ok=array();
	$inclure=false;
	foreach($lines as $l) {
		if($inclure) $lines_ok[]=$l;
		if(strpos($l,"<body")!==false) $inclure=true;
		if(strpos($l,"</body")!==false) $inclure=false;
	}
	array_pop($lines_ok);
	$lines=$lines_ok;
	$lines_ok=array();
	for($i=0; $i<count($lines); $i++) {
		if( (strpos($lines[$i],"<div")!==false) || (strpos($lines[$i],"<p")!==false) ) {
			$lines_ok[]=$lines[$i];
		} else if(strpos($lines[$i],"<dl")!==false) {
			$sub=$lines[$i];
			for($i++; ($i<count($lines)) && (strpos($lines[$i],"</dl")===false); $i++)
				$sub.=$lines[$i];
			if($i<count($lines))
				$sub.=$lines[$i++];

			for($j=0; ($j<count($blacklist)) && (strpos($sub,trim($blacklist[$j]))===false) ; $j++);
			if($j==count($blacklist))
				$lines_ok[]=$sub;
		}
	}
	return join("\n",$lines_ok);
}



file_put_contents("static/feedext.rss",file_get_contents("http://mascot-num.blogspot.com/feeds/posts/default"));
file_put_contents("static/rss2.html",lastnews2());
file_put_contents("static/rssshort.html",lastshort());
file_put_contents("static/hal.html",updhal());
unlink("ephemeral/cache/index");
unlink("ephemeral/cache/newsletter");
unlink("ephemeral/cache/documents");

