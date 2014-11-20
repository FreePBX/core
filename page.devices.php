<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
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
						<h2>Add an Device</h2>
						Please select the type you want to create below
						<br>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 types">
							<?php if($sipdriver == "both" || $sipdriver == "chan_pjsip") {?>
								<a data-id="pjsip_generic" href="?display=devices&amp;tech_hardware=pjsip_generic" class="btn device"><?php echo _("Generic PJSIP Device")?></a><i data-id="pjsip_generic" class="fa fa-question-circle"></i><br/>
								<span id="pjsip_generic-help" class="help-block  selection"><?php echo _("<strong>Generic PJSIP Device</strong>: A new SIP channel driver for Asterisk, chan_pjsip is built on the PJSIP SIP stack. A collection of resource modules provides the bulk of the SIP functionality");?></span>
							<?php } ?>
							<?php if($sipdriver == "both" || $sipdriver == "chan_sip") {?>
								<a data-id="sip_generic" href="?display=devices&amp;tech_hardware=sip_generic" class="btn device"><?php echo _("Generic CHAN SIP Device")?></a><i data-id="sip_generic" class="fa fa-question-circle"></i><br/>
								<span id="sip_generic-help" class="help-block  selection"><?php echo _("<strong>Generic CHAN SIP Device</strong>: The legacy SIP channel driver in Asterisk");?></span>
							<?php } ?>
							<a data-id="iax2_generic" href="?display=devices&amp;tech_hardware=iax2_generic" class="btn device"><?php echo _("Generic IAX2 Device")?></a><i data-id="iax2_generic" class="fa fa-question-circle"></i><br/>
							<span id="iax2_generic-help" class="help-block  selection"><?php echo _("<strong>Generic IAX2 Device</strong>: Inter-Asterisk eXchange (IAX) is a communications protocol native to the Asterisk private branch exchange (PBX) software, and is supported by a few other softswitches, PBX systems, and softphones. It is used for transporting VoIP telephony sessions between servers and to terminal devices");?></span>
							<a data-id="dahdi_generic" href="?display=devices&amp;tech_hardware=dahdi_generic" class="btn device"><?php echo _("Generic DAHDi Device")?></a><i data-id="dahdi_generic" class="fa fa-question-circle"></i><br/>
							<span id="dahdi_generic-help" class="help-block  selection"><?php echo _("<strong>Generic DAHDi Device</strong>: Short for 'Digium Asterisk Hardware Device Interface'");?></span>
							<a data-id="custom_custom" href="?display=devices&amp;tech_hardware=custom_custom" class="btn device"><?php echo _("Other (Custom) Device")?></a><i data-id="custom_custom" class="fa fa-question-circle"></i><br/>
							<span id="custom_custom-help" class="help-block  selection"><?php echo _("<strong>Other (Custom) Device</strong>");?></span>
							<a data-id="virtual" href="?display=devices&amp;tech_hardware=virtual" class="btn device"><?php echo _("None (virtual exten)")?></a><i data-id="virtual" class="fa fa-question-circle"></i><br/>
							<span id="virtual-help" class="help-block selection"><?php echo _("<strong>None (virtual exten)</strong>");?></span>
					</div>
				</div>
			<?php
			} else {
				echo $currentcomponent->generateconfigpage(__DIR__."/views/devices.php");
			} ?>
		</div>
		<div class="bootnav">
			<div class="col-sm-3 hidden-xs">
				<div class="list-group">
					<?php
						$extens = core_devices_list();
						$description = _("Device");
						$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
						?><a href="?display=devices" class="list-group-item <?php echo empty($extdisplay) ? "active" : ""?>"><?php echo _("Add Device")?></a><?php
						foreach($extens as $ext) {
							$active = (trim($extdisplay) == trim($ext[0])) ? 'active' : '';
							?><a href="?display=devices&amp;extdisplay=<?php echo $ext[0]?>" class="list-group-item <?php echo $active?>"><?php echo $ext[1]?> &lt;<?php echo $ext[0]?>&gt;</a><?php
						}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
