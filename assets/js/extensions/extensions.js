var loc = window.location.hash.replace("#", "");
if (loc !== "" && $("#" + loc + ".info-pane").length > 0) {
	$(".info-pane").addClass("hidden");
	$(".change-tab").removeClass("active");
	$("#" + loc + ".info-pane").removeClass("hidden");
	$(".change-tab[data-name='" + loc + "']").addClass("active");
}
$(".change-tab").click(function(event) {
	var pos = document.body.scrollTop,
			id = $(this).data("name");
	if ($(this).hasClass("active")) {
		event.stopPropagation();
		event.preventDefault();
		return true;
	}
	$(".info-pane").addClass("hidden");
	$(".change-tab").removeClass("active");
	$(this).addClass("active");
	$("#" + id).removeClass("hidden");
	location.hash = id;
	document.body.scrollTop = document.documentElement.scrollTop = pos;
	event.stopPropagation();
	event.preventDefault();
});
$(".ext-container .fa.fa-question-circle").hover(function() {
	var id = $(this).data("id");
	$("#" + id + "-help").addClass("active");
}, function() {
	var id = $(this).data("id");
	$("#" + id + "-help").removeClass("active");
});
$(".ext-container input, .ext-container select, .ext-container textarea").focus(function() {
	var id = $(this).parents(".parent").data("id");
	$("#" + id + "-help").addClass("active");
});
$(".ext-container input, .ext-container select, .ext-container textarea").blur(function() {
	var id = $(this).parents(".parent").data("id");
	$("#" + id + "-help").removeClass("active");
});
$(".types .fa.fa-question-circle").hover(function() {
	var id = $(this).data("id");
	$("#" + id + "-help").addClass("active");
}, function() {
	var id = $(this).data("id");
	$("#" + id + "-help").removeClass("active");
});
