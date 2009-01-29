/**
 * Core JS file for SSC
 */

/**
 * SSC Message object manipulation.  Show messages in their own dialog in a fixed position rather than inline
 */

$(document).ready(function(){
	moveMessages();

	function moveMessages(){
		var msgs = $("div#messages");
	
		if (msgs != null){
			msgs.width("50%");
			var w = $("body").width() - msgs.width();
			w = w / 2;
			msgs.css("position", "fixed");
			msgs.css("display", "none");
			msgs.css("left" , w);
			msgs.css("right" , w);
			msgs.css("top", "20%");
			msgs.addClass("message-jsbox");
			if (msgs.children("button").length == 0)
				msgs.append("<button type=\"button\">Close</button>");
			msgs.fadeIn("fast");
		}
	}
	
	$("div#messages button").live('click', function(){$("div#messages").fadeOut("normal", removeMessages);});
	
	function removeMessages(){
		$("div#messages").remove();
	}

	
	var didValidate;
	$("input[type=submit]").click(function(e){
		//
		didValidate = true;
		
		var $target = $(e.target);
		while (!$target.is("form")){
			$target = $($target.parent().get(0));
			if ($target == null)
				return true;		// Can't find parent - try server side validation instead
		}
		
		$target.children().each(tryValidate);
		
		if (!didValidate && $("div#messages").length == 0) {
			$("body").append("<div id=\"messages\"><div class=\"message message-crit\"><span>Not all required fields were filled in</span></div></div>");
			moveMessages();
		}
			
		return didValidate;
	});
	
	function tryValidate(){
		if (!didValidate)
			return;
			
		var $target = $(this);
		var $kids = $target.children();

		if ($kids.length > 0)
			$kids.each(tryValidate);
		else if ($target.is("input.inp-req") && $target.val().length == 0){
			didValidate = false;
		}
	}
});


