/**
 * Sailing related javascript
 * Currently handles switching between skipper and crew as well as placings to times
 */

 $(document).ready(function() {
 	
	var $kids = $(".sail-table thead").children();
	
	// Check to avoid crash and burn
	if ($kids.length > 1){
		// This should be more efficient then re-wrapping it in jQuery stuff
		$kids = $($kids[1]);
		$kids = $kids.children();
		var len = $kids.length;
		// For working out % widths
		var w = $(".sail-table").width();

		// Look for the important columns (skipper and heats)		
		for (i = 0; i < len; i++){
			var $kid = $($kids[i]).children();
			if ($kid.length > 0)
				$kids[i] = $kid[0];	

			if ($kids[i].innerHTML == "Skipper") {
				// Skipper column - ensure there is at least one crew member before showing switch button
				if ($(".sail-table * tr td:nth-child(" + (i + 1) + ")").filter(function(){
					return $(this).attr("title").length > 0;
				}).length > 0) {
					// Crew present - show button and fix column width to a certain percentage
					$(".sail-table * tr th:nth-child(" + (i + 1) + ")").wrapInner("<span />");
					$($kids[i]).width(($($kids[i]).width() / w * 100) + "%");
					$($kids[i]).html($($kids[i]).html() + " <img src=\"" + siteURI + "/images/switch.png\" title=\"Show crew\" \>");
					$($kids[i]).children("img").click(swapSailor);
					$($kids[i]).data("idx", i + 1);
				}
			}
			else {
				// Look for heat column
				if ($kids[i].innerHTML.charAt(0) == "R" && $(".sail-table tbody tr td:nth-child(" + (i + 1) + ")").attr("title").length > 0) {
					// Fix width and add switching button
					$($kids[i]).parent().width(($($kids[i]).parent().width() / w * 100) + "%").append(" <img src=\"" + siteURI + "/images/sail-time.png\" title=\"Show times\" \>");
					$($kids[i]).parent().children("img").data("idx", i + 1).click(swapPlaces);
				}
			}
		}
	}
	
	/**
	 * Alternate between skipper and crew column contents
	 */
	function swapSailor(){
		var $this = $(this);
		// Grab column number
		var col = $this.parent().data("idx");
		
		// Fade out
		$(".sail-table * tr td:nth-child(" + col + ")").animate({"opacity":"toggle"}, 500, "swing", function(){
			// and swap names
			$me = $(this);
			var old = $me.text();
			$me.text($me.attr("title"));//($me.attr("title")).css("opacity",0);
			
			$me.attr("title", old);
			$me.animate({"opacity":"toggle"}, 500);
		});
		
		// Change title of button
		$(".sail-table * tr th:nth-child(" + col + ") span").animate({"opacity":"toggle"}, 500, "swing", function(){
			$me = $(this);
			$txt = $me.text();

			if ($txt.charAt(0) == "S") {
				$me.text($me.text().replace("Skipper", "Crew"));
				$me.parent().children("img").attr("title", "Show skipper");
			}
			else {
				$me.text($me.text().replace("Crew", "Skipper"));
				$me.parent().children("img").attr("title", "Show crew");
			}
			$me.animate({"opacity":"toggle"}, 500);
		});

	}
	
	/**
	 * Alternate heat between place and time
	 */
	function swapPlaces(){
		var $this = $(this);
		// Grab column
		var col = $this.data("idx");

		// Fade out old value
		$(".sail-table * tr td:nth-child(" + col + ")").animate({"opacity":"toggle"}, 500, "swing", function(){
			// Swap content of cell
			$me = $(this);
			var old = $me.text();
			$me.text($me.attr("title"));//($me.attr("title")).css("opacity",0);
			
			$me.attr("title", old);
			$me.animate({"opacity":"toggle"}, 500);
		});
		
		// Change image title and source
		$(".sail-table * tr th:nth-child(" + col + ")").children("img").animate({"opacity":"toggle"}, 500, "swing", function(){
			$me = $(this);
			$txt = $me.attr("title");

			if ($txt == "Show times") {
				$me.attr("title", "Show placings");
				$me.attr("src", $me.attr("src").replace("sail-time.png", "sail-num.png"));
			}
			else {
				$me.attr("title", "Show times");
				$me.attr("src", $me.attr("src").replace("sail-num.png", "sail-time.png"));
			}
			$me.animate({"opacity":"toggle"}, 500);
		});
	}
	
 });
