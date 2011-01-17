$(document).ready(function() {
	//hope this isnt to much to ask for... Not sure why this doent happen automaticaly, but this kludge works!
	//if(url_pram('display') == 'advancedsettings'){$('#footer').remove().appendTo($('#wrapper'));}
	//save settings
	function savebinder(e) {
		if (can_write_amportalconf != 1 ) {
			alert(amportalconf_error);
			return false;
		}
		mythis = $(this);
		mykey = $(this).attr('data-key');
		$.ajax({
			type: 'POST',
			url: location.href,
			data: {
					quietmode: 1,
					skip_astman:1,
					restrictmods: 'core',
					action: 'setkey',
					keyword: mykey,
					value: $('#' + mykey).val()
					},
			beforeSend: function(XMLHttpRequest) {
				mythis.attr({src: 'images/spinner.gif'})
			},
			success: function(data, textStatus, XMLHttpRequest) {
				mythis.attr({src: 'images/accept.png'});
				if (data != 'ok') {
					alert('ERROR: When saving key ' + mykey);
				} else {
					mythis.parent().delay(500).fadeOut();
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
		if($(this).val() != $(this).attr('data-valueinput-orig')){
			
			myel.stop(true, true).delay(1000).fadeIn();
			//only bind if not already bound
			if (myel.children(".save").data("events") == undefined
				|| typeof(myel.children('.save').data('events')["click"]) == undefined) {
				myel.children('.save').bind('click', savebinder);
			}
		} else {
			myel.stop(true, true).delay(1000).fadeOut();
			myel.children('.save').unbind('click', savebinder);
		}
	})

});

