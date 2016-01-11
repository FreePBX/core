$(document).on("click",'a[id^="del"]',function(){
	var cmessage = _("Are you sure you want to delete this DID?");
	if(!confirm(cmessage)){
		return false;
	}
	var chan = $(this).data('channel');
	var row = $('#row'+chan);
	$.ajax({
		url: "config.php",
		data: {
			display:'dahdichandids',
			action:'delete',
			channel: chan
		},
		type: "GET",
		dataType: "html",
		success: function(data){
				location.reload();
		},
		error: function(xhr, status, e){
			console.dir(xhr);
			console.log(status);
			console.log(e);
		}
	});
});
