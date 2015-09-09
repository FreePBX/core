<?php
$blrows = '';
foreach (core_dahdichandids_list() as $item) {
		$channel = $item['channel'];
		$did = $item['did'];
		$description = $item['description'];
$blrows .= <<<HERE
<tr id = "row$channel">
<td>$channel</td>
<td>$description</td>
<td>$did</td>
<td><a href="config.php?display=dahdichandids&view=add&extdisplay=$channel">
	<i class="fa fa-edit"></i></a>&nbsp;&nbsp;
	<a href="#" id="del$channel" data-channel="$channel" >
	<i class="fa fa-trash-o"></i></a></td>
</tr>
HERE;
}
?>
<div id='ddidtoolbar'>
	<a href="config.php?display=dahdichandids&amp;view=add" class="btn btn-default"><i class = "fa fa-plus"></i>&nbsp;&nbsp;<?php echo _("Add DAHDI DID")?></a>
</div>
<table id="ddidgrid"
			data-toolbar="#ddidtoolbar"
			data-cache="false"
			data-state-save="true"
			data-state-save-id-table="dcdidgrid"
			data-toolbar="#toolbar-all"
			data-maintain-selected="true"
			data-toggle="table"
			data-pagination="true"
			data-search="true"
			class="table table-striped">
		<thead>
			<tr>
				<th data-sortable='true'><?php echo _("Channel") ?></th>
				<th data-sortable='true'><?php echo _("Description") ?></th>
				<th data-sortable='true'><?php echo _("DID") ?></th>
				<th><?php echo _("Actions") ?></th>
			</tr>
		</thead>
		<tbody>
			<?php echo $blrows ?>
		</tbody>
	</table>
