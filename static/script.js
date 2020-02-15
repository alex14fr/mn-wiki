function switchMenuMobile() {
	var lfsb=document.getElementById("leftsidebar");
	if(lfsb.style.display=="" || lfsb.style.display=="none") {
		lfsb.style.display="block";
		document.getElementById("mobilemenulink").innerHTML="Hide menu";
	} else {
		lfsb.style.display="none";
		document.getElementById("mobilemenulink").innerHTML="Show menu";
	}
}
window.addEventListener('load',function() {
					document.getElementById('mobilemenulink').addEventListener('click', switchMenuMobile);
});

