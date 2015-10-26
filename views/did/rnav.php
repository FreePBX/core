<div id="toolbar-bootnav">
	<a href="config.php?display=did&amp;view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add Inbound Route")?></a>
  <a href="config.php?display=did" class="btn btn-default"><i class="fa fa-list">&nbsp;<?php echo _("Inbound Routes List")?></i></a>
</div>
<table id="didtable"
 data-toolbar="#toolbar-bootnav"
 data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=allDID"
 data-cache="false"
 data-pagination="true"
 data-search="true"
 data-toggle="table"
 class="table table-striped">
	<thead>
		<tr>
			<th data-field="extension" data-formatter="bootnavlinkFormatter"><?php echo _("DID/CID")?></th>
		</tr>
	</thead>
</table>

<script type="text/javascript">
var destinations = <?php echo json_encode(FreePBX::Modules()->getDestinations())?>;
function bootnavnumberFormatter(value){
	if(value.length == 0){
		return _("Any");
	}else{
		return decodeURIComponent(value);
	}
}
function bootnavlinkFormatter(value, row){
  var extension = bootnavnumberFormatter(row['extension']);
  var cidnum = bootnavnumberFormatter(row['cidnum']);
  html = '<a href="?display=did&view=form&extdisplay='+extension+'%2F'+cidnum+'">'+extension+'/'+cidnum+'</a>';
	return html;
}
</script>
