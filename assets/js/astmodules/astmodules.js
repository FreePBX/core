$(function(){
    $(window).on('scroll', function () {
      var scrollPos = $(document).scrollTop();
      $('#addform').css({
         top: scrollPos
      });
   }).scroll();
});
$("#addmodule").click(function(){
	var currentTab = $("ul li.active").data('name');
	var modName = $("#module").val();
	$.get("config.php?display=astmodules",
		{
			action: 'add',
			section: currentTab,
			module: modName
		},
		function(data,status){			
			location.reload();
		});
	
});
$('[id^="del"]').on('click', function(){
	var currentTab = $("ul li.active").data('name');
	var modName = $(this).data('mod');
		$.get("config.php?display=astmodules",
		{
			action: 'del',
			section: currentTab,
			module: modName
		},
		function(data,status){			
			location.reload();
		});
	
});
$(document).on('show.bs.tab', 'a[data-toggle="tab"]', function (e) {
    var clicked = $(this).attr('href');
    switch(clicked){
		case '#amodload':
			$("#addform").addClass('hidden');
		break;
		case '#amodnoload':
			$("#addform").removeClass('hidden');
		break;
		case '#amodpreload':
			$("#addform").removeClass('hidden');
		break;
		default:
		break;
	}
})
