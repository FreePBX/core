<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	$popover = isset($_REQUEST['fw_popover']) ? "&amp;fw_popover=".$_REQUEST['fw_popover'] : '';
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
				<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#quickCreate"><?php echo _("Quick Create Extension");?></button>
				<div class="display no-border">
					<ul class="nav nav-tabs" role="tablist">
						<li role="presentation" data-name="alldids" class="active">
							<a href="#alldids" aria-controls="alldids" role="tab" data-toggle="tab">
								<?php echo _("All Extensions")?>
							</a>
						</li>
						<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
							<li role="presentation" data-name="<?php echo $driver['hardware']?>" class="">
								<a href="#<?php echo $driver['hardware']?>" aria-controls="<?php echo $driver['hardware']?>" role="tab" data-toggle="tab">
									<?php echo sprintf(_("%s Extensions"),$driver['shortName'])?>
								</a>
							</li>
						<?php } ?>
					</ul>
					<div class="tab-content display">
						<div role="tabpanel" id="alldids" class="tab-pane active">
							<table class="table table-striped">
								<tr><th>Extension</th><th>Name</th><th>Type</th></tr>
								<?php foreach(FreePBX::Core()->getAllUsersByDeviceType() as $user) {?>
									<tr><td><a href="?display=extensions&amp;extdisplay=<?php echo $user['extension']?>"><?php echo $user['extension']?></a></td><td><?php echo $user['name']?></td><td><?php echo $user['tech']?></td></tr>
								<?php } ?>
							</table>
						</div>
						<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
							<div role="tabpanel" id="<?php echo $driver['hardware']?>" class="tab-pane">
								<table class="table table-striped">
									<tr><th>Extension</th><th>Name</th></tr>
									<?php foreach(FreePBX::Core()->getAllUsersByDeviceType($driver['rawName']) as $user) {?>
										<tr><td><a href="?display=extensions&amp;extdisplay=<?php echo $user['extension']?>"><?php echo $user['extension']?></a></td><td><?php echo $user['name']?></td></tr>
									<?php } ?>
								</table>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="modal fade paged" id="quickCreate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-pages="<?php echo count(FreePBX::Core()->getQuickCreateDisplay())?>" data-currentpage="1">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title" id="quickCreateLabel"><?php echo _("Quick Create Extension")?></h4>
							</div>
							<div class="modal-body">
								<form>
									<?php foreach(FreePBX::Core()->getQuickCreateDisplay() as $page => $data) {?>
										<div class="page <?php echo ($page > 0) ? 'hidden' : ''?>" data-num="<?php echo $page + 1?>">
											<?php foreach($data as $pageDisplay) { ?>
												<?php echo $pageDisplay['html']?>
											<?php } ?>
										</div>
									<?php } ?>
								</form>
							</div>
							<script>
								function validateQC() {
									<?php foreach(FreePBX::Core()->getQuickCreateDisplay() as $page => $data) {?>
										<?php foreach($data as $pageDisplay) { ?>
											<?php echo !empty($pageDisplay['validate']) ? $pageDisplay['validate'] : ''?>
										<?php } ?>
									<?php } ?>
									return true;
								}
							</script>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _('Close')?></button>
								<button type="button" class="btn btn-primary back hidden"><?php echo _('Back')?></button>
								<button type="button" class="btn btn-primary next"><?php echo _('Next')?></button>
								<button type="button" class="btn btn-primary create hidden"><?php echo _('Create')?></button>
							</div>
						</div>
					</div>
				</div>
			<?php
			} else {
				echo $currentcomponent->generateconfigpage(__DIR__."/views/extensions.php");
			} ?>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<div class="list-group">
				<a href="?display=extensions<?php echo $popover?>" class="list-group-item"><i class="fa fa-list"></i> <?php echo _('List Extensions')?></a>
				<?php
					foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {
						?><a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" class="list-group-item"><?php echo sprintf(_("Add New %s Extension"), $driver['shortName'])?></a><?php
					}
				?>
			</div>
		</div>
	</div>
</div>
