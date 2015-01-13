$('#outbound_routes').sortable({
	containerSelector: 'tbody',
	itemPath: 'tbody',
	itemSelector: 'tr',
	placeholder: '<tr class="placeholder"/>',
	handle: '.fa-arrows',
	update: function(event, ui){
		var id = ui.item.data('id');
		var seq = ui.item.index()+1;
		$.ajax({
			type: 'POST',
			url: location.href,
			data: 'action=ajaxroutepos&quietmode=1&skip_astman=1&restrictmods=core&repotrunkkey='+id+'&repotrunkdirection='+seq,
			dataType: 'json',
			success: function(data) {
				toggle_reload_button('show');
			}
		});
	}
})

$("a[id^='del']").click(function(){	
	var id = $(this).data('id');
	$.ajax({
		type: 'POST',
		url: location.href,
		data: 'action=delroute&quietmode=1&skip_astman=1&json=true&restrictmods=core&id='+id,
		dataType: 'json',
		success: function(data) {
			toggle_reload_button('show');
			location.reload();
		}
	});		
});
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
		case "#dialpatterns":
			$("#wizmenu").removeClass('hidden');
		break;
		default:
			$("#wizmenu").addClass('hidden');
		break;
		
	}
});
