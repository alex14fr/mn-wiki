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

include_once "parse.php";
include_once "auth.php";

$addCsp = "";
if (!empty($_GET['do']) && $_GET['do'] == 'edit') {
    $addCsp = "script-src 'self'; connect-src 'self'";
}
sendCsp($addCsp);

if (empty($_GET['id'])) {
    $pageId = 'index';
} else {
    $pageId = san_pageId($_GET['id']);
}

if (empty($_GET['do']) && empty($_POST['do'])) {
	$etag=md5(get_login().$pageId.file_get_contents("commit_id").filemtime($pageDir."/$pageId.txt"));
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		$ss1=substr($_SERVER['HTTP_IF_NONE_MATCH'],0,8);
		$ss2=substr($etag,0,8);
		if($ss1==$ss2) {
			header("302 Not modified");
			exit;
		}
	}
	header("Etag: $etag");
	if (empty($_GET['rev'])) {
		 print render_page_full($pageId);
	} else {
		 print render_page_full($pageId, san_pageRev($_GET['rev']));
	}
	exit;
}


if (!empty($_GET['do'])) {
	header("X-Robots-Tag: noindex");
    switch ($_GET['do']) {
        case "register":
        case "resendpwd":
        case "login":
            gen_xtok("default");
            print render_html(str_replace(
                array("~~XTOK~~","~~ID~~"),
                array(pr_xtok("default"),$pageId),
                readtmpl($_GET['do'])
            ));
            exit;
        case "reinitpwd":
            exit;
        case "logout":
            auth_logout();
				header("Location: index.php");
            exit;
        case "release":
            if (!empty(get_login())) {
                if (file_get_contents("$lockDir/$pageId") === get_login()) {
                    unlink("$lockDir/$pageId");
                    header("Location: " . pageLink($pageId));
                }
                exit;
            }
            break;
        case "edit":
            if (!auth_isContrib()) {
                die403("not yet authorized, your edit rights are under review");
            }
				if (!auth_canEdit($pageId)) {
					die403("not authorized");
				}
            gen_xtok("edit_$pageId");
            $lockfile = "$lockDir/$pageId";
            if (file_exists($lockfile)) {
                $locked_until = filemtime($lockfile) + $locktime;
            } else {
                $locked_until = 0;
            }
            if (time() < $locked_until) {
                $locked_by = file_get_contents($lockfile);
                if ($locked_by !== get_login()) {
                    die("locked by $locked_by until " . date('H:i:s T', filemtime($lockfile)));
                }
            }
            file_put_contents($lockfile, get_login());
            if (!empty($_GET['rev'])) {
                $rev = san_pageRev($_GET['rev']);
                if (!is_readable("$atticDir/$pageId.$rev.txt.gz")) {
                    die("err");
                }
                $pagetxt = implode(gzfile("$atticDir/$pageId.$rev.txt.gz"));
            } else {
                if (!is_writable("$pageDir/$pageId.txt")) {
                    $pagetxt = "";
                } else {
                    $pagetxt = file_get_contents("$pageDir/$pageId.txt");
                }
            }
            clearstatcache();
            $tmpl = readtmpl("edit");
            $tmpl = str_replace(
                array("~~XTOK~~","~~ID~~","~~TXT~~","~~LOCK_UNTIL~~"),
                array(pr_xtok("edit_$pageId"),$pageId,$pagetxt,date('H:i:s T', filemtime($lockfile) + $locktime)),
                $tmpl
            );
            print render_html($tmpl);
            exit;
        case "revisions":
            if (!auth_isCommittee()) {
                die403("not authorized");
            }
            $out = "<h1>Revisions of " . $pageId . "</h1><form><input type=hidden name=do value=diff><input type=hidden name=id value=$pageId><input type=submit value=\"Diff selected\"><p>";
            $chgsetInfo = file("$metaDir/$pageId.changes");
            $first = true;
				$second = false;
				$chgset = array();
				$tss = array();
				foreach(glob("$atticDir/$pageId.*.txt.gz") as $f) {
					$fs = explode(".",$f);
					$chgset[]=array('ts'=>$fs[1],'msg'=>'x', 'author'=>'x'); 
					$tss[]=$fs[1];
				}
				$lastts=filemtime("$pageDir/$pageId.txt");
				$chgset[]=array('ts'=>$lastts,'msg'=>'x','author'=>'x');
				$tss[]=$lastts;
            foreach ($chgsetInfo as $chg) {
					if(strpos($chg,"\x00")===false) {
						 $chgs = explode("\t", $chg);
						 if($ii=array_search($chgs[0],$tss)) 
							 $chgset[$ii]=array('ts'=>$chgs[0], 'msg'=>$chgs[5], 'author'=>$chgs[4]." (".$chgs[1].")");
					}
				}

				foreach (array_reverse($chgset) as $info) {
					$ts=$info['ts'];
                $out .= "<input type=radio name=diffA value=" . ($first ? "\"\" checked=1 " : $ts) . ">" .
					"<input type=radio name=diffB value=" . ($first ? "\"\"" : $ts) . ($second ? " checked=1" : "") . ">" .
					 date('y/m/d H:i T', $ts) .
                " <a href=?id=$pageId&rev=" . ($first ? "" : $ts) . ">View</a>" .
                " <a href=?id=$pageId&rev=" . ($first ? "" : $ts) . "&do=edit>Revert</a>" .
                " " . $info['msg'] .
                " <span class=aIp>" . $info['author'] . "</span><br>";
					 if ($second) {
						 $second = false;
					 }
                if ($first) {
                    $first = false;
						  $second = true;
                }
            }
            print render_html($out);
            exit;
        case "resendpwd2":
            if (empty($_GET['u']) || empty($_GET['tok'])) {
                die("resendpwd2 error 1");
            }
            $u = san_filename($_GET['u']);
            $tok = san_filename($_GET['tok']);
            auth_resendpwd2($u, $tok);
            print "An email with your new password has been sent. ";
            exit;
        case "addcontributor":
            $u = san_filename($_GET['login']);
            $h = san_filename($_GET['hash']);
            auth_addcontributor($u, $_GET['mail'], $h);
            print "added as contributor";
            exit;
			case "allowEdit":
				if(!auth_isAdmin()) {
					die403("unauthorized");
				}
				print "<form method=post action=index.php><input type=hidden name=id value=$pageId><input type=hidden name=do value=allowEdit>";
				print pr_xtok();
				print "Confirm to set page $pageId contrib-writable.  <input type=submit value=Confirm></form><p><a href=$pageId.html>Back</a>";
				exit;
			case "revokeEdit":
				if(!auth_isAdmin()) {
					die403("unauthorized");
				}
				print "<form method=post action=index.php><input type=hidden name=id value=$pageId><input type=hidden name=do value=revokeEdit>";
				print pr_xtok();
				print "Confirm to set page $pageId not contrib-writable.  <input type=submit value=Confirm></form><p><a href=$pageId.html>Back</a>";
				exit;
		  case "diff":
				if(!auth_isCommittee()) {
					die403("unauthorized");
				}
				$r1=san_filename($_GET['diffA']);
				$r2=san_filename($_GET['diffB']);
				$f1=(empty($r1) ? file_get_contents("$pageDir/$pageId.txt") : implode(gzfile("$atticDir/$pageId.$r1.txt.gz")));
				$f2=(empty($r2) ? file_get_contents("$pageDir/$pageId.txt") : implode(gzfile("$atticDir/$pageId.$r2.txt.gz")));
				$t1=(empty($r1) ? "current revision" : date('y/m/d H:i T', $r1));
				$t2=(empty($r2) ? "current revision" : date('y/m/d H:i T', $r2));
				print "<!doctype html>";
				print "<html><head><meta charset=utf8><link rel=stylesheet href=static/mstyle.css?".filemtime("static/mstyle.css")."></head><body><article><tt>";
				print "--- " . $pageId . " " . $t2 . "<br>";
				print "+++ " . $pageId . " " . $t1 . "<p>";
				//print textDiff2($f2,$f1);
				include_once "class.Diff.php";
				print Diff::toTable(contextDiff(Diff::compare(san_diff($f2), san_diff($f1))));
				print "</tt><p>";
				print "<a href=?do=revisions&id=$pageId>Back</a></article></body></html>";
				exit;
        default:
            print "unsupported do";
            exit;
    }
}

if (!empty($_POST['do'])) {
    switch ($_POST['do']) {
        case "login":
            chk_xtok("default");
            if (empty($_POST['u']) || empty($_POST['p'])) {
                die403("empty username or password");
            }
            if (!auth_login($_POST['u'], $_POST['p'])) {
                die403("wrong username or password");
            }
            if (empty($_POST['id'])) {
                $_POST['id'] = 'index';
            }
            header("Location: " . pageLink(san_pageId($_POST['id'])));
            exit;

        case "resendpwd":
            chk_xtok("default");
            if (empty($_POST['e'])) {
                die("empty email");
            }
            if (auth_resendpwd1($_POST['e'])) {
                print "An email with your login and a link to reset your password has been sent. ";
            } else {
                print "Email address not found. ";
            }
            exit;

        case "register":
            chk_xtok("default");
            if (empty($_POST['e']) || empty($_POST['u']) || empty($_POST['n']) || empty($_POST['p']) || empty($_POST['p2'])) {
                die("all form fields are required");
            }
            auth_register($_POST['u'], $_POST['p'], $_POST['p2'], $_POST['n'], $_POST['e'], $_POST['sup']);
            print "Registration successful. <a href=\"?do=login\">Go to login page</a>";
            exit;

        case "edit":
            if (!auth_isContrib()) {
                die403("not yet authorized");
            }
            $pageId = san_pageId($_POST['id']);
				if (!auth_canEdit($pageId)) {
					die403("not authorized");
				}
            chk_xtok("edit_$pageId");
            if (is_readable("$pageDir/$pageId.txt")) {
                $oldmt = filemtime("$pageDir/$pageId.txt");
                $oldtext = file_get_contents("$pageDir/$pageId.txt");
                $gzd = gzencode($oldtext, 9);
                file_put_contents("$atticDir/$pageId.$oldmt.txt.gz", $gzd);
            }
            $newtext = $_POST['txt'];
            file_put_contents("$pageDir/$pageId.txt", $newtext);
            clearstatcache();
            $mt = filemtime("$pageDir/$pageId.txt");
            $ps = substr(san_csv($_POST['summary']), 0, 64);
            $cline = "$mt\t$clientIp\tE\t$pageId\t" . get_login() . "\t" . $ps . "\n";
            file_put_contents("$metaDir/$pageId.changes", $cline, FILE_APPEND | LOCK_EX);
            unlink("$lockDir/$pageId");
				include_once "class.Diff.php";
				$boundary=bin2hex(openssl_random_pseudo_bytes(4));
				$txtbase="Username:     " . get_login() . "
IP:           " . $clientIp . "

Summary:      " . $ps . "

Old: " . (empty($oldmt) ? "page created" : $baseUrl . "?id=$pageId&rev=$oldmt") . "
New: " . pageLink($pageId, true) . "

";
				$txtbasehtml=nl2br($txtbase);
				$dc=contextDiff(Diff::compare(san_diff($oldtext), san_diff($newtext)));

            sendNotify("change", "Page $pageId changed", "This is a multipart message in MIME format.

--$boundary
Content-type: text/plain; charset=utf8
Content-transfer-encoding: 8bit

$txtbase

" . Diff::toString($dc) . "

--$boundary
Content-type: text/html; charset=utf8
Content-transfer-encoding: 8bit

<!doctype html>
<style>
.diffInserted span {
	border: 1px solid rgb(192,255,192);
	background-color: rgb(224,255,224);
}

.diffDeleted span {
	border: 1px solid rgb(255,192,192);
	background-color: rgb(255,224,224);
}
</style>
<tt>
$txtbasehtml

" . (Diff::toTable($dc)). "</tt>

--$boundary--
","Content-type: multipart/alternative; boundary=$boundary\r\n");
            
            header("Location: " . pageLink($pageId));
            exit;
			case "allowEdit":
				if(!auth_isAdmin() || !chk_xtok()) {
					die403("unauthorized");
				}
				file_put_contents($editableDir . "/" . $pageId, "");
				print "Page $pageId contrib-writable.  <a href=index.php?id=$pageId>Back</a>";
				exit;
			case "revokeEdit":
				if(!auth_isAdmin() || !chk_xtok()) {
					die403("unauthorized");
				}
				unlink($editableDir . "/" . $pageId);
				print "Page $pageId not contrib-writable.  <a href=index.php?id=$pageId>Back</a>";
				exit;
    }
}

