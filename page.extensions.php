<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	$popover = isset($_REQUEST['fw_popover']) ? "&amp;fw_popover=".$_REQUEST['fw_popover'] : '';
?>
<div class="fpbx-container container-fluid">
	<?php if((isset($_REQUEST['fw_popover']) && empty($_REQUEST['tech_hardware']) && empty($_REQUEST['extdisplay']))) { ?>
		<div class="row">
			<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) { ?>
				<a class="btn btn-default" href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" ><i class="fa fa-plus"></i> <strong><?php echo sprintf(_('Add New %s Extension'),$driver['shortName'])?></strong></a></br>
			<?php } ?>
		</div>
	<?php } else { ?>
		<div class="row">
			<div class="col-sm-12">
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
							$('[name="fw_popover_process"]').val('true');
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
						<div class="nav-container">
							<div class="scroller scroller-left"><i class="glyphicon glyphicon-chevron-left"></i></div>
							<div class="scroller scroller-right"><i class="glyphicon glyphicon-chevron-right"></i></div>
							<div class="wrapper">
								<ul class="nav nav-tabs list" role="tablist">
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
							</div>
						</div>
						<div class="tab-content display">
							<div role="tabpanel" id="alldids" class="tab-pane active">
								<div id="toolbar-all">
									<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
										<i class="fa fa-plus">&nbsp;</i><?php echo _("Add Extension")?> <span class="caret"></span>
									</button>
									<ul class="dropdown-menu" role="menu">
										<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) { ?>
											<li><a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" ><i class="fa fa-plus"></i> <strong><?php echo sprintf(_('Add New %s Extension'),$driver['shortName'])?></strong></a></li>
										<?php } ?>
									</ul>
									<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#quickCreate"><i class="fa fa-bolt"></i> <?php echo _("Quick Create Extension");?></button>
									<button id="remove-all" class="btn btn-danger btn-remove" data-type="extensions" disabled data-section="all">
										<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
									</button>
								</div>
								<table data-state-save="true" data-state-save-id-table="extensions-all" data-url="ajax.php?module=core&amp;command=getExtensionGrid&amp;type=all" data-cache="false" data-show-refresh="true" data-toolbar="#toolbar-all" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped ext-list" id="table-all">
									<thead>
										<tr>
											<th data-checkbox="true"></th>
											<th data-sortable="true" data-field="extension"><?php echo _('Extension')?></th>
											<th data-sortable="true" data-field="name"><?php echo _('Name')?></th>
											<th data-formatter="CWIconFormatter"><?php echo _('CW')?></th>
											<th data-formatter="DNDIconFormatter"><?php echo _('DND')?></th>
											<th data-formatter="FMFMIconFormatter"><?php echo _('FM/FM')?></th>
											<th data-formatter="CFIconFormatter"><?php echo _('CF')?></th>
											<th data-formatter="CFBIconFormatter"><?php echo _('CFB')?></th>
											<th data-formatter="CFUIconFormatter"><?php echo _('CFU')?></th>
											<th data-sortable="true" data-field="tech"><?php echo _('Type')?></th>
											<th data-field="actions"><?php echo _('Actions')?></th>
										</tr>
									</thead>
								</table>
							</div>
							<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
								<div role="tabpanel" id="<?php echo $driver['hardware']?>" class="tab-pane">
									<div id="toolbar-<?php echo $driver['rawName']?>">
										<a href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo sprintf(_('Add New %s Extension'),$driver['shortName'])?></a>
										<button id="remove-<?php echo $driver['rawName']?>" class="btn btn-danger btn-remove" data-type="extensions" data-section="<?php echo $driver['rawName']?>" disabled>
											<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
										</button>
									</div>
									<table data-state-save="true" data-state-save-id-table="extensions-<?php echo $driver['rawName']?>" data-url="ajax.php?module=core&amp;command=getExtensionGrid&amp;type=<?php echo $driver['rawName']?>" data-cache="false" data-show-refresh="true" data-toolbar="#toolbar-<?php echo $driver['rawName']?>" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped ext-list" id="table-<?php echo $driver['rawName']?>">
										<thead>
											<tr>
												<th data-checkbox="true"></th>
												<th data-sortable="true" data-field="extension"><?php echo _('Extension')?></th>
												<th data-sortable="true" data-field="name"><?php echo _('Name')?></th>
												<th data-formatter="CWIconFormatter"><?php echo _('CW')?></th>
												<th data-formatter="DNDIconFormatter"><?php echo _('DND')?></th>
												<th data-formatter="FMFMIconFormatter"><?php echo _('FMFM')?></th>
												<th data-formatter="CFIconFormatter"><?php echo _('CF')?></th>
												<th data-formatter="CFBIconFormatter"><?php echo _('CFB')?></th>
												<th data-formatter="CFUIconFormatter"><?php echo _('CFU')?></th>
												<th data-field="actions"><?php echo _('Actions')?></th>
											</tr>
										</thead>
									</table>
								</div>
							<?php } ?>
						</div>
					</div>

					<div class="modal fade paged" id="quickCreate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-pages="<?php echo count(FreePBX::Core()->getQuickCreateDisplay())?>" data-currentpage="1">
						<form>
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
										<h4 class="modal-title" id="quickCreateLabel"><?php echo _("Quick Create Extension")?></h4>
									</div>
									<div class="modal-body swMain" id="wizard">
										<ul>
											<?php foreach(FreePBX::Core()->getQuickCreateDisplay() as $page => $data) {?>
												<li>
													<a href="#step-<?php echo $page +1?>">
														<label class="stepNumber"><?php echo $page +1?></label>
														<span class="stepDesc">
															Step <?php echo $page +1?><br />
														</span>
													</a>
												</li>
											<?php } ?>
										</ul>
											<?php foreach(FreePBX::Core()->getQuickCreateDisplay() as $page => $data) {?>
												<div id="step-<?php echo $page +1?>">
														<div class="fpbx-container">
															<h2 class="StepTitle">Step <?php echo $page +1?></h2>
															<div class="display">
																<?php foreach($data as $pageDisplay) { ?>
																	<?php echo $pageDisplay['html']?>
																<?php } ?>
															</div>
														</div>
												</div>
											<?php } ?>
									</div>
								</div>
							</div>
						</form>
					</div>
				<?php
				} else {
					echo $currentcomponent->generateconfigpage(__DIR__."/views/extensions.php");
				} ?>
			</div>
		</div>
	<?php } ?>
</div>

<script>
	$(document).ready(function() {
		$('#wizard').smartWizard({
			keyNavigation: false,
			onLeaveStep: function(obj, context) {
				<?php foreach(FreePBX::Core()->getQuickCreateDisplay() as $page => $data) {?>
					if(context.fromStep == <?php echo $page + 1?>) {
						<?php foreach($data as $pageDisplay) { ?>
							<?php echo !empty($pageDisplay['validate']) ? $pageDisplay['validate'] : ''?>
						<?php } ?>
					}
				<?php } ?>
				return true;
			},
			onFinish: function(obj, context) {
				<?php foreach(FreePBX::Core()->getQuickCreateDisplay() as $page => $data) {?>
					<?php foreach($data as $pageDisplay) { ?>
						<?php echo !empty($pageDisplay['validate']) ? $pageDisplay['validate'] : ''?>
					<?php } ?>
				<?php } ?>
				var data = {};
				$("#quickCreate form input[type=text], #quickCreate form input[type=number], #quickCreate form input[type=email], #quickCreate form input[type=password], #quickCreate form input[type=radio]:checked, #quickCreate form select").each(function() {
					data[$(this).prop('name')] = $(this).val();
				});
				$('#quickCreate .buttonFinish').addClass("buttonDisabled");
				$.post("ajax.php?module=core&command=quickcreate", data, function(d,status){
					if(d.status) {
						extmap[d.ext] = d.name;
						$('#quickCreate').modal('hide');
						toggle_reload_button("show");
						$("#quickCreate form")[0].reset();
						$('#wizard').smartWizard('goToStep',1);
						$('#table-all').bootstrapTable('refresh');
						$('#table-' + data.tech).bootstrapTable('refresh');
					} else {
						$('#wizard').smartWizard('showMessage',d.message);
						$('#quickCreate .buttonFinish').removeClass("buttonDisabled");
					}
				});
			}
		});
	})
	function DNDIconFormatter(value, row) {
		return row.settings.dnd ? '<i class="fa fa-check-square-o" style="color:green" title="<?php echo _("Do Not Disturb is enabled")?>"></i>' : '<i class="fa fa-square-o" title="<?php echo _("Do Not Disturb is disabled")?>"></i>';
	}
	function CWIconFormatter(value, row) {
		return row.settings.cw ? '<i class="fa fa-check-square-o" style="color:green" title="<?php echo _("Call Waiting is enabled")?>"></i>' : '<i class="fa fa-square-o" title="<?php echo _("Call Waiting is disabled")?>"></i>';
	}
	function CFIconFormatter(value, row) {
		return row.settings.cf ? '<i class="fa fa-check-square-o" style="color:green" title="<?php echo _("Call Forwarding is enabled")?>"></i>' : '<i class="fa fa-square-o" title="<?php echo _("Call Forwarding is disabled")?>"></i>';
	}
	function CFBIconFormatter(value, row) {
		return row.settings.cfb ? '<i class="fa fa-check-square-o" style="color:green" title="<?php echo _("Call Forwarding Busy is enabled")?>"></i>' : '<i class="fa fa-square-o" title="<?php echo _("Call Forwarding Busy is disabled")?>"></i>';
	}
	function CFUIconFormatter(value, row) {
		return row.settings.cfu ? '<i class="fa fa-check-square-o" style="color:green" title="<?php echo _("Call Forwarding Unconditional is enabled")?>"></i>' : '<i class="fa fa-square-o" title="<?php echo _("Call Forwarding Unconditional is disabled")?>"></i>';
	}
	function FMFMIconFormatter(value, row) {
		return row.settings.fmfm ? '<i class="fa fa-check-square-o" style="color:green" title="<?php echo _("Find Me/Follow Me is enabled")?>"></i>' : '<i class="fa fa-square-o" title="<?php echo _("Find Me/Follow Me is disabled")?>"></i>';
	}
</script>
