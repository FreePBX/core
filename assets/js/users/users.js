var deleteExts = [];
$(".btn-remove").click(function() {
	var type = $(this).data("type"), btn = $(this);
	if(confirm(_("Are you sure you wish to delete these users?"))) {
		btn.find("span").text(_("Deleting..."));
		btn.prop("disabled", true);
		$.post( "ajax.php", {command: "delete", module: "core", extensions: deleteExts, type: type}, function(data) {
			if(data.status) {
				btn.find("span").text(_("Delete"));
				$("#table-users").bootstrapTable('remove', {
					field: "extension",
					values: deleteExts
				});
			} else {
				btn.find("span").text(_("Delete"));
				btn.prop("disabled", true);
				alert(data.message);
			}
		});
	}
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
