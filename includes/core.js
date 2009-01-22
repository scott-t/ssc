/**
 * @author Scott
 */


// Move messages to middle of screen
function bodyLoad(){
	var msgs = document.getElementById("messages");
	if (msgs != null){
		msgs.style.width = "50%";
		var w = document.getElementsByTagName("body")[0].clientWidth - msgs.clientWidth;
		w = w / 2;
		msgs.style.position = "fixed";
		msgs.style.left = w + "px";
		msgs.style.right = w + "px";
		msgs.className = "message-jsbox";
		msgs.innerHTML += "<button type=\"button\" onclick=\"javascript: hideMessages()\">Close</button>";
	}
}

function hideMessages(){
	var msgs = document.getElementById("messages");
	if (msgs != null) {
		msgs.style.display = "none";
	}
}
