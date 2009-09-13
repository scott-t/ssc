/**
 * Core JS file for SSC
 */

$(document).ready(function(){
	
	/*
	 * SSC Message object manipulation.  Show messages in their own dialog in a fixed position rather than inline
	 */
	
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
			msgs.css("top", "25%");
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

	/*
	 * Form manipulation.  Retrieve form hierarchy when the particular form obtains focus
	 */
	var formVal = {};
	
	$("input").focus(function (e) {
		// Check if any trees exist yet
		if (typeof formVal.forms == 'undefined') {
			formVal.forms = {};
		}
	
		// Find what form we're after
		var target = $(e.target).closest("form").attr("name");
		if (!formVal.forms[target]) {
			formVal.forms[target] = 1;
			$.getJSON(siteURI + "/?core=val-form&ajax=y&form=" + target, 
				function(data) {
					formVal.forms[target] = data;
				});
		}
	});
	
	

	formVal.err;
	formVal.didVal;
	formVal.form;
	$("input[type=submit]").click(function(e){
		//
		formVal.didVal = true;
		formVal.err = "";
		formVal.$form = $(e.target).closest("form");
		var target = formVal.$form.attr("name");
		if (!formVal.forms[target]) {
			// Validation hasn't loaded yet...
			return true;
		}

		$.each(formVal.forms[target], tryValidate);
		
		if (!formVal.didVal && $("div#messages").length == 0) {
			$("body").append("<div id=\"messages\"><div class=\"message message-crit\"><span>Not all required fields were filled in: " + formVal.err + "</span></div></div>");
			moveMessages();
		}
			
		return formVal.didVal;
	});
	
	function tryValidate(index, element){
		
		// If #type exists, probably a form element
		if (element["#type"]) {

			// Dive into fieldsets
			if (element["#type"] == "fieldset") {
				tryValidate(element, $.each(element, tryValidate));
			}
			else {
				// Form element
				$e = formVal.$form.find("*[name='" + index + "']:input");
				if (element["#required"] && $e.val().length == 0) {
					formVal.didVal = false;
					if (formVal.err.length == 0)
						formVal.err += element["#title"];
					else
						formVal.err += ", " + element["#title"];
						
					if (!$e.hasClass("form-error"))
						$e.addClass("form-error");
				}
				else {
					$e.removeClass("form-error");
				}
			}
		}
	}

});








