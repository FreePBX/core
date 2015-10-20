<?php switch($display) {?>
<?php case "extensions":?>
	<?php if($show) { ?>
		<div class="text-center">
			<a href="?display=extensions" class="btn btn-sm"><i class="fa fa-list"></i> <?php echo _("List All Extensions")?></a>
		<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) { ?>
			<a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?>" class="btn btn-sm"><?php echo sprintf(_("Add New %s Extension"), $driver['shortName'])?></a>
		<?php } ?>
		</div>
		<table data-url="ajax.php?module=core&amp;command=getExtensionGrid&amp;type=all" data-cache="false" data-toggle="table" data-search="true" class="table" id="table-all-side">
			<thead>
				<tr>
					<th data-sortable="true" data-field="extension"><?php echo _('Extension')?></th>
					<th data-sortable="true" data-field="name"><?php echo _('Name')?></th>
				</tr>
			</thead>
		</table>
	<?php } else { ?>
		<div class="bootnav" style="margin-top:15px">
			<div class="list-group">
				<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) { ?>
					<a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?>" class="list-group-item"><?php echo sprintf(_("Add New %s Extension"), $driver['shortName'])?></a>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
<?php break;?>
<?php case "users":?>
<?php if($show) { ?>
	<div class="text-center">
		<div class="text-center">
			<a href="?display=users" class="btn btn-sm"><i class="fa fa-list"></i> <?php echo _("List Users")?></a>
			<a href="?display=users&amp;view=add" class="btn btn-sm"><?php echo _("Add User")?></a>
		</div>
	</div>
	<table data-url="ajax.php?module=core&amp;command=getUserGrid&amp;type=all" data-cache="false" data-toggle="table" data-search="true" class="table " id="table-all-users-side">
		<thead>
			<tr>
				<th data-sortable="true" data-field="extension"><?php echo _('User')?></th>
				<th data-sortable="true" data-field="name"><?php echo _('Name')?></th>
			</tr>
		</thead>
	</table>
<?php } else { ?>
	<div class="bootnav">
			<div class="list-group">
				<a href="?display=users" class="list-group-item"><i class="fa fa-list"></i> <?php echo _("List Users")?></a>
				<a href="?display=users&amp;view=add" class="list-group-item"><?php echo _("Add User")?></a>
			</div>
	</div>
<?php } ?>
<?php break;?>
<?php case "devices":?>
<?php if($show) { ?>
	<div class="text-center">
		<a href="?display=devices<?php echo isset($popover)?$popover:''?>" class="btn btn-sm"><i class="fa fa-list"></i> <?php echo _('List Devices')?></a>
		<?php
			foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {
				?><a href="?display=devices&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo isset($popover)?$popover:''?>" class="btn btn-sm"><?php echo sprintf(_("Add New %s Device"), $driver['shortName'])?></a><?php
			}
		?>
	</div>
	<table data-url="ajax.php?module=core&amp;command=getDeviceGrid&amp;type=all" data-cache="false" data-toggle="table" data-search="true" class="table" id="table-all-devices-side">
		<thead>
			<tr>
				<th data-sortable="true" data-field="id"><?php echo _('Device')?></th>
				<th data-sortable="true" data-field="description"><?php echo _('Description')?></th>
			</tr>
		</thead>
	</table>
<?php } else { ?>
	<div class="bootnav">
			<div class="list-group">
				<a href="?display=devices<?php echo isset($popover)?$popover:''?>" class="list-group-item"><i class="fa fa-list"></i> <?php echo _('List Devices')?></a>
				<?php
					foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {
						?><a href="?display=devices&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo isset($popover)?$popover:''?>" class="list-group-item"><?php echo sprintf(_("Add New %s Device"), $driver['shortName'])?></a><?php
					}
				?>
			</div>
	</div>
<?php } ?>
<?php break;?>
<?php } ?>
