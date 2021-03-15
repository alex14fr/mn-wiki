<!doctype html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$sec=file_get_contents("/persist/data/secret2");
$sec1=file_get_contents("/persist/data/secret1");
$key=sha1($sec);
if(!hash_equals($_GET['tok'],$key)) { print "token error"; exit; }
foreach(file("/persist/mascot21_upload/allowed",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l) {
	print "<a href=21upload.php?mail=".urlencode($l)."&tok=".sha1($sec1.$l).">".$l."</a><br>";
}
?>
<textarea onclick=this.select() rows=20 cols=60>
<?php
foreach(file("/persist/mascot21_upload/allowed",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l) {
	?>
(cat << EOF
from: Alexandre Janon (GDR MASCOT-NUM) <alexandre.janon@u-psud.fr>
to: <?php print $l; ?>
subject: MASCOT21 Poster session instructions
content-type: text/plain
message-id: <?php print sha1(time().sha1($l)); ?>@u-psud.fr
mime-version: 1.0
date: <?php print date(DATE_RFC822, time()); ?>

Dear all,

The session poster of the MASCOT21 conference is shared in two parallel sessions on April, the 28th, organized as follows:

- 4:00-4:45 pm a poster blitz during which a video with all recorded presentations of the session will be presented,
- 4:45-5:45 pm a 1 hour question and answer session during which, based on the a pdf support, you will defend your poster work. 

To prepare the session for the poster session of the MASCOT21 conference, we ask you to send to us, via the Web form linked below, a video recording containing your presentation (of 5 minutes maximum) before April 20th. 

You can send to us one video file (of you commenting your presentation, or a screen presentation with aural comments, or both), or two synchronized video files (one with you, the other one for your screen presentation).  You can prepare the video files using your favorite movie recording application, or you can use the video recording Web application linked below.

We also need a PDF file of your presentation that you will probably share as a support during audience's question session. 

Web form for uploading your files (mandatory) : https://gdr-mascotnum.fr/21upload.php?mail=<?php print $l; ?>&tok=<?php print sha1($sec1.$l); ?>

Web application for video recording (optional) : https://gdr-mascotnum.fr/21videorec.php?mail=<?php print $l; ?>&tok=<?php print sha1($sec1.$l); ?>

If you have any question regarding the process, you can send mail to alexandre.janon@u-psud.fr .

Best regards,

EOF)|msmtp -a psud <?php print $l; ?> alexandre.janon@u-psud.fr bertrand.iooss@edf.fr clementine.prieur@univ-grenoble-alpes.fr celine.helbert@ec-lyon.fr anthony.nouy@ec-nantes.fr christophette.blanchet@ec-lyon.fr
<?php
}
?>
</textarea>

