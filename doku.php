<?php
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
		case "edit":

			exit;
		case "revisions":

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

	}
}

if(empty($_GET['id'])) $_GET['id']='index';
print render_page_full($_GET['id']);

