$(function() {
	$('#outbound_routes').sortable({
		containerSelector: 'tbody',
		itemPath: 'tbody',
		itemSelector: 'tr',
		placeholder: '<tr class="placeholder"/>',
		handle: '.sort-handle',
		stop: function(event, ui){
			var rows = $('#routes').find('tr');
			var routeorder = [];
			$(rows).each(function(){routeorder.push($(this).data('id'))});
			$.ajax({
				type: 'POST',
				url: 'ajax.php?module=core&command=updateRoutes',
				data: {'data':routeorder},
				dataType: 'json',
				success: function(data) {
					toggle_reload_button('show');
					fpbxToast(_('Route order updated. Press "Apply Config" button to write out changes.'),_('Updated'),'success');
				}
			});
		}
	});
	$('#routetrunks').sortable({
		containerSelector: 'tbody',
		itemPath: 'tbody',
		itemSelector: 'tr',
		placeholder: '<tr class="placeholder"/>',
		handle: '.fa-arrows',
		update: function(event, ui){
			var cur = 0;
			var seq = [];
			$("[id^='trunkpri']").each(function(){
				var trunk = $(this).val();
				if(trunk === ''){return;}
				seq.push(trunk);
			});
			$.ajax({
				type: 'POST',
				url: FreePBX.ajaxurl,
				data: {
					command:"updatetrunks",
					module:"core",
					trunkpriority:seq,
					route_id:$("#extdisplay").val()
				},
				dataType: 'json',
				success: function(data) {
					toggle_reload_button('show');
					fpbxToast(_('Trunk order updated. </br>Press "Apply Config" button to write out changes.'),_('Updated'),'success');
				}
			});
		}
	});
	$(".deltrunkrow").on("click",function(e){
		e.preventDefault();
		if($(".deltrunkrow").length > 1){
			$(this).closest('tr').remove();
		}
		bindToLast();
	});
});

$('#routes').on("post-body.bs.table", function () {
	$("a[id^='del']").click(function(e){
		e.preventDefault();
		if(confirm("Are you sure you want to delete this route?") === false){
			return false;
		}
		var id = $(this).data('id'),
		curRow = $(this).closest('tr');
		$.ajax({
			type: 'POST',
			url: "ajax.php",
			data: 'module=core&command=delroute&id='+id,
			dataType: 'json',
			success: function(data) {
				window.location = window.location.href;
				toggle_reload_button('show');
			}
		});
	});
});

$(document).on('click',"a[id^='routerowdel']",function(e){
	e.preventDefault();
	var rowCount = $('#dptable >tbody >tr').length;
	var curRow = $(this).closest('tr');
	if(rowCount > 1){
		curRow.fadeOut("slow", function(){
			$(this).remove();
		});
	}else{
		curRow.find('input:text').each(function(){$(this).val('')});
	}
});
$(document).on('click',"a[id^='routerowadd']",function(e){
	e.preventDefault();
	var curRow = $("tr[id^='dprow']").last();
	var id = $("tr[id^='dprow']").length++;
	var newhtml = '';
	newhtml +='<tr id="dprow'+id+'">';
	newhtml +=	'<td class="prepend">';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+10)+'">(</span>';
	newhtml +=	'		<input placeholder="prepend" type="text" id="prepend_digit_'+id+'" class="form-control " value="">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+11)+'">)</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td>';
	newhtml +=	'<td>';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<input placeholder="prefix" type="text" id="pattern_prefix_'+id+'" class="form-control " value=""> ';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+12)+'">|</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td>';
	newhtml +=	'<td>';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+13)+'">[</span>';
	newhtml +=	'		<input placeholder="match pattern" type="text" id="pattern_pass_'+id+'" class="form-control dpt-value" value="">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+14)+'">/</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td>';
	newhtml +=	'<td>';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<input placeholder="CallerID" type="text" id="match_cid_'+id+'" class="form-control " value="">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+15)+'">]</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td><td>';
	newhtml +=	'		<a href="#" id="routerowadd'+id+'"><i class="fa fa-plus"></i></a>';
	newhtml +=	'		<a href="#" id="routerowdel'+id+'"><i class="fa fa-trash"></i></a>';
	newhtml +=	'</td>';
	newhtml +=	'</tr>';
	curRow.parent().append(newhtml);

});

//DialPlan Wizard
$("[id='routinggetlocalprefixes']").click(function(){
	var npa = $('#lpwnpa').val();
	var nxx = $('#lpwnxx').val();
	var patterns = [];
	if ($('#fwdownload').prop('checked')){
		$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: 'module=core&command=getnpanxxjson&npa='+npa+'&nxx='+nxx,
		dataType: 'json',
		async:false,
		beforeSend: function(){
			$('#dpwizard').modal('hide');
		},
		success: function(data) {
			$.each(data,function(){
				var npa = this.npa;
				var nxx = this.nxx;
				if ($('#fw7').prop('checked')){
					patterns.push({match:nxx+'XXXX'});
				}
				if ($('#fw10').prop('checked')){
					patterns.push({match:npa+nxx+'XXXX'});
				}
				if ($('#fw11').prop('checked')){
					patterns.push({match:'1'+npa+nxx+'XXXX'});
				}
			});
		},
		error: function(){
			fpbxToast(_('Something went wrong with the download'));
		}
	});
	}else{
		if ($('#fw7').prop('checked')){
			patterns.push({match:'NXXXXXX'});
		}
		if ($('#fw10').prop('checked')){
			patterns.push({match:'NXXNXXXXXX'});
		}
		if ($('#fw11').prop('checked')){
			patterns.push({match:'1NXXNXXXXXX'});
		}
	}
	if ($('#fwtollfree').prop('checked')){
		//800 since 1966
		patterns.push({match:'1800NXXXXXX'});
		//888 since 1996
		patterns.push({match:'1888NXXXXXX'});
		//877 since 1998
		patterns.push({match:'1877NXXXXXX'});
		//866 Since 2000
		patterns.push({match:'1866NXXXXXX'});
		//855 Since 2010
		patterns.push({match:'1855NXXXXXX'});
		//844 Since 2013
		patterns.push({match:'1844NXXXXXX'});
		//833 Since 2017
		patterns.push({match:'1833NXXXXXX'});
		//Future not implimented 822,880-887,889
	}
	if ($('#fwinfo').prop('checked')){
		//Community Services
		patterns.push({match:'211'});
		//Municipal services Non-Emergency
		patterns.push({match:'311'});
		//Directory Assistance
		patterns.push({match:'411'});
		//Traffic
		patterns.push({match:'511'});
		//Telephone company repair
		patterns.push({match:'611'});
		//TDD Relay
		patterns.push({match:'711'});
	}
	if ($('#fwemergency').prop('checked')){
		patterns.push({match:'911'});
		patterns.push({match:'933'});
		patterns.push({match:'911', prefix:'1'});
		patterns.push({match:'911', prefix:'9'});
		patterns.push({match:'911', prefix:'91'});
      	//National Suicide Hotline; Added to emergency due to the nature of the number.
      	patterns.push({match: '988'});
	}
	if ($('#fwemergencyeu').prop('checked')){
		// https://europa.eu/youreurope/citizens/travel/security-and-emergencies/emergency/index_en.htm
		// https://europa.eu/youreurope/citizens/travel/security-and-emergencies/emergency/faq/index_en.htm
		
		//Emergency Number in Europan
		patterns.push({match:'112'});
		//Missing children
		patterns.push({match:'116000'});
		//helpline for children
		patterns.push({match:'116111'});
		//emotional support helpline
		patterns.push({match:'116123'});
		//helpline for crime victims
		patterns.push({match:'116006'});
	}
	if ($('#fwinfoeu').prop('checked')){
		//Non-emergency medical assistance
		patterns.push({match:'116117'});
	}
	if ($('#fwint').prop('checked')){
		patterns.push({match:'011.'});
	}
	if ($('#fwld').prop('checked')){
		patterns.push({match:'1NXXNXXXXXX'});
	}
	if($('#dptable').length){
		var idbase = ($("tr[id^='dprow']").length + $('#dptable').length);
		$.each(patterns,function(){
			var match = (this.match)?this.match:'';
			var prefix = (this.prefix)?this.prefix:'';
			var cid = (this.cid)?this.cid:'';
			var prepend = (this.prepend)?this.prepend:'';
			var lastRow = $("tr[id^='dprow']").last();
			var id = idbase++;
			var newhtml = '';
			newhtml +='<tr id="dprow'+id+'">';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+10)+'">(</span>';
			newhtml +=	'		<input placeholder="prepend" type="text" id="prepend_digit_'+id+'" class="form-control " value="'+prepend+'">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+11)+'">)</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td>';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<input placeholder="prefix" type="text" id="pattern_prefix_'+id+'"  class="form-control " value="'+prefix+'"> ';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+12)+'">|</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td>';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+13)+'">[</span>';
			newhtml +=	'		<input placeholder="match pattern" type="text" id="pattern_pass_'+id+'" class="form-control dpt-value" value="'+match+'">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+14)+'">/</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td>';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<input placeholder="CallerID" type="text" id="match_cid_'+id+'"  class="form-control " value="'+cid+'">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+15)+'">]</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td><td>';
			newhtml +=	'		<a href="#" id="routerowadd'+id+'"><i class="fa fa-plus"></i></a>';
			newhtml +=	'		<a href="#" id="routerowdel'+id+'"><i class="fa fa-trash"></i></a>';
			newhtml +=	'</td>';
			newhtml +=	'</tr>';
			lastRow.parent().append(newhtml);
		});
	}
	if($('#bulk_patterns').length){
		$('#bulk_patterns').val(patterns.join("\r\n"));
	}
	$('#dpwizard').modal('hide');
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

//Notification email settings
var emailFromDefault = 'PBX@localhost.localdomain';
var emailSubjectDefault = _('PBX: A call has been placed via outbound route:') + ' {{ROUTENAME}}';
var emailBodyDefault = '-----------------------------------------'
	+ '\n' + _('Call Details:')
	+ '\n-----------------------------------------'
	+ '\n' + _('Call Time:') + ' {{MONTH}}-{{DAY}}-{{YEAR}} {{TIMEAMPM}} {{TZSHORT}}'
	+ '\n' + _('Caller:') + ' {{CALLERALL}}'
	+ '\n' + _('Call to:') + ' {{DIALEDNUMBER}}'
	+ '\n' + _('CallerID Sent:') + ' {{OUTGOINGCALLERIDALL}}'
	+ '\n' + _('Outbound Route:') + ' {{ROUTENAME}}'
	+ '\n' + _('CallUID:') + ' {{CALLUID}}';
//fill in the form with these defaults when adding a new route
if ($('#emailfrom').val() === '') { $('#emailfrom').val(emailFromDefault); }
if ($('#emailsubject').val() === '') { $('#emailsubject').val(emailSubjectDefault); }
if ($('#emailbody').val() === '') { $('#emailbody').val(emailBodyDefault); }

function check_pattern(pattern){
	if(pattern.trim().substring(0, 1) == "."){
		var msg_alert = '<div class="alert alert-dismissable alert-warning">'+
						'<button type="button" class="close" data-dismiss="alert" aria-hidden="true">'+
						'<i class="fa fa-times"></i>'+
						'</button>'+
						'<strong>'+_('Warning!')+'</strong> '+_('A dial pattern of a single dot is STRONGLY DISCOURAGED. It is recommended that you change it to X.')+
						'</div>';
		$("#msg_alert_pattern").html(msg_alert);
	}
}

bindToLast();
function bindToLast() {
	$("[id^='trunkpri']:last").one("change", function(){
		var id = /trunkpri([0-9]*)/.exec($(this).attr('id')),
				nextid = (Number(id[1]) +1),
				curRow,
				newRow,
				newinput;

		if($(this).val() === ''){
			curRow = $(this).closest('tr');
			curRow.fadeOut(2000, function(){
				$(this).remove();
			});
		}else{
			curRow = $(this).closest('tr');
			newRow = curRow.clone(true);
			$(newRow).attr('id', 'trunkrow'+nextid);
			newinput = newRow.find("[id^='trunkpri']");
			$(newinput).attr('id', 'trunkpri'+nextid);
			$(newinput).attr('name', 'trunkpriority['+nextid+']');
			curRow.after(newRow);
		}
		bindToLast();
	});
}

$("#duplicate").click(function(e){
	e.preventDefault();
	e.stopPropagation();
	var name = $("#routename").val();
	$("#routename").val(name + "-" + _("copy"));
	$("#action").val("addroute");
	$("#extdisplay").val("");
	$("#id").val("");
	$("#routeEdit").submit();
	//$("#action").val("copyroute");
});
$("#routeEdit").submit(function(){
	tmp_route_name  = $("#routename").val().trim();
	if($.inArray(tmp_route_name, routing_names) != -1){
		alert(_("The Route Name '" + tmp_route_name  + "' already used, please use a different name."));
		return false;
	}
	var patlen = 0;
	if($("#outcid_modeyes").is(":checked") && $("#outcid").val().trim() === "") {
		warnInvalid($("#outcid"), _("Route CID must be set if Override Extension is set to yes"));
		return false;
	}
	if($("#emergency").is(":checked") && $("#intracompany").is(":checked")){
		alert(_("Route Type: Emergency and Intra company cann't be selected at the same time."));
		return false;
	}
	$("#dptable").find('input').each(function(){patlen += $(this).val().length;});
	if($("[name='pattern_file']").val().length > 0){
		patlen += 1;
	}
	if($('#bulk_patterns').length) {
		patlen += $('#bulk_patterns').val().length;
	}
	if(patlen === 0){
		alert(_("You must complete the dial pattern tab before submitting"));
		$('.nav-tabs a[href="#dialpatterns"]').tab('show');
		return false;
	}
	var curpatterns = [];
	$("[id^='prepend_digit_']").each(function(){
	    var prepend_digit = $(this).val() || "";
	    var pattern_prefix = $(this).parent().parent().parent().find("[id^='pattern_prefix_']").val() || "";
	    var pattern_pass = $(this).parent().parent().parent().find("[id^='pattern_pass_']" ).val() || "";
	    var match_cid = $(this).parent().parent().parent().find("[id^='match_cid_']" ).val() || "";
	    curpatterns.push({'prepend_digit': prepend_digit, 'pattern_prefix':pattern_prefix, 'pattern_pass': pattern_pass, 'match_cid':match_cid});
	});
	var data = JSON.stringify(curpatterns);
	$("#dialpatterndata").val(data);

    //Notifications - fill in the defaults if they try to save these with blank values
	if ($('#emailfrom').val() === '') { $('#emailfrom').val(emailFromDefault); }
	if ($('#emailsubject').val() === '') { $('#emailsubject').val(emailSubjectDefault); }
	if ($('#emailbody').val() === '') { $('#emailbody').val(emailBodyDefault); }

	return true;
});
