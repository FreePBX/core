$('.fpbx-submit').submit(function() {
	var theForm = document.ampuser,
		username = theForm.username;

	if (username.value === "") {
		return warnInvalid(username, _("Username must not be blank"));
	} else if (!username.value.match('^[a-zA-Z][a-zA-Z0-9\.]+$')) {
		return warnInvalid(username, _("Username cannot start with a number, and can only contain letters and numbers"));
	}
	return true;
});

$(function  () {
  $("#selected").sortable({
	connectWith : "#unselected",
	update: function(e, ui){
		var selected = $("#selected li");
		var valarray = [];
		selected.each(function(){valarray.push($(this).data('id'));});
		$('input[name^="sections"]').each(function(){$(this).remove();});
		if($("#unselected li").length !== 0){
			$(valarray).each(function(){
				$("#ampuser").append('<input type="hidden" name="sections[]" value="'+this+'" />');
			});
		}else{
			$("#ampuser").append('<input type="hidden" name="sections[]" value="*" />');
		}
	},
	forcePlaceholderSize: true
  });
  $("#unselected").sortable({
	connectWith : "#selected",
	update: function(e, ui){
		var selected = $("#selected li");
		var valarray = [];
		selected.each(function(){valarray.push($(this).data('id'));});
		$('input[name^="sections"]').each(function(){$(this).remove();});
		if($("#unselected li").length !== 0){
			$(valarray).each(function(){
				$("#ampuser").append('<input type="hidden" name="sections[]" value="'+this+'" />');
			});
		}else{
			$("#ampuser").append('<input type="hidden" name="sections[]" value="*" />');
		}
	},
  });
});
$("#selectall").click(function(e){
	e.preventDefault();

	$("#unselected li").each(function(){
		var ci = $(this);
		$( "#selected" ).sortable('option','update')(null,{
			item: ci.appendTo("#selected")
		});
	});
});

$("#unselectall").click(function(e){
	e.preventDefault();
	$("#selected li").each(function(){
		var ci = $(this);
		$( "#unselected" ).sortable('option','update')(null,{
			item: ci.appendTo("#unselected")
		});
	});
});
