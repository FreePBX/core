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
	if(!($('#extension').val().match(/^\+?[0-9a-dA-D#\*]+$/)) && ($('#extension').val().trim() != '') && ($('#extension').val().indexOf('_') !== 0)){
		return warnInvalid($('#extension'), _("DID can only be numbers, A-D, * and #. DID may also start with a +. Patterns must begin with an _"));
	}
	var keywords = ["private","blocked","unknown","restricted","anonymous","unavailable"];
	if(!isDialpattern($('#cidnum').val()) && !isEmpty($('#cidnum').val().trim()) && keywords.indexOf($('#cidnum').val().toLowerCase()) === -1){
		return warnInvalid($('#cidnum'), _("CIDNUM can only be numbers, A-D, * and #. DID may also start with a +. Patterns must begin with an _ DID may also start with a +."));
	}
});
