$(".types .fa.fa-question-circle").hover(function() {
	var id = $(this).data("id");
	$("#" + id + "-help").addClass("active");
}, function() {
	var id = $(this).data("id");
	$("#" + id + "-help").removeClass("active");
});
