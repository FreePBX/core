$('.fpbx-submit').submit(function() {
	if($("#channel").val().trim() === "") {
		return warnInvalid(channel, _("Channel must not be blank"));
	}
	if(!($('#channel').val().match(/^[0-9]+$/))) {
		return warnInvalid(channel, _("Invalid Channel Number, must be numeric"));
	}
	if($("#did").val().trim() === "") {
		return warnInvalid(did, _("Invalid DID, must be a non-blank DID"));
	}
	if($.inArray($("#channel").val(), channel_num) != -1){
		return warnInvalid(channel, $("#channel").val()  + _(" already used, please use a different channel."));
	}
});

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
