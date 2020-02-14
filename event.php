<?php
include_once "utils.php";
canonical();
include_once "auth.php";
if(!isset($_SESSION)) session_start();
sendCsp();
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

function protect($str) {
	return san_csv($str);
}

function voitMails() {
	if(auth_isAdmin()) return true;

	return false;
}

function listeInscrits() {
	global $inscrits;
	global $msg;	
	global $id;
	global $db;
	$res=$db->query("SELECT * FROM inscrits WHERE idrencontre='".$db->escapeString($id)."' ORDER BY nomprenom");
	$out="<ul>";
	while($l=$res->fetchArray()) {
	//	array_push($inscrits2,$l['nomprenom'].','.$l['affiliation'].','.$l['mail']);
		$out .= "<li><b>".$l['nomprenom']."</b>, ".$l['affiliation'];
		if(voitMails())  {
			$out .=", ".$l['mail']."</li>";
			$listeMails.=trim($l['mail']).",";
		}
	}
	$out.="</ul>";
	if(voitMails()) {
		$out.="<textarea rows=10 cols=60>$listeMails</textarea>";
	}
	return $out;
}

function formInscription() {
	global $id, $secret1, $secret2,$captch;
	gen_xtok("event");
?>
<form method="post">
<?php print pr_xtok("event"); ?>
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type=hidden name=sectok value=<?php print hash("sha256",$secret1.$id.$secret2); ?>>
<table border="0">
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
if(!hash_equals(hash("sha256",$secret1.$id.$secret2),$_REQUEST['sectok'])) { print "E"; exit; }

print str_replace(array("~~TITLE~~","~~SIDEBAR~~","~~ACTIONS~~"),"",file_get_contents("conf/htmlhead.tmpl"));
print "<h1>Event ".$id."</h1>";
backl();

try {
	$db=new SQLite3("$dbevents");
} catch(Exception $e) {
	die("db error ".$e->getMessage());
}

if(!empty($_POST['nom'])) {
	if(chk_xtok("event") || checkRecaptcha()) {
		$prenom=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["prenom"])));
		$affil=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["affil"])));
		$nom=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["nom"])));
		$email=protect(htmlspecialchars(preg_replace("/,/"," ",$_POST["email"])));
		$email=san_csv($email);
		$id=protect($id);
		$mnotif=$mailNotify["change"];
		array_push($mnotif, $email);

		if(!   $db->exec("INSERT INTO inscrits (idrencontre,nomprenom,mail,affiliation) VALUES('".$db->escapeString($id)."','".$db->escapeString(ucname(strtolower($nom.' '.$prenom)))."','".$db->escapeString($email)."','".$db->escapeString($affil)."')") ) {
			die("db error insert ".$db->lastErrorMsg());
		}

		foreach($mnotif as $notif) 
			xmail($notif, "Registration to $id", "
Your registration has been taken into account.

Name: ".$nom.", ".$prenom."
Affiliation: ".$affil."
Email: ".$email."

For more information, please consult ".pageLink($id,true));
		$msg='Your registration has been taken into account. ';
	}

	else {
		$msg='CAPTCHA or xtoken error, please try again';
	}

	print $msg;

	print "<p><a href=".pageLink($id).">&lt;&lt; Back</a>";

	exit;
}

if(!empty($_REQUEST['action'])) 
	print listeInscrits();
else
	formInscription();

?>
<?php backl(); ?>
Please contact <a href="mailto:gdr-mascotnum-admin ___at___ services.cnrs.fr">webmaster</a> for support requests.

