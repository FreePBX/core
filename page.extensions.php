<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	$popover = isset($_REQUEST['fw_popover']) ? "&amp;fw_popover=".$_REQUEST['fw_popover'] : '';
?>
<div class="fpbx-container container-fluid">
	<div class="row">
		<div class="col-sm-9 <?php echo (isset($_REQUEST['fw_popover']) && empty($_REQUEST['tech_hardware']) && empty($_REQUEST['extdisplay'])) ? "hidden":""?>">
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
							<div id="toolbar-all">
								<button id="remove-all" class="btn btn-danger btn-remove" data-type="extensions" disabled data-section="all">
									<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
								</button>
							</div>
							<table data-toolbar="#toolbar-all" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-all">
								<thead>
									<tr>
										<th data-checkbox="true"></th>
										<th data-sortable="true" data-field="extension"><?php echo _('Extension')?></th>
										<th data-sortable="true"><?php echo _('Name')?></th>
										<th data-sortable="true"><?php echo _('Type')?></th>
										<th><?php echo _('Actions')?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach(FreePBX::Core()->getAllUsersByDeviceType() as $user) {?>
										<tr id="<?php echo $user['extension']?>">
											<td></td>
											<td><?php echo $user['extension']?></td>
											<td><?php echo $user['name']?></td>
											<td><?php echo $user['tech']?></td>
											<td class="actions">
												<a href="?display=extensions&amp;extdisplay=<?php echo $user['extension']?>"><i class="fa fa-pencil-square-o"></i></a>
												<i class="fa fa-times" data-id="<?php echo $user['extension']?>" data-section="all"></i>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
							<div role="tabpanel" id="<?php echo $driver['hardware']?>" class="tab-pane">
								<div id="toolbar-<?php echo $driver['rawName']?>">
									<button id="remove-<?php echo $driver['rawName']?>" class="btn btn-danger btn-remove" data-type="extensions" data-section="<?php echo $driver['rawName']?>" disabled>
										<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
									</button>
								</div>
								<table data-toolbar="#toolbar-<?php echo $driver['rawName']?>" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-<?php echo $driver['rawName']?>">
									<thead>
										<tr>
											<th data-checkbox="true"></th>
											<th data-sortable="true" data-field="extension"><?php echo _('Extension')?></th>
											<th data-sortable="true"><?php echo _('Name')?></th>
											<th><?php echo _('Actions')?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach(FreePBX::Core()->getAllUsersByDeviceType($driver['rawName']) as $user) {?>
											<tr id="<?php echo $user['extension']?>">
												<td></td>
												<td><?php echo $user['extension']?></td>
												<td><?php echo $user['name']?></td>
												<td class="actions">
													<a href="?display=extensions&amp;extdisplay=<?php echo $user['extension']?>"><i class="fa fa-pencil-square-o"></i></a>
													<i class="fa fa-times" data-id="<?php echo $user['extension']?>" data-section="<?php echo $driver['rawName']?>"></i>
												</td>
											</tr>
										<?php } ?>
									</tbody>
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
		<div class="col-sm-3 hidden-xs bootnav <?php echo (isset($_REQUEST['fw_popover']) && (!empty($_REQUEST['tech_hardware']) || !empty($_REQUEST['extdisplay']))) ? "hidden":""?>">
			<div class="list-group">
				<?php if (!isset($_REQUEST['fw_popover'])) { ?>
					<a href="?display=extensions<?php echo $popover?>" class="list-group-item"><i class="fa fa-list"></i> <?php echo _('List Extensions')?></a>
				<?php } ?>
				<?php
					foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {
						?><a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" class="list-group-item"><?php echo sprintf(_("Add New %s Extension"), $driver['shortName'])?></a><?php
					}
				?>
			</div>
		</div>
	</div>
</div>
