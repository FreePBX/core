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
				console.log(data);
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
		data: 'action=delroute&quietmode=1&skip_astman=1&restrictmods=core&id='+id,
		dataType: 'json',
		success: function(data) {
			console.log(data);
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
