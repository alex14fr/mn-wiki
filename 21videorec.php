<?php
if(!hash_equals(sha1(file_get_contents("data/secret1").$_GET['mail']), $_GET['tok'])) {
	print 'token error'; exit;
}
?>
<!doctype html>
<meta name=viewport value="width=device-width">
<style>body { line-height: 1.7; font-size: 18px;  font-family:sans-serif;}</style>
<h2>MASCOT21 Days - Poster session - Recorder</h2>
<b>Step #1 : </b> Select your recording mode. Besides audio track, you can record your webcam, your screen, or both. <br><i>(Note: screen recording may not be supported on mobile browsers and Safari; some audio/video desync problems have been reported on Mac OS/Chrome - also try switching between Chromium/Chrome and Firefox if you have problems.)</i><br>
<select id=recordMode>
<option value=cam-screen>Camera + Screen + Microphone</option>
<option value=cam>Camera + Microphone</option>
<option value=screen>Screen + Microphone</option>
</select>
<p>
<b>Step #2 : </b> Record yourself ! Before recording your full presentation, you are advised to perform a test on a short record. <br><b>Please do your presentation in no more than 5 minutes. </b><br>
<button id=record>Start recording</button>
<button disabled id=stop>Stop recording</button>
<div id=statusmsg style=font-weight:bold;color:firebrick></div>
<div id=errormsg style=font-weight:bold></div>
<p>
<b>Step #3 : </b> Download and review your video file(s).<br>
For Cam+Mic or Screen+Mic mode, you can download only one file. <br>For Cam+Screen+Mic mode, you download two files: one for camera and audio, the other for your screen presentation.<br>
<button id=savecam disabled>Download camera recording</button>
<button id=savescreen disabled>Download screen recording</button>
<p>
<b>Step #4 : </b> Upload your video file(s).<br>
Use the <a href="21upload.php?mail=<?php print $_GET['mail'];?>&tok=<?php print $_GET['tok'];?>">upload form</a>.
<p>

<script src="https://cdnjs.cloudflare.com/ajax/libs/webrtc-adapter/7.4.0/adapter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/RecordRTC/5.5.6/RecordRTC.min.js"></script>
<script>
"use strict";
var recorder_screen=false, recorder_cam=false;
var blobScreen=false, blobCam=false;
var desktopM=false, micM=false, camM=false;

async function startrecording(ev) {
	document.getElementById('savescreen').disabled=true;
	document.getElementById('savecam').disabled=true;
	recorder_screen=recorder_cam=blobScreen=blobCam=false;
	desktopM=micM=camM=false;
	var errspn=document.getElementById("errormsg");
	var tracks=[];
	var recordMode=document.getElementById("recordMode").value;
	errspn.innerHTML="";
	if(!navigator.mediaDevices) { errspn.innerHTML="error: incompatible browser !"; }
	if(recordMode=='screen') {
		try {
			if(navigator.mediaDevices.getUserMedia)
				micM = await navigator.mediaDevices.getUserMedia({video: false, audio: true});
		} catch(error) {
			errspn.innerHTML+="microphone sharing error : "+error+"<br>";
		}
	}
	if(recordMode=='screen'||recordMode=='cam-screen') {
		try {
			if(navigator.mediaDevices.getDisplayMedia)
				desktopM = await navigator.mediaDevices.getDisplayMedia({video:true});
		} catch(error) {
			errspn.innerHTML+="screen sharing error : "+error+"<br>";
		}
	}
	if(recordMode=='cam'||recordMode=='cam-screen') {
		try {
			if(navigator.mediaDevices.getUserMedia) 
				camM = await navigator.mediaDevices.getUserMedia({video: true, audio: true});
		} catch(error) {
			errspn.innerHTML+="camera sharing error : "+error+"<br>";
			try {
				camM = await navigator.mediaDevices.getUserMedia({video: false, audio: true});
			} catch(error2) {
				errspn.innerHTML+="audio-only sharing error : "+error2+"<br>";
			}
		}
	}

	var rrtcconf={type:'video','audioBitsPerSecond':96*1024,'videoBitsPerSecond':384*1024};
	if(recordMode=='cam') {
		recorder_cam = RecordRTC(new MediaStream(camM ? camM.getTracks() : []), rrtcconf);
	} else if(recordMode=='screen') {
		recorder_screen = RecordRTC(new MediaStream([...(desktopM ? desktopM.getTracks() : []), 
																	...(micM ? micM.getTracks() : [])
																	]), rrtcconf);
	} else {
		recorder_cam = RecordRTC(new MediaStream(camM ? camM.getTracks() : []), rrtcconf);
		recorder_screen = RecordRTC(new MediaStream([...(desktopM ? desktopM.getTracks() : [])], 
																	rrtcconf));
	}

	if(recorder_cam)
		recorder_cam.startRecording();
	if(recorder_screen)
		recorder_screen.startRecording();

	document.getElementById('statusmsg').innerHTML='Recording started';
	document.getElementById('record').disabled=true;
	document.getElementById('stop').disabled=false;

};

function stoprecording() {
	if(recorder_cam)
		recorder_cam.stopRecording(function() {
			blobCam = recorder_cam.getBlob();
			document.getElementById('savecam').onclick=function() {invokeSaveAsDialog(blobCam, "mascot21 cam.webm");}
			document.getElementById('savecam').disabled=false;
		}); 
	if(recorder_screen)
		recorder_screen.stopRecording(function() {
			blobScreen = recorder_screen.getBlob();
			document.getElementById('savescreen').onclick=function() {invokeSaveAsDialog(blobScreen, "mascot21 screen.webm");}
			document.getElementById('savescreen').disabled=false;
		}); 

	if(camM) camM.stop();
	if(desktopM) desktopM.stop();
	if(micM) micM.stop();
	document.getElementById('statusmsg').innerHTML='';
	document.getElementById('record').disabled=false;
	document.getElementById('stop').disabled=true;
};


document.addEventListener('DOMContentLoaded',function() {
	document.getElementById('record').onclick=startrecording;
	document.getElementById('stop').onclick=stoprecording;
});

</script>
