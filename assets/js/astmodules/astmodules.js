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
$("#modnoload,#modpreload,#modload").on('post-body.bs.table',function(){
$('[id^="del"]').on('click', function(e){
  e.preventDefault();
	var currentTab = $("ul li.active").data('name');
	var modName = $(this).data('mod');
  var row = $(this).closest('tr');
  console.log(currentTab);
		$.get("ajax.php?module=core",
		{
			command: 'delastmodule',
			section: currentTab,
			astmod: modName
		},
		function(data,status){
      if(data){
        row.remove();
        fpbxToast(_('Modules updated'));
      }
		});
});
});
