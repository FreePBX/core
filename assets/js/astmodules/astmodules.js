$(function(){
    $(window).on('scroll', function () {
      var scrollPos = $(document).scrollTop();
      $('#addform').css({
         top: scrollPos
      });
   }).scroll();
});
$(document).ready(function(){
  var tabid = localStorage.getItem('astmodules.chosentab');
  if(tabid !== null){
    var thistab = $('a[href="'+tabid+'"]');
    thistab.trigger("click");
  }
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

$('a[href="#amodnoload"],a[href="#amodpreload"],a[href="#amodload"]').on('click',function(e){
  localStorage.setItem('astmodules.chosentab', e.target.hash);
});



$("#amodnoload,#amodpreload,#amodload").on('post-body.bs.table',function(){
$('[id^="del"]').on('click', function(e){
  e.preventDefault();
	var currentTab = $("ul li.active").data('name');
  localStorage.setItem('astmodules.tab', currentTab);
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
