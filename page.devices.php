<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<div class="fpbx-container container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<?php
			// If this is a popOver, we need to set it so the selection of device type does not result
			// in the popover closing because config.php thinks it was the process function. Maybe
			// the better way to do this would be to log an error or put some proper mechanism in place
			// since this is a bit of a kludge
			//
			if (!empty($_REQUEST['fw_popover'])) {
			?>
				<script>
					$(document).ready(function(){
						$('[name="fw_popover_process"]').val('');
						$('<input>').attr({type: 'hidden', name: 'fw_popover'}).val('1').appendTo('.popover-form');
					});
				</script>
			<?php
			}

			$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;
			$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
			$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

			global $currentcomponent;
			if(empty($_REQUEST['tech_hardware']) && empty($_REQUEST['extdisplay'])) {
				?>
				<div class="display no-border">
					<ul class="nav nav-tabs" role="tablist">
						<li role="presentation" data-name="alldids" class="active">
							<a href="#alldids" aria-controls="alldids" role="tab" data-toggle="tab">
								<?php echo _("All Devices")?>
							</a>
						</li>
						<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
							<li role="presentation" data-name="<?php echo $driver['hardware']?>" class="">
								<a href="#<?php echo $driver['hardware']?>" aria-controls="<?php echo $driver['hardware']?>" role="tab" data-toggle="tab">
									<?php echo sprintf(_("%s Devices"),$driver['shortName'])?>
								</a>
							</li>
						<?php } ?>
					</ul>
					<div class="tab-content display">
						<div role="tabpanel" id="alldids" class="tab-pane active">
							<table class="table table-striped">
								<tr><th><?php echo _('Device')?></th><th><?php echo _('Name')?></th><th><?php echo _('Type')?></th></tr>
								<?php foreach(FreePBX::Core()->getAllDevicesByType() as $user) {?>
									<tr><td><a href="?display=devices&amp;extdisplay=<?php echo $user['id']?>"><?php echo $user['id']?></a></td><td><?php echo $user['description']?></td><td><?php echo $user['tech']?></td></tr>
								<?php } ?>
							</table>
						</div>
						<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
							<div role="tabpanel" id="<?php echo $driver['hardware']?>" class="tab-pane">
								<table class="table table-striped">
									<tr><th><?php echo _('Device')?></th><th><?php echo _('Name')?></th></tr>
									<?php foreach(FreePBX::Core()->getAllDevicesByType($driver['rawName']) as $user) {?>
										<tr><td><a href="?display=devices&amp;extdisplay=<?php echo $user['id']?>"><?php echo $user['id']?></a></td><td><?php echo $user['description']?></td></tr>
									<?php } ?>
								</table>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php
			} else {
				echo $currentcomponent->generateconfigpage(__DIR__."/views/devices.php");
			} ?>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<div class="list-group">
				<a href="?display=devices<?php echo $popover?>" class="list-group-item"><i class="fa fa-list"></i> <?php echo _('List Devices')?></a>
				<?php
					foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {
						?><a href="?display=devices&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" class="list-group-item"><?php echo sprintf(_("Add New %s Device"), $driver['shortName'])?></a><?php
					}
				?>
			</div>
		</div>
	</div>
</div>
