
$("a[id^='rowadd']").click(function(){
	var curRow = $(this).closest('tr');
	var newRow = curRow.clone(true);
	curRow.after(newRow);
});	
$("a[id^='rowdel']").click(function(){
	var curRow = $(this).closest('tr');
	curRow.fadeOut(2000, function(){
		$(this).remove();
	});
});
//DialPlan Wizard
$("[id='getlocalprefixes']").click(function(){
	var npa = $('#lpwnpa').val();	
	var nxx = $('#lpwnxx').val();
	var patterns = [];
	if ($('#fwdownload').prop('checked')){
		$.ajax({
		type: 'POST',
		url: location.href,
		data: 'action=getnpanxxjson&npa='+npa+'&nxx='+nxx,
		dataType: 'json',
		async:false,
		beforeSend: function(){
			$('#dpwizard').modal('hide');
			$('#dploading').modal('show');
		},
		success: function(data) {
			$.each(data,function(){
				var npa = this.npa;	
				var nxx = this.nxx;
				if ($('#fw7').prop('checked')){
					patterns.push(nxx+'XXXX');
				}
				if ($('#fw10').prop('checked')){
					patterns.push(npa+nxx+'XXXX');
				}
				if ($('#fw11').prop('checked')){
					patterns.push('1'+npa+nxx+'XXXX');
				}
			});
		},
		error: function(){
			$('#dploading').html('<h1>Something went wrong with the download</h1>');
		}
	});
	}else{
		if ($('#fw7').prop('checked')){
			patterns.push('NXXXXXX');
		}
		if ($('#fw10').prop('checked')){
			patterns.push('NXXNXXXXXX');
		}
		if ($('#fw11').prop('checked')){
			patterns.push('1NXXNXXXXXX');
		}
	}
	if ($('#fwtollree').prop('checked')){
		//800 since 1966
		patterns.push('1800NXXXXXX');
		//888 since 1996
		patterns.push('1888NXXXXXX');
		//877 since 1998
		patterns.push('1877NXXXXXX');
		//866 Since 2000
		patterns.push('1866NXXXXXX');
		//855 Since 2010
		patterns.push('1855NXXXXXX');
		//844 Since 2013
		patterns.push('1844NXXXXXX');
		//Future not implimented 833,822,880-887,889
	}
	if ($('#fwinfo').prop('checked')){
		//Community Services
		patterns.push('211');
		//Municipal services Non-Emergency
		patterns.push('311');
		//Directory Assistance
		patterns.push('411');
		//Traffic
		patterns.push('511');
		//Telephone company repair
		patterns.push('611');
		//TDD Relay
		patterns.push('711');
	}
	if ($('#fwemergency').prop('checked')){
		patterns.push('911');
	}
	if ($('#fwint').prop('checked')){
		patterns.push('011.');	
	}
	if ($('#fwld').prop('checked')){
		patterns.push('1NXXNXXXXXX');
	}
	if($('#dptable').length){
		$.each(patterns,function(){
			var lastRow = $('#dptable tr:last');
			var newRow = lastRow.clone(true);
			newRow.find("[id^='pattern_pass']").val(this);
			lastRow.after(newRow);
		});
	}
	if($('#bulk_patterns').length){
		$('#bulk_patterns').val(patterns.join("\r\n"));
	}
	$('#dploading').modal('hide');	
});
//tab specifics
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
	switch(e.target.hash){
		case "#tdialplan":
			$("#wizmenu").removeClass('hidden');
		break;
		default:
			$("#wizmenu").addClass('hidden');
		break;
		
	}
});
//Duplicate button
$("#duplicate").click(function(){
	$("#action").val("copytrunk");
});
//Toggles
$('input[name="failtrunk_enable"]').on('change', function(){
	console.log(this);
	if($(this).val() == "1"){
		$('input[name="failtrunk"]').prop('disabled', false);
	}else{
		$('input[name="failtrunk"]').prop('disabled', true);
	}
});
$('input[name="dialoutopts_cb"]').on('change', function(){
	console.log(this);
	if($(this).val() == "or"){
		$('input[name="dialopts"]').prop('disabled', false);
	}else{
		$('input[name="dialopts"]').prop('disabled', true);
	}
});