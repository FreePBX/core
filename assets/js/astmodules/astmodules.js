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
$(document).ready(function(){
$('[id^="del"]').on('click', function(e){
  e.preventDefault();
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
});
