<?php
include_once "canonical.php";

error_reporting(E_STRICT|E_ALL);
include_once "parse.php";
include_once "auth.php";
session_start();
if(!empty($_GET['do'])) {
	switch($_GET['do']) {
		case "register":
		case "resendpwd":
		case "login":
			print render_html(file_get_contents("conf/".$_GET['do'].".tmpl"));
			exit;
		case "reinitpwd":
			exit;
		case "logout":
			auth_logout();
			break;
		case "release":
			if($_SESSION['auth_user']) {
				$pageId=san_pageId($_GET['id']);
				if(file_get_contents("data/locks/$pageId")==$_SESSION['auth_user']) {
					unlink("data/locks/$pageId");
					header("Location: doku.php?id=$pageId");
				}
				exit;
			}
			break;
		case "edit":
			if(!auth_isContrib()) { die("not yet authorized"); }
			$pageId=san_pageId($_GET['id']);
			$lockfile="data/locks/$pageId";
			$locktime=300;
			if(file_exists($lockfile)) 
				$locked_until=filemtime($lockfile)+$locktime;
			else
				$locked_until=0;
			if(time()<$locked_until) { 
				$locked_by=file_get_contents($lockfile);
				if($locked_by!=$_SESSION['auth_user'])
					die("locked by $locked_by until ".date('H:i:s T',filemtime($lockfile))); }
			file_put_contents($lockfile,$_SESSION['auth_user']);
			if(!empty($_GET['rev']))  {
				$rev=san_pageRev($_GET['rev']);
				if(!is_readable("$atticDir/$pageId.$rev.txt.gz")) { die("err"); }
				$pagetxt=implode(gzfile("$atticDir/$pageId.$rev.txt.gz"));
			} else {
				if(!is_writable("$pageDir/$pageId.txt")) {
					$pagetxt="";
				} else {
					$pagetxt=file_get_contents("$pageDir/$pageId.txt");
				}
			}
			clearstatcache();
			$tmpl=file_get_contents("conf/edit.tmpl");
			$tmpl=str_replace(array("~~ID~~","~~TXT~~","~~LOCK_UNTIL~~"),
									array($pageId,$pagetxt,date('H:i:s T',filemtime($lockfile)+$locktime)),$tmpl);
			print render_html($tmpl);
			exit;
		case "revisions":
			if(!auth_isContrib()) { die("not yet authorized"); }
			$pageId=san_pageId($_GET['id']);
			$out="<h1>Revisions of ".$pageId."</h1><table border=0>";
			$chgset=array_reverse(file("$metaDir/$pageId.changes"));
			$first=true;
			foreach($chgset as $chg) {
				$chgs=explode("\t",$chg);
				$out.="<tr><td>".date('y/m/d H:i',$chgs[0]).
				" <a href=doku.php?id=$pageId&rev=".($first ? "" : $chgs[0]).">View</a>".
				" <a href=doku.php?id=$pageId&rev=".($first ? "" : $chgs[0])."&do=edit>Revert</a>".
				" <td>".$chgs[5].
				" <span style=color:#888>".$chgs[4]." (".$chgs[1].")</span>";
				if($first)
					$first=false;
			}
			$out.="</table>";
			print render_html($out);
			exit;
		case "resendpwd2":
			if(empty($_GET['u']) || empty($_GET['tok'])) die("resendpwd2 error 1");
			auth_resendpwd2($_GET['u'], $_GET['tok']);
			print "An email with your new password has been sent. ";
			exit;
		case "addcontributor":
			auth_addcontributor($_GET['login'],$_GET['mail'],$_GET['hash']);
			print "added as contributor";
			exit;
		default:
			print "unsupported do";
			exit;
	}
}

if(!empty($_POST['do'])) {
	switch($_POST['do']) {
		case "login":
			if(empty($_POST['u']) || empty($_POST['p'])) {
				die("empty username or password");
			}
			if(!auth_login($_POST['u'], $_POST['p'])) {
				die("wrong username or password");
			}
			header("Location: doku.php");
			exit;

		case "resendpwd":
			if(empty($_POST['e'])) { die("empty email"); }
			if(auth_resendpwd1($_POST['e'])) {
				print "An email with your login and a link to reset your password has been sent. ";
			} else {
				print "Email address not found. ";
			}
			exit;

		case "register":
			if(empty($_POST['e'])||empty($_POST['u'])||empty($_POST['n'])||empty($_POST['p'])||empty($_POST['p2'])) {
				die("all form fields are required");
			}
			auth_register($_POST['u'],$_POST['p'],$_POST['p2'],$_POST['n'],$_POST['e']);
			print "Registration successful. <a href=\"?do=login\">Go to login page</a>";
			exit;

		case "edit":
			if(!auth_isContrib()) { die("not yet authorized"); }
			$pageId=san_pageId($_POST['id']);
			if(is_readable("$pageDir/$pageId.txt")) {
				$oldmt=filemtime("$pageDir/$pageId.txt");
				$oldtext=file_get_contents("$pageDir/$pageId.txt");
				$gzd=gzencode($oldtext,9);
				file_put_contents("$atticDir/$pageId.$oldmt.txt.gz", $gzd);
			}
			$newtext=$_POST['txt'];
			file_put_contents("$pageDir/$pageId.txt", $newtext);
			clearstatcache();
			$mt=filemtime("$pageDir/$pageId.txt");
			$ps=trim(substr($_POST['summary'],0,50));
			$cline="$mt\t".$_SERVER['REMOTE_ADDR']."\tE\t$pageId\t".$_SESSION['auth_user']."\t".$ps."\n";
			file_put_contents("$metaDir/$pageId.changes",$cline,FILE_APPEND|LOCK_EX);
			unlink("data/locks/$pageId");
			sendNotify("Page $pageId changed", "
			Username:     ".$_SESSION['auth_user']."
			IP:           ".$_SERVER['REMOTE_ADDR']."

			Old revision: ".$baseUrl."/doku.php?id=$pageId&rev=$oldmt
			New revision: ".$baseUrl."/doku.php?id=$pageId&rev=$mt

			".textDiff($oldtext,$newtext),"");
			
			header("Location: doku.php?id=$pageId");
			exit;
	}
}

if(empty($_GET['id'])) $_GET['id']='index';
if(empty($_GET['rev']))
	print render_page_full($_GET['id']);
else
	print render_page_full($_GET['id'],$_GET['rev']);

