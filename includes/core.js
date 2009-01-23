/**
 * Core JS file for SSC
 */

/**
 * SSC Message object manipulation.  Show messages in their own dialog in a fixed position rather than inline
 */
$(document).ready(function(){
	var msgs = $("div#messages");

	if (msgs != null){
		msgs.width("50%");
		var w = $("body").width() - msgs.width();
		w = w / 2;
		msgs.css("position", "fixed");
		msgs.css("display", "none");
		msgs.css("left" , w);
		msgs.css("right" , w);
		msgs.addClass("message-jsbox");
		msgs.append("<button type=\"button\" onclick=\"javascript: hideMessages()\">Close</button>");
		msgs.fadeIn("fast");

	}
	
	$("div#messages button").click(function(){$("div#messages").fadeOut();});

});

