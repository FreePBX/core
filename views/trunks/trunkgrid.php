<div id="toolbar-all">
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
</div>
<table data-toolbar="#toolbar-all" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" id="table-all">
	<thead>
		<tr>
			<th data-sortable="true"><?php echo _('Name')?></th>
			<th data-sortable="true"><?php echo _('Tech')?></th>
			<th data-sortable="true"><?php echo _('CallerID')?></th>
			<th data-sortable="true"><?php echo _('Status')?></th>
			<th><?php echo _('Actions')?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($trunks as $trunk) { ?>
			<tr id="<?php echo $trunk['tresult']['trunkid']?>">
				<td><?php echo $trunk['tresult']['name']?></td>
				<td><?php echo $trunk['tresult']['tech']?></td>
				<td><?php echo $trunk['tresult']['outcid']?></td>
				<td><?php echo $trunk['tresult']['disabled'] == 'on'?_("Disabled"):_("Enabled")?></td>
				<td>
					<a href="config.php?display=trunks&amp;tech=<?php echo $trunk['tresult']['tech']?>&amp;extdisplay=OUT_<?php echo $trunk['tresult']['trunkid']?>"><i class="fa fa-edit"></i></a>
					<a class="delAction" href="config.php?display=trunks&amp;extdisplay=OUT_<?php echo $trunk['tresult']['trunkid']?>&amp;action=deltrunk"><i class="fa fa-trash"></i></a>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
