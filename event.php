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

include_once "utils.php";
canonical();
include_once "auth.php";
if (!isset($_SESSION)) {
    session_start();
}
sendCsp();
header("X-Robots-Tag: noindex");

$captch = false;

function checkRecaptcha()
{
    global $captch;
    if (!$captch) {
        return(true);
    }

    $ctx = stream_context_create(array('http' => array('method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded\r\n',
                    'content' => http_build_query(array('secret' => 'googlesecret','response' => $_POST['g-recaptcha-response']), '', '&'))));
    $str = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    $obj = json_decode($str, true);
    print "\r\n<!-- ** $str ** ->\r\n";
    return $obj['success'];
}

function ucname($string)
{
    $string = ucwords(strtolower($string));

    foreach (array('-', '\'') as $delimiter) {
        if (strpos($string, $delimiter) !== false) {
            $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
        }
    }
    return $string;
}

function protect($str)
{
    return san_csv($str);
}

function voitMails()
{
    if (auth_isAdmin()) {
        return true;
    }

    return false;
}

function listeInscrits()
{
    global $inscrits;
    global $msg;
    global $id;
    global $db;
    $res = $db->query("SELECT DISTINCT * FROM inscrits WHERE idrencontre='" . $db->escapeString($id) . "' ORDER BY nomprenom");
    $out = "<ul>";
	 $listeMailsA=array();
    while ($l = $res->fetchArray()) {
		 if(in_array(trim($l['mail']), $listeMailsA)) next;
		 $listeMailsA[]=trim($l['mail']);
        $out .= "<li><b>" . $l['nomprenom'] . "</b>, " . $l['affiliation'];
        if (voitMails()) {
            $out .= ", " . $l['mail'] . " <a href=\"".unsubLink($l['nomprenom'],$l['mail'])."\" target=_blank>cancel registration</a></li>";
        }
    }
	 $listeMails = explode(",", $listeMailsA);
    $out .= "</ul>";
    if (voitMails()) {
        $out .= "<textarea rows=10 cols=60>$listeMails</textarea>";
    }
    return $out;
}

function unsubToken($nompre,$mail) {
	global $id, $secret2;
	return hash_hmac("sha256",$nompre."/".$id."/".$mail,$secret2);
}

function unsubLink($nompre,$mail) {
	global $baseUrl, $id, $secret2;
	return $baseUrl."event.php?action=remove&id=$id&nompre=".urlencode($nompre)."&mail=".urlencode($mail)."&sectok=".unsubToken($nompre,$mail);
}

function formInscription()
{
    global $id, $secret1, $secret2,$captch;
    gen_xtok("event");
    ?>
<form method="post">
    <?php print pr_xtok("event"); ?>
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type=hidden name=sectok value=<?php print hash("sha256", $secret1 . $id . $secret2); ?>>
<table border="0">
<tr><td><label for="nom">Family name</label><td><input id="nom" name="nom">
<tr><td><label for="prenom">First name</label><td><input id="prenom" name="prenom">
<tr><td><label for="email">Email adress<td><input id="email" name="email">
<tr><td><label for="affil">Affiliation</label><td><input id="affil" name="affil">
    <?php if ($captch) {
        ?><tr><td colspan=2><div class="g-recaptcha" data-sitekey="6LfllnEUAAAAABxznBVTJa5EsU2AlSkIyqtICE4w"></div><?php
    }?>
<tr><td colspan="2" style="text-align:center"><button type="submit" >Submit</button>
</table>
</form>
    <?php
}

function backl()
{
    global $id;
    print "<p><a href=" . pageLink($id) . ">&lt;&lt; Back</a><p>";
}

$id = san_pageId($_REQUEST['id']);
if ($_GET['action']!='remove' && !hash_equals(hash("sha256", $secret1 . $id . $secret2), $_REQUEST['sectok'])) {
    print "E";
    exit;
}

print str_replace(array("~~TITLE~~","~~SIDEBAR~~","~~ACTIONS~~"), "", file_get_contents("conf/htmlhead.tmpl"));
print "<h1>Event " . $id . "</h1>";
backl();

try {
    $db = new SQLite3("$dbevents");
} catch (Exception $e) {
    die("db error " . $e->getMessage());
}

if (!empty($_POST['nom'])) {
    if (chk_xtok("event") || checkRecaptcha()) {
        $prenom = protect(htmlspecialchars(preg_replace("/,/", " ", $_POST["prenom"])));
        $affil = protect(htmlspecialchars(preg_replace("/,/", " ", $_POST["affil"])));
        $nom = protect(htmlspecialchars(preg_replace("/,/", " ", $_POST["nom"])));
        $email = protect(htmlspecialchars(preg_replace("/,/", " ", $_POST["email"])));
        $email = san_csv($email);
        $id = protect($id);
        $mnotif = $mailNotify["change"];
        array_push($mnotif, $email);

        if (!   $db->exec("INSERT INTO inscrits (idrencontre,nomprenom,mail,affiliation) VALUES('" . $db->escapeString($id) . "','" . $db->escapeString(ucname(strtolower($nom . ' ' . $prenom))) . "','" . $db->escapeString($email) . "','" . $db->escapeString($affil) . "')")) {
            die("db error insert " . $db->lastErrorMsg());
        }

        foreach ($mnotif as $notif) {
            xmail($notif, "Registration to $id", "
Your registration has been taken into account.

Name:        " . $nom . ", " . $prenom . "
Affiliation: " . $affil . "
Email:       " . $email . "

For more information, please consult " . pageLink($id, true)."

To cancel your registration, click on the following link : 

".unsubLink(ucname(strtolower($nom.' '.$prenom)), $email)."

");
        }
        $msg = 'Your registration has been taken into account. ';
    } else {
        $msg = 'CAPTCHA or xtoken error, please try again';
    }

    print $msg;

    print "<p><a href=" . pageLink($id) . ">&lt;&lt; Back</a>";

    exit;
}

if ($_GET['action']=='remove') {
	if(!hash_equals(unsubToken($_GET['nompre'],$_GET['mail']), $_GET['sectok'])) {
		die("E");
	}
	if(!$db->exec("DELETE FROM inscrits WHERE idrencontre='".$db->escapeString($id)."' AND nomprenom='".$db->escapeString(ucname(strtolower($_GET['nompre'])))."' AND mail='".$db->escapeString($_GET['mail'])."'")) {
		die("db error delete ".$db->lastErrorMsg());
	} else {
		print "Your registration to $id has been canceled. ";
		exit;
	}
}


if (!empty($_REQUEST['action'])) {
    print listeInscrits();
} else {
    formInscription();
}

?>
<?php backl(); ?>
Please contact <a href="mailto:gdr-mascotnum-admin ___at___ services.cnrs.fr">webmaster</a> for support requests.

