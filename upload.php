<?php
include_once "auth.php";
include_once "utils.php";
include_once "conf/conf.php";

if(!auth_isContrib())
	exit;

$ip=$_SERVER["REMOTE_ADDR"];
$ip=$ip." (".gethostbyaddr($ip).")";

function check_allowed($fn) {
	global $ext_ok;
	if(strpos($fn,".")===false) { die("error: empty extension not allowed"); }
	$ext=substr($fn,strrpos($fn,".")+1);
	if(!in_array($ext,$ext_ok)) {
		die("error : extension $ext not allowed");
	}
}

if($_FILES['fich']) {
	$desti=basename($_FILES["fich"]["name"]);
	if($_POST['nom'])
		$desti=$_POST['nom'];
	$desti=san_filename($desti);
	check_allowed($desti);
	if(!move_uploaded_file($_FILES["fich"]["tmp_name"], "$mediaDir/$desti")) 
		die("erreur d'upload");
	mail_notify('Fichier ajouté : '.$desti, "Le fichier:\r\n\r\n   $url/$mediaDir/$desti\r\n\r\na été ajouté par ".$_SERVER['REMOTE_USER']. ' ; IP='.$ip.'). ' );
}

if($_GET['delete']) {
	mail_notify('Fichier supprimé : '.$_GET['delete'], 'Le fichier '.$_GET['delete'].' a été supprimé par '.$_SERVER['REMOTE_USER']. ' ; IP='.$ip.'). ');
	unlink("$mediaDir/".basename($_GET['delete']));
}

?>
<!doctype html>
<body>
<h2>Envoi de fichiers</h2>
<form enctype='multipart/form-data' method='post'>
<label>
Fichier :
<input type="file" name="fich">
</label>
</div>
<div>
<label>
Nom (laisser vide pour nom par défaut) : 
<input name="nom">
</div>
<input type=submit value=Envoyer>
</form>
<h2>Liste des fichiers</h2>
<ul>
<?php
$d=scandir('data/media/');
foreach($d as $dd) {
	if($dd != '.' && $dd != '..')
		print "<li><a href='$mediaDir/$dd'>$dd</a> <button href='?delete=$dd'>Supprimer</button>";
}
?>
</ul>
</body>
</html>
