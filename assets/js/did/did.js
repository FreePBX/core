$("[name='privacyman']").change(function(){
	if($(this).val() == "1"){
		$("#pmmaxretries").attr('disabled', false);
		$("#pmminlength").attr('disabled', false);
	}else{
		$("#pmmaxretries").attr('disabled', true);
		$("#pmminlength").attr('disabled', true);

	}
});
$('.fpbx-submit').submit(function() {
	if(!($('#extension').val().match(/^\+?[0-9a-dA-D#\*]+$/)) && $('#extension').val().trim() != ''){
		return warnInvalid($('#extension'), _("DID can only be numbers, A-D, * and #. DID may also start with a +"));
	}
	if(!($('#cidnum').val().match(/^\+?[0-9a-dA-D#\*]+$/)) && ($('#cidnum').val().trim() != '')){
		return warnInvalid($('#cidnum'), _("CIDNUM can only be numbers, A-D, * and #. DID may also start with a +"));
	}

});
