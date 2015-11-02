<div id="bnavtrunk">
  <div class="btn-group">
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
      <i class="fa fa-plus">&nbsp;</i><?php echo _("Add Trunk")?> <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu">
  		<?php
  		foreach($trunk_types as $type => $label) {
  			echo '<li><a href="config.php?display=trunks&amp;tech='.$type.'" ><i class="fa fa-plus"></i>&nbsp;<strong>'.sprintf(_("Add %s Trunk"),$label).'</strong></a></li>';
  		}
  		?>
    </ul>
  </div>
	<a href="?display=trunks" class="btn btn-default"><i class="fa fa-list"></i>&nbsp;<?php echo _("List Trunks")?></a>

</div>
<table data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=allTrunks" data-cache="false" data-toggle="table" data-search="true" data-toolbar="#bnavtrunk" class="table" id="table-all-side">
	<thead>
			<tr>
					<th data-formatter="rtrunk" data-sortable="true" data-field="trunkid"><?php echo _('Trunks')?></th>
			</tr>
	</thead>
</table>
<script>
	function rtrunk(v,r){
    return r['name']+'&nbsp;('+r['tech']+')';
  }
  $("#table-all-side").on('click-row.bs.table',function(e,row,elem){
    window.location = '?display=trunks&tech='+row['tech']+'&extdisplay=OUT_'+row['trunkid'];
  })
</script>
