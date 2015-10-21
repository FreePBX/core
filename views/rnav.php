<?php switch($display) {?>
<?php case "extensions":?>
	<?php if($show) { ?>
		<div id="toolbar-all">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				<i class="fa fa-plus">&nbsp;</i><?php echo _("Add Extension")?> <span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) { ?>
					<li><a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" ><i class="fa fa-plus"></i> <strong><?php echo sprintf(_('Add New %s Extension'),$driver['shortName'])?></strong></a></li>
				<?php } ?>
			</ul>
			<a href="?display=extensions" class="btn"><i class="fa fa-list"></i> <?php echo _("List All Extensions")?></a>
		</div>
		<table data-url="ajax.php?module=core&amp;command=getExtensionGrid&amp;type=all" data-toolbar="#toolbar-all" data-cache="false" data-toggle="table" data-search="true" class="table" id="table-all-side">
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
	<div id="toolbar-all">
		<a href="?display=users" class="btn"><i class="fa fa-list"></i> <?php echo _("List Users")?></a>
		<a href="?display=users&amp;view=add" class="btn"><?php echo _("Add User")?></a>
	</div>
	<table data-url="ajax.php?module=core&amp;command=getUserGrid&amp;type=all" data-toolbar="#toolbar-all" data-cache="false" data-toggle="table" data-search="true" class="table " id="table-all-users-side">
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
	<div id="toolbar-all">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			<i class="fa fa-plus">&nbsp;</i><?php echo _("Add Device")?> <span class="caret"></span>
		</button>
		<a href="?display=devices<?php echo isset($popover)?$popover:''?>" class="btn"><i class="fa fa-list"></i> <?php echo _('List Devices')?></a>
		<ul class="dropdown-menu" role="menu">
			<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) { ?>
				<li><a href="?display=devices&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo isset($popover)?$popover:''?>" ><i class="fa fa-plus"></i> <strong><?php echo sprintf(_("Add New %s Device"), $driver['shortName'])?></strong></a></li>
			<?php } ?>
		</ul>
	</div>
	<table data-url="ajax.php?module=core&amp;command=getDeviceGrid&amp;type=all" data-toolbar="#toolbar-all" data-cache="false" data-toggle="table" data-search="true" class="table" id="table-all-devices-side">
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
