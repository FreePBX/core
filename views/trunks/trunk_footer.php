			<tr>
				<td colspan="2">
          <h6>
            <input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>" tabindex="<?php echo ++$tabindex;?>">
            <input name="copytrunk" type="submit" value="<?php echo _("Duplicate Trunk");?>"/>
            <!--input type="button" id="page_reload" value="<?php echo _("Refresh Page");?>"/-->
          </h6>
				</td>
			</tr>
			</table>

<?php
$pp_tit = _("prepend");
$pf_tit = _("prefix");
$mp_tit = _("match pattern");
?>
<script language="javascript">
<!--

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
    if ($.trim(this.value) == '') {
    } else if (this.value.search('[^0-9*#+wW\s]+') >= 0) {
      culprit = this;
      return false;
    }
  });
  if (!culprit) {
    $(".dp-prefix").each(function() {
      if ($.trim($(this).val()) == '') {
      } else if (!isDialpattern(this.value) || this.value.search('[._]+') >= 0) {
        culprit = this;
        return false;
      }
    });
  }
  if (!culprit) {
    $(".dp-match").each(function() {
      if ($.trim(this.value) == '') {
      } else if (!isDialpattern(this.value) || this.value.search('[_]+') >= 0) {
        culprit = this;
        return false;
      }
    });
  }

  if (culprit != undefined) {
	  msgInvalidDialPattern = "<?php echo _('Dial pattern is invalid'); ?>";
    return warnInvalid(culprit, msgInvalidDialPattern);
  } else {
    return true;
  }
}

document.trunkEdit.trunk_name.focus();

function trunkEdit_onsubmit(act) {
	var theForm = document.trunkEdit;

	var tech = '<?php echo !empty($tech) ? strtolower($tech) : strtolower($_REQUEST['tech']) ?>';
	var msgInvalidOutboundCID = "<?php echo _('Invalid Outbound CallerID'); ?>";
	var msgInvalidMaxChans = "<?php echo _('Invalid Maximum Channels'); ?>";
	var msgInvalidDialRules = "<?php echo _('Invalid Dial Rules'); ?>";
	var msgInvalidOutboundDialPrefix = "<?php echo _('The Outbound Dial Prefix contains non-standard characters. If these are intentional the press OK to continue.'); ?>";
	var msgInvalidTrunkName = "<?php echo _('Invalid Trunk Name entered'); ?>";
	var msgInvalidChannelName = "<?php echo _('Invalid Custom Dial String entered'); ?>";
	var msgInvalidTrunkAndUserSame = "<?php echo _('Trunk Name and User Context cannot be set to the same value'); ?>";
	var msgConfirmBlankContext = "<?php echo _('User Context was left blank and User Details will not be saved!'); ?>";
	var msgCIDValueRequired = "<?php echo _('You must define an Outbound CallerID when Choosing this CID Options value'); ?>";
	var msgCIDValueEmpty = "<?php echo _('It is highly recommended that you define an Outbound CallerID on all trunks, undefined behavior can result when nothing is specified. The CID Options can control when this CID is used. Do you still want to continue?'); ?>";
	var msgInvalidServerURI = "<?php echo _('You Must define a Server URI')?>";
	var msgInvalidClientURI = "<?php echo _('You must defined a Client URI')?>";
	var msgInvalidAORContact = "<?php echo _('You must define a(n) AOR Contact')?>";
	var msgInvalidSIPServer = "<?php echo _('You must define a SIP Server')?>";
	var msgInvalidSIPServerPort = "<?php echo _('You must define a SIP Port')?>";

	defaultEmptyOK = true;

	if (isEmpty($.trim($('#trunkEdit input[name="outcid"]').val()))) {
		if ($('#trunkEdit input[name="keepcid"]').val() == 'on' || $('#trunkEdit input[name="keepcid"]').val() == 'all') {
			return warnInvalid(theForm.outcid, msgCIDValueRequired);
		} else {
			if (confirm(msgCIDValueEmpty) == false) {
				return false;
			}
		}
	}

	if (!isCallerID($('#trunkEdit input[name="outcid"]').val()))
		return warnInvalid(theForm.outcid, msgInvalidOutboundCID);

	if (!isInteger($('#trunkEdit input[name="maxchans"]').val()))
		return warnInvalid(theForm.maxchans, msgInvalidMaxChans);

	if (!isDialIdentifierSpecial($('#dialoutprefix').val())) {
		if (confirm(msgInvalidOutboundDialPrefix) == false) {
			$('#dialoutprefix').focus();
			return false;
		}
	}

	if (isEmpty($.trim($('#trunkEdit input[name="trunk_name"]').val()))) {
		return warnInvalid(theForm.trunk_name, msgInvalidTrunkName);
	}

	if(tech == 'pjsip') {
		console.log('OK');
		if($('#configmode').val() == 'advanced') {
			if (isEmpty($('#trunkEdit input[name="client_uri"]').val())) {
				return warnInvalid(theForm.client_uri, msgInvalidClientURI);
			}
			if (isEmpty($('#trunkEdit input[name="server_uri"]').val())) {
				return warnInvalid(theForm.server_uri, msgInvalidServerURI);
			}
			if (isEmpty($('#trunkEdit input[name="aor_contact"]').val())) {
				return warnInvalid(theForm.aor_contact, msgInvalidAORContact);
			}
		}
		if (isEmpty($('#trunkEdit input[name="sip_server"]').val())) {
			return warnInvalid(theForm.sip_server, msgInvalidSIPServer);
		}
		if (isEmpty($('#trunkEdit input[name="sip_server_port"]').val())) {
			return warnInvalid(theForm.sip_server_port, msgInvalidSIPServerPort);
		}
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
	}

	if(tech == 'sip' || tech.substr(0,3) == 'iax') {
		if ((isEmpty(theForm.usercontext.value) || isWhitespace(theForm.usercontext.value)) && (!isEmpty(theForm.userconfig.value) && !isWhitespace(theForm.userconfig.value)) && (theForm.userconfig.value != "secret=***password***\ntype=user\ncontext=from-trunk")) {
			if (confirm(msgConfirmBlankContext) == false)
			return false;
		}
	}

	clearPatterns();
	if (validatePatterns()) {
		theForm.action.value = act;
		return true;
	} else {
		return false;
	}
}

function isDialIdentifierSpecial(s) { // special chars allowed in dial prefix (e.g. fwdOUT)
    var i;

    if (isEmpty(s))
       if (isDialIdentifierSpecial.arguments.length == 1) return defaultEmptyOK;
       else return (isDialIdentifierSpecial.arguments[1] == true);

    for (i = 0; i < s.length; i++)
    {
        var c = s.charAt(i);

        if ( !isDialDigitChar(c) && (c != "w") && (c != "W") && (c != "q") && (c != "Q") && (c != "+") ) return false;
    }

    return true;
}
//-->
</script>

		</form>
