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

include_once "conf/conf.php";

function auth_getline($login)
{
    global $pwdFile;
    $fd = fopen($pwdFile, "r");
    if (!$fd) {
        die("can't open passwd file");
    }
    while ($line = fgets($fd)) {
        if (strpos('#', $line)) {
            next;
        }

        $lspl = explode(':', $line);

        if ($lspl[0] === $login) {
            return $lspl;
        }
    }
    return false;
}

function auth_hashpass($login, $pass)
{
        return password_hash($pass, PASSWORD_DEFAULT);
}

function auth_login($login, $pass)
{
    $login = san_filename($login);
    $lspl = auth_getline($login);
    if ($lspl) {
        $hashpass = $lspl[1];
        if (password_verify($pass, $hashpass)) {
            auth_changePassword($login, $pass);
				allow_login($login);
            return(true);
        } else {
            return(false);
        }
    }
    return(false);
}

function auth_getgroups()
{
    if (empty(get_login())) {
        return(array());
    }
    $lspl = auth_getline(get_login());
    if ($lspl) {
        return(explode(',', trim($lspl[4])));
    }
    return(array());
}

function auth_isContrib()
{
    return(in_array("contributor", auth_getgroups(), TRUE) || auth_isAdmin() || auth_isCommittee() );
}

function auth_isCommittee()
{
    return(in_array("committee", auth_getgroups(), TRUE) || auth_isAdmin());
}

function auth_canEdit($id)
{
	global $pageDir, $editableDir, $permFile;

	if(auth_isAdmin() || auth_isCommittee())
		return true;

	if(file_exists($editableDir . "/" . $id) && auth_isContrib())
		return true;

	if(!empty(get_login()) && in_array(get_login() . ":" . $id, file($permFile,FILE_IGNORE_NEW_LINES), TRUE)) 
		return true;

	return false;
}

function auth_isAdmin()
{
    return(in_array("admin", auth_getgroups(), TRUE));
}

function auth_logout()
{
	setcookie(get_login_cookie_name(), "");
}

function genrepwhash($login, $curpwd)
{
    global $secret1, $secret2, $secret3;
    return hash_hmac('sha256', $login . $curpwd, $secret3 . $secret1);
}

function auth_resendpwd1($email)
{
    global $secret1, $secret2, $secret3, $pwdFile;
    global $baseUrl, $mailFrom;
    $email = san_csv($email);
    $crlf = "\r\n";
    $fd = fopen($pwdFile, "r");
    if (!$fd) {
        die("can't open passwd file");
    }
    while ($line = fgets($fd)) {
        if (strpos('#', $line) !== false) {
            next;
        }

        $lspl = explode(':', $line);

        if (!empty($lspl[3]) && $lspl[3] === $email) {
            $login = $lspl[0];
            $sectok = genrepwhash($login, $lspl[1]);
            $mailtext = " Someone (probably you) claimed for lost credentials for the Wiki at $baseUrl . " . $crlf . $crlf . " Your username is $login. " . $crlf . $crlf . " To reset your password, please visit $baseUrl" . "/index.php?do=resendpwd2&u=$login&tok=$sectok " . $crlf . $crlf . " If not, you can safely ignore this message. ";
            xmail($email, "Password reset", $mailtext);
            return(true);
        }
    }
    return(false);
}


function generatePassword($length = 8)
{
    $password = "";
    $possible = "234678923456789%%%%!!!!!*****aeubcdfghjkmnpqrtvwxyzAEUBCDFGHJKLMNPQRTVWXYZ";
    $maxlength = strlen($possible);
    if ($length > $maxlength) {
        $length = $maxlength;
    }
    $i = 0;
    while ($i < $length) {
        $char = substr($possible, mt_rand(0, $maxlength - 1), 1);
        if (!strstr($password, $char)) {
            $password .= $char;
            ++$i;
        }
    }
    return $password;
}

function auth_lockPasswd()
{
    global $pwdFile;
    $lockFile = $pwdFile . ".lock";
    $i = 0;
    while ($i < 10 && file_exists($lockFile) && filemtime($lockFile) + 10 > time()) {
        sleep(1);
        ++$i;
    }
    if ($i == 10) {
        die("timeout while locking passwd file");
    }
}

function auth_releaseLockPasswd()
{
    global $pwdFile;
    @unlink($pwdFile . ".lock");
}

function auth_rewriteLockedPasswd($newcontent)
{
    global $pwdFile;
    $lockFile = $pwdFile . ".lock";
    $tempfile = dirname($pwdFile) . "/" . get_random() . "_xx.php";
    if (!file_put_contents($tempfile, $newcontent)) {
        auth_releaseLockPasswd();
        die("can't write new temp file");
    }
    if (!rename($tempfile, $pwdFile)) {
        auth_releaseLockPasswd();
        die("can't rename to passwd ");
    }
}

function auth_changeUser($login, $newline)
{
    global $pwdFile;
    $newfile = "";
    auth_lockPasswd();
    $fd = fopen($pwdFile, "r");
    if (!$fd) {
        auth_releaseLockPasswd();
        die("can't open passwd file in r mode");
    }
    while ($line = fgets($fd)) {
        $lspl = explode(':', $line);
        if (!$login || $lspl[0] !== $login) {
            $newfile .= $line;
        }
    }
    fclose($fd);
    $newfile .= $newline;
    auth_rewriteLockedPasswd($newfile);
    auth_releaseLockPasswd();
}

function auth_changePassword($login, $newpass)
{
    $lspl = auth_getline($login);
    $lspl[1] = auth_hashPass($login, $newpass);
    auth_changeUser($login, implode(':', $lspl));
}

function auth_resendpwd2($login, $tok)
{
    global $mailFrom;
    $lspl = auth_getline($login);
    if (genrepwhash($login, $lspl[1]) !== $tok) {
        die("resendpwd2 error 2");
    }
    $newpass = generatePassword();
    $mailto = $lspl[3];
    auth_changePassword($login, $newpass);
    xmail($mailto, "Your password", "Your password is : $newpass");
}

function auth_register($u, $p, $p2, $n, $e, $sup)
{
    global $pwdFile, $secret3, $baseUrl, $clientIp;
    if ($p !== $p2) {
        die("passwords don't match");
    }
    if (strlen($p) < 7) {
        die("password is too short");
    }
    if ($u !== san_filename($u)) {
        die("invalid username: must be <64 chars long, lowercase, a-z 0-9 - _ .");
    }
    if ($e !== san_csv($e)) {
        die("mail address contains invalid characters");
    }
    if (auth_getline($u)) {
        die("login already exists");
    }
    $n = san_csv($n);
    $hash = auth_hashPass($u, $p);
    $line = "$u:$hash:$n:$e:user\n";
    auth_changeUser(false, $line);

    $hash = hash_hmac('sha256', $u, $secret3);
	 $suplInfo="";
	 foreach($sup as $fld=>$val) 
		 $suplInfo .= "   $fld:   ".str_replace("&amp;","&",$val)."\r\n";
    sendNotify("register", "Moderation request", "New user registered on wiki : \r\n
   Username:    $u
   Real name:   $n
   Email:       $e
   IP:          $clientIp (" . gethostbyname($clientIp) . ")\r\n
$suplInfo\r\n
Visit the following link to grant him edit rights:\r\n 
    $baseUrl" . "?do=addcontributor&login=$u&mail=$e&hash=$hash");
}


function auth_addcontributor($login, $mail, $hash)
{
    global $secret3, $baseUrl, $mailFrom;
    $hashok = hash_hmac('sha256', $login, $secret3);
    //print "hashok=$hashok   hash=$hash\n";
    if (!hash_equals($hashok, $hash)) {
        die('invalid link');
    }
    $lspl = auth_getline($login);
    if (strpos($lspl[4], "contributor") !== false) {
        die("already a contributor");
    }
    $lspl[4] = "contributor," . $lspl[4];
    auth_changeUser($login, implode(":", $lspl));
    sendNotify("register", "Edit rights granted", "To username $login");
    $mailtxt = "The administrator of the wiki at $baseUrl has accepted 
you as a contributor.

This means you can edit pages on the wiki to add any
information you think the community may be interested in.

A good way to start is to add yourself (in alphabetical order please) to the
User list. To do this, click on the following link:

        $baseUrl" . "?id=users

and choose 'Edit this page' on the left. If this link does not appear, use the
Login link first (your login is $login).

Yours faithfully.";
        xmail($mail, "Edit rights granted", $mailtxt);
}
