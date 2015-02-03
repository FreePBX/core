<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	$popover = isset($_REQUEST['fw_popover']) ? "&amp;fw_popover=".$_REQUEST['fw_popover'] : '';
?>
<div class="container-fluid">
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
				$sipdriver = FreePBX::create()->Config->get_conf_setting('ASTSIPDRIVER');
				?>
				<div class="row">
					<div class="col-sm-12">
						<h2>Add an Extension</h2>
						Please select the type you want to create below
						<br>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 types">
						<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
							<a data-id="<?php echo $driver['hardware']?>" href="?display=extensions&amp;tech_hardware=<?php echo $driver['hardware']?><?php echo $popover?>" class="btn device"><?php echo $driver['prettyName']?></a>
							<?php if(!empty($driver['description'])) { ?>
								<i data-id="<?php echo $driver['hardware']?>" class="fa fa-question-circle"></i>
								<br/>
								<span id="<?php echo $driver['hardware']?>-help" class="help-block  selection"><?php echo $driver['description']?></span>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
			<?php
			} else {
				echo $currentcomponent->generateconfigpage(__DIR__."/views/extensions.php");
			} ?>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<div class="list-group">
				<?php
					$extens = core_users_list();
					$description = _("Extension");
					$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
					?><a href="?display=extensions" class="list-group-item <?php echo empty($extdisplay) ? "active" : ""?>"><?php echo _("Add Extension")?></a><?php
					foreach($extens as $ext) {
						$active = (trim($extdisplay) == trim($ext[0])) ? 'active' : '';
						?><a href="?display=extensions&amp;extdisplay=<?php echo $ext[0]?>" class="list-group-item <?php echo $active?>"><?php echo $ext[1]?> &lt;<?php echo $ext[0]?>&gt;</a><?php
					}
				?>
			</div>
		</div>
	</div>
</div>
