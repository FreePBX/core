$(document).ready(function() {
	//save settings
	function savebinder(e) {
		if (!can_write_amportalconf) {
			alert(amportalconf_error);
			return false;
		}
		var mythis = $(this);
		var mykey = $(this).attr('data-key');
		switch ($(this).attr('data-type')) {
			case 'BOOL':
				var myval = $('input[name="' + mykey + '"]:checked').val();
				break;
			default:
				var myval = $('#' + mykey).val();
				break;
		}
		$.ajax({
			type: 'POST',
			url: location.href,
			data: {
					quietmode: 1,
					skip_astman:1,
					restrictmods: 'core',
					action: 'setkey',
					keyword: mykey,
					value: myval
					},
			beforeSend: function(XMLHttpRequest, set) {
				mythis.attr({src: 'images/spinner.gif'})
			},
      dataType: 'json',
			success: function(data, textStatus, XMLHttpRequest) {
				mythis.attr({src: 'images/accept.png'});
        if (!data.validated) {
					alert(data.msg);
				}
				if (!data.validated && data.saved) {
				  $('#' + mykey).val(data.saved_value);
				}
				if (data.saved) {
					mythis.parent().delay(500).fadeOut();
				}
				
				//reset data-valueinput-orig to new value
				switch (mythis.attr('data-type')) {
					case 'BOOL':
						$('input[name="' + mykey + '"]').attr('data-valueinput-orig', mykey);
						break;
					default:
						var myval = $('#' + mykey).attr('data-valueinput-orig', mykey);
						break;
				}
			},
			error: function(data, textStatus, XMLHttpRequest) {
				alert('Ajax Web ERROR: When saving key ' + mykey + ': ' + textStatus);
			}
		
		})
	}
	//set defualt values
	$('.adv_set_default').click(function(){
		switch ($(this).attr('data-type')) {
		case 'BOOL':
			$('input[name="' + $(this).attr('data-key')).removeAttr("checked");
			$('input[name="' + $(this).attr('data-key') + '"]').filter('[value=' + $(this).attr('data-default') + ']').attr("checked","checked").trigger('change');
			break;
		default:
			$('#'+$(this).attr('data-key')).val($(this).attr('data-default')).trigger('change');
			break;
		}
	});
	//show save button
	$('.valueinput').bind('keyup keypress keydown paste change', function(){
		var myel = $(this).parent().next().next();
		if($(this).val() != $(this).attr('data-valueinput-orig')){
			
			myel.stop(true, true).delay(100).fadeIn();
			//only bind if not already bound
			if (myel.children(".save").data("events") == undefined
				|| typeof(myel.children('.save').data('events')["click"]) == undefined) {
				myel.children('.save').bind('click', savebinder);
			}
		} else {
			myel.stop(true, true).delay(100).fadeOut();
			myel.children('.save').unbind('click', savebinder);
		}
	})

});

