
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" data-name="alldids" class="active">
		<a href="#alldids" aria-controls="alldids" role="tab" data-toggle="tab" aria-expanded="false">All Trunks</a>
	</li>
</ul>
<div class="tab-content display">
	<div role="tabpanel" id="alldids" class="tab-pane active">
		<table data-toolbar="#toolbar-all" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-all">
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
	</div>
</div>
