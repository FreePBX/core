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
<div class="table-responsive">
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th><?php echo _("Channel") ?></th>
				<th><?php echo _("Description") ?></th>
				<th><?php echo _("DID") ?></th>
				<th><?php echo _("Actions") ?></th>
			</tr>
		</thead>
		<tbody>
			<?php echo $blrows ?>
		</tbody>
	</table>
</div>
