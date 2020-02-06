<?php
include_once "utils.php";
canonical();
include_once "auth.php";
session_start();

$captch=false;

function checkRecaptcha() {
	global $captch;
	if(!$captch) return(true);

	$ctx=stream_context_create(array('http'=>array('method'=>'POST',
					'header'=>'Content-type: application/x-www-form-urlencoded\r\n',
					'content'=>http_build_query(array('secret'=>'googlesecret','response'=>$_POST['g-recaptcha-response']),'','&'))));
	$str=file_get_contents('https://www.google.com/recaptcha/api/siteverify',false,$ctx);
	$obj=json_decode($str,true);
	print "\r\n<!-- ** $str ** ->\r\n";
	return $obj['success'];
}

function ucname($string) {
	$string =ucwords(strtolower($string));

	foreach (array('-', '\'') as $delimiter) {
		if (strpos($string, $delimiter)!==false) {
			$string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
		}
	}
	return $string;
}

function db_query($qry) {
	global $db;
	try {
		return($db->query($qry));
	} catch(PDOException $e) {
		print 'erreur db: '.$e->getMessage();
	}
	print($db->errorInfo());
}

function protect($str) {
	$str=str_replace(';','',$str);
	$str=str_replace('\'','',$str);
	return $str;
}

function voitMails() {
	if(auth_isAdmin()) return true;

	return false;
}

function listeInscrits() {
	global $inscrits;
	global $msg;	
	global $id;
	$fich="data/rencontres.$id";
	if(file_exists($fich)) 
		$inscrits=file($fich,FILE_SKIP_EMPTY_LINES);
	else
		$inscrits=array();
	$inscrits2=array();
	foreach($inscrits as $usr) {
		if(trim($usr)) {
			list($nompre,$affil,$email)=explode(",",trim($usr));
			array_push($inscrits2,ucname(strtolower($nompre)).",".$affil.",".$email."\n");
		}
	}
	$r=db_query("SELECT * FROM inscrits WHERE idrencontre='$id'");
	foreach($r as $l) {
		array_push($inscrits2,$l['nomprenom'].','.$l['affiliation'].','.$l['mail']);
	}


	$inscrits2=array_unique($inscrits2);
	sort($inscrits2);
	$inscrits=$inscrits2;
	$inscrits=array_unique($inscrits);
	?>
	<ul>
	<?php
	$listeMails='';
	sort($inscrits);
	foreach($inscrits as $usr) {
		list($nompre,$affil,$email)=explode(",",$usr);
		print "<li><b>$nompre</b>, $affil";
		if(voitMails())  {
			print ", $email</li>";
			$listeMails.=trim($email).",";
		}
	}
	?>
	</ul>
	<?php
	if(voitMails()) {
		print "<textarea onclick=\"this.select()\" rows=10 cols=60>";
		print $listeMails;
		print "</textarea>";
	}
}

function formInscription() {
	global $id, $secret1, $secret2,$captch;
?>
<form method="post">
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type=hidden name=sectok value=<?php print md5($secret1.$id.$secret2); ?>>
<table style="margin-left:20px;margin-right:20px;border-collapse:collapse;border:1px solid black" border="0">
<tr><td><label for="nom">Family name</label><td><input id="nom" name="nom">
<tr><td><label for="prenom">First name</label><td><input id="prenom" name="prenom">
<tr><td><label for="email">Email adress<td><input id="email" name="email">
<tr><td><label for="affil">Affiliation</label><td><input id="affil" name="affil">
<?php if($captch) { ?><tr><td colspan=2><div class="g-recaptcha" data-sitekey="6LfllnEUAAAAABxznBVTJa5EsU2AlSkIyqtICE4w"></div><?php }?>
<tr><td colspan="2" style="text-align:center"><button type="submit" >Submit</button>
</table>
</form>
<?php 
} 

function backl() {
	global $id;
	print "<p><a href=".pageLink($id).">&lt;&lt; Back</a><p>";
}

$id=san_pageId($_REQUEST['id']);
if($_REQUEST['sectok']!=md5($secret1.$id.$secret2)) { print "E"; exit; }

print str_replace(array("~~TITLE~~","~~SIDEBAR~~","~~ACTIONS~~"),"",file_get_contents("conf/htmlhead.tmpl"));
print "<h1>Event ".$id."</h1>";
backl();

try {
	$db=new PDO("sqlite:$dbevents");
} catch(PDOException $e) {
	die ('db error'.$e->getMessage());
}

if(!empty($_POST['nom'])) {
	if(checkRecaptcha()) {
		$prenom=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["prenom"])));
		$affil=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["affil"])));
		$nom=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["nom"])));
		$email=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["email"])));
		$id=protect($id);
		$mnotif=$mailNotify["change"];
		array_push($mnotif, $email);

		db_query("INSERT INTO inscrits (idrencontre,nomprenom,mail,affiliation) VALUES('$id','".ucname(strtolower($nom.' '.$prenom))."','$email','$affil')");

		foreach($mnotif as $notif) 
			mail($notif, "Registration to $id", "
Your registration has been taken into account.

Name: ".$nom.", ".$prenom."
Affiliation: ".$affil."
Email: ".$email."

For more information, please consult ".pageLink($id,true),"From: $mailFrom\r\nContent-type: text/plain;charset=utf8\r\n");
		$msg='Your registration has been taken into account. ';
	}

	else {
		$msg='<span style="color:#ee1111;font-size:14pt;">RECAPTCHA error, please try again. </span>';
	}

	print $msg;

	print "<p><a href=".pageLink($id).">&lt;&lt; Back</a>";

	exit;
}

if(!empty($_REQUEST['action'])) 
	listeInscrits();
else
	formInscription();

?>
<?php backl(); ?>
Please contact <a href="mailto:gdr-mascotnum-admin ___at___ services.cnrs.fr">webmaster</a> for support requests.

