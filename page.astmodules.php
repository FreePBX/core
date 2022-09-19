<?php
$mods = FreePBX::create()->ModulesConf();
$pc = $mods->ProcessedConfig['modules'];
$loadrows =
$noloadrows = '';
$preloadrows = '';
if (isset($pc['noload'])) {
	$noloads = is_array($pc['noload']) ? $pc['noload'] : array($pc['noload']);
	foreach($noloads as $mod){
		$noloadrows .= <<<HERE
<tr id = "row$mod">
<td>$mod</td>
<td>
	<a href="#" id="del$mod" data-mod="$mod">
	<i class="fa fa-trash-o"></i></a></td>
</tr>
HERE;
	}
}
if (isset($pc['load'])) {
	$loads = is_array($pc['load']) ? $pc['load'] : array($pc['load']);
	foreach($loads as $mod){
		$loadrows .= <<<HERE
<tr id = "row$mod">
<td>$mod</td>
<td>
	<a href="#" id="del$mod" data-mod="$mod">
	<i class="fa fa-trash-o"></i></a></td>
</tr>
HERE;
	}
}
if (isset($pc['preload'])) {
	$preloads = is_array($pc['preload']) ? $pc['preload'] : array($pc['preload']);
	foreach($preloads as $mod){
		$preloadrows .= <<<HERE
<tr id = "row$mod">
<td>$mod</td>
<td>
	<a href="#" id="del$mod" data-mod="$mod">
	<i class="fa fa-trash-o"></i></a></td>
</tr>
HERE;
	}
}
?>

<div class="container-fluid">
	<h1><?php echo _("Asterisk Modules")?></h1>
	<div class="well well-info">
		<?php echo _("Note that this is for ASTERISK modules, not FreePBX Modules.")?>
		<br/>
		<?php echo _("It is unlikely you'll need to change anything here.")?>
		<br/>
		<?php echo _("Please be careful when adding or removing modules, as it is possible to stop Asterisk from starting with an incorrect configuration.")?>
		<br/>
		<?php echo _("Deleting the modules.conf file will reset this to defaults.")?>
	</div>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-9">
					<div class="fpbx-container">
						<form class="fpbx-submit" name="frm_extensions" action="" method="post" data-fpbx-delete="" role="form">
							<ul class="nav nav-tabs" role="tablist">
								<li role="presentation" data-name="amodnoload" class="active">
									<a href="#amodnoload" aria-controls="amodnoload" role="tab" data-toggle="tab">
										<?php echo _("Excluded Modules")?>
									</a>
								</li>
								<li role="presentation" data-name="amodload" class="change-tab">
									<a href="#amodload" aria-controls="amodload" role="tab" data-toggle="tab">
										<?php echo _("Manually Loaded Modules")?>
									</a>
								</li>
								<li role="presentation" data-name="amodpreload" class="change-tab">
									<a href="#amodpreload" aria-controls="amodpreload" role="tab" data-toggle="tab">
										<?php echo _("Preloaded Modules")?>
									</a>
								</li>
							</ul>
								<div class="tab-content display">
									<div role="tabpanel" id="amodnoload" class="tab-pane active">
										<table id="modnoload"
										 data-toggle="table"
										 data-pagination="true"
										 data-search="true"
										 class="table table-striped">
												<thead>
													<tr>
														<th data-sortable="true"><?php echo _("Module") ?></th>
														<th class = "col-sm-2"><?php echo _("Action") ?></th>
													</tr>
												</thead>
												<tbody>
													<?php echo $noloadrows ?>
												</tbody>
											</table>
									</div>
									<div role="tabpanel" id="amodload" class="tab-pane">
												<table id="modload"
												 data-toggle="table"
												 data-pagination="true"
												 data-search="true"
												 class="table table-striped">
												<thead>
													<tr>
														<th data-sortable="true"><?php echo _("Module") ?></th>
														<th class = "col-sm-2"><?php echo _("Action") ?></th>
													</tr>
												</thead>
												<tbody>
													<?php echo $loadrows ?>
												</tbody>
											</table>
									</div>
									<div role="tabpanel" id="amodpreload" class="tab-pane">
												<table id="modpreload"
												 data-toggle="table"
												 data-pagination="true"
												 data-search="true"
												 class="table table-striped">
												<thead>
													<tr>
														<th data-sortable="true"><?php echo _("Module") ?></th>
														<th class = "col-sm-2"><?php echo _("Action") ?></th>
													</tr>
												</thead>
												<tbody>
													<?php echo $preloadrows ?>
												</tbody>
											</table>
									</div>
								</div>
						</form>
					</div>
				</div>
				<div class="col-sm-3 hidden-xs bootnav" id='addform'>
					<label class="control-label" for="module"><b><?php echo _("Add Module") ?></b></label>
					<input type="text" class="form-control" id="module" name="module" value="">
					<button class="btn btn-default" id="addmodule"><i class="fa fa-plus"></i> <?php echo _("Add")?></button>
				</div>
			</div>
		</div>
	</div>
</div>
