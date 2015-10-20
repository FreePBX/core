var deleteExts = [];
$("#table-all-users-side").on("click-row.bs.table", function(row, $element) {
	window.location = "?display=users&extdisplay="+$element.extension;
});
$(".btn-remove").click(function() {
	var btn = $(this);
	if(confirm(_("Are you sure you wish to delete these users?"))) {
		btn.find("span").text(_("Deleting..."));
		btn.prop("disabled", true);
		$.post( "ajax.php", {command: "delete", module: "core", extensions: deleteExts, type: "users"}, function(data) {
			if(data.status) {
				btn.find("span").text(_("Delete"));
				$(".ext-list").bootstrapTable('remove', {
					field: "extension",
					values: deleteExts
				});
				toggle_reload_button("show");
			} else {
				btn.find("span").text(_("Delete"));
				btn.prop("disabled", true);
				alert(data.message);
			}
		});
	}
});
$("table").on("post-body.bs.table", function () {
	$(this).find(".clickable.delete").click(function() {
		var id = $(this).data("id");
		if(confirm(_("Are you sure you wish to delete this user?"))) {
			$.post( "ajax.php", {command: "delete", module: "core", extensions: [id], type: "users"}, function(data) {
				if(data.status) {
					$(".ext-list").bootstrapTable('remove', {
						field: "extension",
						values: [id.toString()]
					});
					toggle_reload_button("show");
				} else {
					alert(data.message);
				}
			});
		}
	});
});
$("table").on("page-change.bs.table", function () {
	$(".btn-remove").prop("disabled", true);
	deleteExts = [];
});
$("table").on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
	var toolbar = $(this).data("toolbar"), button = $(toolbar).find(".btn-remove"), id = $(this).prop("id");
	button.prop('disabled', !$("#"+id).bootstrapTable('getSelections').length);
	deleteExts = $.map($("#"+id).bootstrapTable('getSelections'), function (row) {
		return row.extension;
  });
});
