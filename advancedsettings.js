$(document).ready(function() {
	//save settings
	function savebinder(e) {
		if (can_write_amportalconf != 1 ) {
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
		//console.log('saving ',mykey,'as',myval,'which is a',$(this).attr('type'))
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
				//console.log('set',set)
				mythis.attr({src: 'images/spinner.gif'})
			},
			success: function(data, textStatus, XMLHttpRequest) {
				mythis.attr({src: 'images/accept.png'});
				if (data != 'ok') {
					alert('ERROR: When saving key ' + mykey);
				} else {
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
				alert('ERROR: When saving key ' + mykey + ': ' + textStatus);
			}
		
		})
	}
	//set defualt values
	$('.adv_set_default').click(function(){
		$('#'+$(this).attr('data-key')).val($(this).attr('data-default')).trigger('change');
	});
	//show save button
	$('.valueinput').bind('keyup keypress keydown paste change', function(){
		var myel = $(this).parent().next().next();
		//console.log(myel)
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

