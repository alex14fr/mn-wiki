<!doctype html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$sec=file_get_contents("/persist/data/secret2");
$sec1=file_get_contents("/persist/data/secret1");
$key=sha1($sec);
if(!isset($_GET['tok']) || !hash_equals($_GET['tok'],$key)) { print "token error"; exit; }
foreach(file("/persist/mascot21_upload/allowed",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l) {
	print "<a href=21upload.php?mail=".urlencode($l)."&tok=".sha1($sec1.$l).">".$l."</a><br>";
}
?>
<p>
<textarea cols=60 rows=12>
<?php
foreach(file("/persist/mascot21_upload/allowed",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l) {
	?>
(cat << EOF
From: Alexandre Janon (GDR MASCOT-NUM) <alexandre.janon@u-psud.fr>
To: <?php print $l."\n"; ?>
Cc: alexandre.janon@u-psud.fr,bertrand.iooss@edf.fr,clementine.prieur@univ-grenoble-alpes.fr,celine.helbert@ec-lyon.fr,anthony.nouy@ec-nantes.fr,christophette.blanchet@ec-lyon.fr
Subject: Reminder - MASCOT21 Poster session
Content-type: text/plain
Message-id: <<?php print sha1(time().sha1($l)); ?>@u-psud.fr>
Mime-version: 1.0
Date: <?php print date(DATE_RFC822, time())."\n"; ?>

Dear Sir/Madam,

We sent this gentle reminder to you, as we have not received your presentation yet (please signal it to us if you sent it).

Best regards,


The session poster of the MASCOT21 conference is shared in two parallel sessions on April, the 28th, organized as follows:

- 4:00-4:45 pm a poster blitz during which a video with all recorded presentations of the session will be presented,
- 4:45-5:45 pm a 1 hour question and answer session during which, based on the a pdf support, you will defend your poster work. 

To prepare the session for the poster session of the MASCOT21 conference, we ask you to send to us, via the Web form linked below, a video recording containing your presentation (of 5 minutes maximum) before April 20th. 

You can send to us one video file (of you commenting your presentation, or a screen presentation with aural comments, or both), or two synchronized video files (one with you, the other one for your screen presentation).  You can prepare the video files using your favorite movie recording application, or you can use the video recording Web application linked below.

We also need a PDF file of your presentation that you will probably share as a support during audience's question session. 

Web form for uploading your files (mandatory) : https://gdr-mascotnum.fr/21upload.php?mail=<?php print $l; ?>&tok=<?php print sha1($sec1.$l)."\n"; ?>

Web application for video recording (optional) : https://gdr-mascotnum.fr/21videorec.php?mail=<?php print $l; ?>&tok=<?php print sha1($sec1.$l)."\n"; ?>

If you have any question regarding the process, you can send mail to alexandre.janon@u-psud.fr .

Best regards,

EOF
)|msmtp -t -a psud 
<?php
}
?>
</textarea>

