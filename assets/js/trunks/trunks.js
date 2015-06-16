
$("a[id^='rowadd']").click(function(e){
	e.preventDefault();
	var curRow = $("tr[id^='dprow']").last();
	var id = $("tr[id^='dprow']").length++;
	var newhtml = '';
	newhtml +='<tr id="dprow'+id+'">';
	newhtml +=	'<td class="hidden-xs prepend">';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+10)+'">(</span>';
	newhtml +=	'		<input placeholder="prepend" type="text" id="prepend_digit_'+id+'" name="prepend_digit[]" class="form-control " value="">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+11)+'">)</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td>';
	newhtml +=	'<td class="prefix">';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<input placeholder="prefix" type="text" id="pattern_prefix_'+id+'" name="pattern_prefix[]" class="form-control " value=""> ';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+12)+'">|</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td>';
	newhtml +=	'<td class="match">';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+13)+'">[</span>';
	newhtml +=	'		<input placeholder="match pattern" type="text" id="pattern_pass_'+id+'" name="pattern_pass[]" class="form-control dpt-value" value="">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+14)+'">/</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td>';
	newhtml +=	'<td class="hidden-xs hidden-sm callerid">';
	newhtml +=	'	<div class="input-group">';
	newhtml +=	'		<input placeholder="CallerID" type="text" id="match_cid_'+id+'" name="match_cid[]" class="form-control " value="">';
	newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+15)+'">]</span>';
	newhtml +=	'	</div>';
	newhtml +=	'</td><td>';
	newhtml +=	'		<a href="#" id="routerowadd'+id+'"><i class="fa fa-plus"></i></a>';
	newhtml +=	'		<a href="#" id="routerowdel'+id+'"><i class="fa fa-trash"></i></a>';
	newhtml +=	'</td>';
	newhtml +=	'</tr>';
	curRow.parent().append(newhtml);
});
$("a[id^='rowdel']").click(function(){
	var curRow = $(this).closest('tr');
	curRow.fadeOut(2000, function(){
		$(this).remove();
	});
});
//DialPlan Wizard
$("[id='trunkgetlocalprefixes']").click(function(){
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
		var idbase = ($("tr[id^='dprow']").length + $('#dptable').length);
		$.each(patterns,function(){
			var lastRow = $("tr[id^='dprow']").last();
			var id = idbase++;
			console.log(idbase);
			var newhtml = '';
			newhtml +='<tr id="dprow'+id+'">';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+10)+'">(</span>';
			newhtml +=	'		<input placeholder="prepend" type="text" id="prepend_digit_'+id+'" name="prepend_digit[]" class="form-control " value="">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+11)+'">)</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td>';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<input placeholder="prefix" type="text" id="pattern_prefix_'+id+'" name="pattern_prefix[]" class="form-control " value=""> ';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+12)+'">|</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td>';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+13)+'">[</span>';
			newhtml +=	'		<input placeholder="match pattern" type="text" id="pattern_pass_'+id+'" name="pattern_pass[]" class="form-control dpt-value" value="'+this+'">';
			newhtml +=	'		<span class="input-group-addon" id="basic-addon'+(id+14)+'">/</span>';
			newhtml +=	'	</div>';
			newhtml +=	'</td>';
			newhtml +=	'<td>';
			newhtml +=	'	<div class="input-group">';
			newhtml +=	'		<input placeholder="CallerID" type="text" id="match_cid_'+id+'" name="match_cid[]" class="form-control " value="">';
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
	$('#dploading').modal('hide');
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

$(document).ready(function(){
  /* Add a Custom Var / Val textbox */
  $("#dial-pattern-add").click(function(){
    addCustomField('','','',$("#last_row"));
  });
  $('#pattern_file').hide();
  $("#dial-pattern-clear").click(function(){
    clearAllPatterns();
  });
});

function patternsRemove(idx) {
  $("#prepend_digit_"+idx).parent().parent().remove();
}

function addCustomField(prepend_digit, pattern_prefix, pattern_pass, start_loc, scroll) {
	if(typeof scroll === 'undefined'){
		var scroll = true;
	};
	if($('#bulk_patterns').length) {
		var idx = 1;
		var prepend_digit = (prepend_digit != '') ? prepend_digit+"+" : "";
		var pattern_prefix = (pattern_prefix != '') ? pattern_prefix+"|" : "";
		$('#bulk_patterns').val($('#bulk_patterns').val()+prepend_digit+pattern_prefix+pattern_pass+"\n");
	} else {
		var idx = $(".dial-pattern").size();
		var idxp = idx - 1;
		var tabindex = parseInt($("#pattern_pass_"+idxp).attr('tabindex')) + 1;
		var tabindex1 = tabindex + 2;
		var tabindex2 = tabindex + 3;
		var dpt_title = 'dpt-title dpt-display';
		var dpt_prepend_digit = prepend_digit == '' ? dpt_title : 'dpt-value';
		var dpt_pattern_prefix = pattern_prefix == '' ? dpt_title : 'dpt-value';
		var dpt_pattern_pass = pattern_pass == '' ? dpt_title : 'dpt-value';

		var new_insert = start_loc.before('\
			<tr>\
				<td colspan="2">\
					(<input placeholder="<?php echo $pp_tit?>" type="text" size="10" id="prepend_digit_'+idx+'" name="prepend_digit['+idx+']" class="dp-prepend dial-pattern '+dpt_prepend_digit+'" value="'+prepend_digit+'" tabindex="'+tabindex+'">) +\
					<input placeholder="<?php echo $pf_tit?>" type="text" size="6" id="pattern_prefix_'+idx+'" name="pattern_prefix['+idx+']" class="dp-prefix '+dpt_pattern_prefix+'" value="'+pattern_prefix+'" tabindex="'+tabindex1+'"> |\
					<input placeholder="<?php echo $mp_tit?>" type="text" size="16" id="pattern_pass_'+idx+'" name="pattern_pass['+idx+']" class="dp-match '+dpt_pattern_pass+'" value="'+pattern_pass+'" tabindex="'+tabindex2+'">\
					<img src="images/core_add.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("insert")?>" title="<?php echo _("Click here to insert a new pattern before this pattern")?>" onclick="addCustomField(\'\',\'\',\'\',$(\'#prepend_digit_'+idx+'\').parent().parent(),false)">\
					<img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("remove")?>" title="<?php echo _("Click here to remove this pattern")?>" onclick="patternsRemove('+idx+')">\
				</td>\
			</tr>\
		').prev();

		if(scroll) {
			$('.dialpatterns').animate({"scrollTop": $('.dialpatterns')[0].scrollHeight}, "fast");
		}
	}
	return idx;
}

function clearPatterns() {
  $(".dpt-display").each(function() {
    if($(this).val() == $(this).data("defText")) {
      $(this).val("");
    }
  });
  return true;
}

function clearAllPatterns() {

  $(".dpt-value").addClass('dpt-title dpt-nodisplay').removeClass('dpt-value').mouseover(function(){

  }).each(function(){
    $(this).val("");
  });

  return true;
}

// all blanks are ok
function validatePatterns() {
  var culprit;
  var msgInvalidDialPattern;
  defaultEmptyOK = true;

  // TODO: need to validate differently for prepend, prefix and match fields. The prepend
  //      must be a dialable digit. The prefix can be any pattern but not contain "." and
  //      the pattern can contain a "." also
  //$filter_prepend = '/[^0-9\+\*\#/';
  //$filter_match = '/[^0-9\-\+\*\#\.\[\]xXnNzZ]/';
  //$filter_prefix = '/[^0-9\-\+\*\#\[\]xXnNzZ]/';
	//defaultEmptyOK = false;
  /* TODO: get some sort of check in for dialpatterns
	if (!isDialpattern(theForm.dialpattern.value))
		return warnInvalid(theForm.dialpattern, msgInvalidDialPattern);
    */

  $(".dp-prepend").each(function() {
    if ($.trim(this.value) === '') {
    } else if (this.value.search('[^0-9*#+wW\s]+') >= 0) {
      culprit = this;
      return false;
    }
  });
  if (!culprit) {
    $(".dp-prefix").each(function() {
      if ($.trim($(this).val()) === '') {
      } else if (!isDialpattern(this.value) || this.value.search('[._]+') >= 0) {
        culprit = this;
        return false;
      }
    });
  }
  if (!culprit) {
    $(".dp-match").each(function() {
      if ($.trim(this.value) === '') {
      } else if (!isDialpattern(this.value) || this.value.search('[_]+') >= 0) {
        culprit = this;
        return false;
      }
    });
  }

  if (culprit !== undefined) {
	  msgInvalidDialPattern = "<?php echo _('Dial pattern is invalid'); ?>";
    return warnInvalid(culprit, msgInvalidDialPattern);
  } else {
    return true;
  }
}

//document.trunkEdit.trunk_name.focus();

$(document).ready(function() {
	$('#submit, #duplicate').click(function() {
		var theForm = document.trunkEdit;

		defaultEmptyOK = true;

		if (isEmpty($.trim($('#trunkEdit input[name="outcid"]').val()))) {
			if ($('#trunkEdit input[name="keepcid"]').val() == 'on' || $('#trunkEdit input[name="keepcid"]').val() == 'all') {
				return warnInvalid(theForm.outcid, msgCIDValueRequired);
			} else {
				if (confirm(msgCIDValueEmpty) === false) {
					return false;
				}
			}
		}

		if (!isCallerID($('#trunkEdit input[name="outcid"]').val()))
			return warnInvalid(theForm.outcid, msgInvalidOutboundCID);

		if (!isInteger($('#trunkEdit input[name="maxchans"]').val()))
			return warnInvalid(theForm.maxchans, msgInvalidMaxChans);

		if (!isDialIdentifierSpecial($('#dialoutprefix').val())) {
			if (confirm(msgInvalidOutboundDialPrefix) === false) {
				$('#dialoutprefix').focus();
				return false;
			}
		}

		if (isEmpty($.trim($('#trunkEdit input[name="trunk_name"]').val()))) {
			return warnInvalid(theForm.trunk_name, msgInvalidTrunkName);
		}

		if(tech != 'enum' && tech != 'custom' && tech != 'dundi' && tech != 'pjsip') {
			defaultEmptyOK = true;
			if (isEmpty(theForm.channelid.value) || isWhitespace(theForm.channelid.value))
				return warnInvalid(theForm.channelid, msgInvalidTrunkName);

			if (theForm.channelid.value == theForm.usercontext.value)
				return warnInvalid(theForm.usercontext, msgInvalidTrunkAndUserSame);
		} else if (tech == 'custom' || tech == 'dundi') {
			if (isEmpty(theForm.channelid.value) || isWhitespace(theForm.channelid.value))
				return warnInvalid(theForm.channelid, msgInvalidChannelName);

			if (theForm.channelid.value == theForm.usercontext.value)
				return warnInvalid(theForm.usercontext, msgInvalidTrunkAndUserSame);
		} else if(tech == 'pjsip') {
			if (isEmpty($('#trunkEdit input[name="sip_server"]').val())) {
				return warnInvalid(theForm.sip_server, msgInvalidSIPServer);
			}
			if (isEmpty($('#trunkEdit input[name="sip_server_port"]').val())) {
				return warnInvalid(theForm.sip_server_port, msgInvalidSIPServerPort);
			}
		}

		if(tech == 'sip' || tech.substr(0,3) == 'iax') {
			if ((isEmpty(theForm.usercontext.value) || isWhitespace(theForm.usercontext.value)) && (!isEmpty(theForm.userconfig.value) && !isWhitespace(theForm.userconfig.value)) && (theForm.userconfig.value != "secret=***password***\ntype=user\ncontext=from-trunk")) {
				if (confirm(msgConfirmBlankContext) === false)
				return false;
			}
		}

		clearPatterns();
		if (validatePatterns()) {
			if ($(this).prop('name') === 'duplicate') {
				theForm.action.value = 'copytrunk';
			}
			return true;
		} else {
			return false;
		}
	});
});

function isDialIdentifierSpecial(s) { // special chars allowed in dial prefix (e.g. fwdOUT)
	var i;

	if (isEmpty(s)) {
		if (isDialIdentifierSpecial.arguments.length == 1) {
			return defaultEmptyOK;
		} else {
			return (isDialIdentifierSpecial.arguments[1] === true);
		}
	}

	for (i = 0; i < s.length; i++) {
		var c = s.charAt(i);

		if ( !isDialDigitChar(c) && (c != "w") && (c != "W") && (c != "q") && (c != "Q") && (c != "+") ) {
			return false;
		}
	}

	return true;
}
