$("#quickCreate .fa.fa-question-circle").hover(function() {
	var id = $(this).data("id");
	$("#" + id + "-help").addClass("active");
}, function() {
	var id = $(this).data("id");
	$("#" + id + "-help").removeClass("active");
});
$("#quickCreate .form-control").focus(function() {
	$(".help-block").removeClass("active");
	var id = $(this).data("for");
	$("#" + id + "-help").addClass("active");
});
$(".modal.paged .modal-footer .next").click(function() {
	changePage("next",$("#quickCreate"));
});
$(".modal.paged .modal-footer .back").click(function() {
	changePage("back",$("#quickCreate"));
});

$("#quickCreate #extension, #quickCreate #name").on('keyup',function() {
	var name = $("#quickCreate #name").val(), ext = $("#quickCreate #extension").val();
	if($(this).prop('id') == "extension") {
		if(typeof extmap[ext] !== "undefined") {
			$(this).parents('.form-group').addClass('has-warning');
			$('#extension-help').append('<span class="warnMessage"><br/><strong>' + sprintf(_('In Use: %s'),extmap[ext]) + '</strong></span>');
		} else {
			$(this).parents('.form-group.has-warning').removeClass('has-warning');
			$('#extension-help .warnMessage').remove();
		}
	}
	$("#quickCreate #outboundcid").prop("placeholder",'"' + name + '" <' + ext + '>');
});

$("#quickCreate .modal-footer .create").click(function() {
	if(validateQC()) {
		var data = {};
		$("#quickCreate form input[type=text], #quickCreate form input[type=number], #quickCreate form input[type=email], #quickCreate form input[type=radio]:checked, #quickCreate form select").each(function() {
			data[$(this).prop('name')] = $(this).val();
		});
		$('#quickCreate .create').prop("disabled", true);
		$.post("ajax.php?module=core&command=quickcreate", data, function(data,status){
			console.log(data);
			$('#quickCreate').modal('hide');
		});

	}
});

$('.modal.paged').on('hidden.bs.modal', function (e) {
	jumpPage(1,$(this));
	$(this).find("form")[0].reset();
	$('#quickCreate .create').prop("disabled", false);
});

function changePage(direction, modal) {
	var totalPages = modal.data("pages"), currentPage = parseInt(modal.data("currentpage"));
	if(direction == "back") {
		if(currentPage == 1) {
			return;
		}
		modal.find(".page[data-num=" + currentPage + "]").addClass("hidden");
		currentPage--;
		modal.find(".page[data-num=" + currentPage + "]").removeClass("hidden");
		if(currentPage != totalPages) {
			modal.find(".modal-footer .create").addClass("hidden");
			modal.find(".modal-footer .next").removeClass("hidden");
		}
		if(currentPage == 1) {
			modal.find(".modal-footer .next").removeClass("hidden");
			modal.find(".modal-footer .back").addClass("hidden");
		}
	} else {
		if(currentPage == totalPages) {
			return;
		}
		modal.find(".page[data-num=" + currentPage + "]").addClass("hidden");
		currentPage++;
		modal.find(".page[data-num=" + currentPage + "]").removeClass("hidden");
		if(currentPage == totalPages) {
			modal.find(".modal-footer .create").removeClass("hidden");
			modal.find(".modal-footer .next").addClass("hidden");
			modal.find(".modal-footer .back").removeClass("hidden");
		} else {
			modal.find(".modal-footer .back").removeClass("hidden");
		}
	}
	modal.data("currentpage",currentPage);
}
function jumpPage(page, modal) {
	var totalPages = modal.data("pages"), currentPage = parseInt(modal.data("currentpage"));
	if(page > totalPages) {
		return;
	}
	modal.find(".page").addClass("hidden");
	currentPage = page;
	modal.find(".page[data-num=" + page + "]").removeClass("hidden");
	if(page == 1) {
		modal.find(".modal-footer .next").removeClass("hidden");
		modal.find(".modal-footer .back").addClass("hidden");
	} else if(page == totalPages) {
		modal.find(".modal-footer .next").addClass("hidden");
		modal.find(".modal-footer .back").removeClass("hidden");
	} else {
		modal.find(".modal-footer .next").removeClass("hidden");
		modal.find(".modal-footer .back").removeClass("hidden");
	}
	modal.data("currentpage",currentPage);
}
