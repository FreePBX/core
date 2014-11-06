<?php /* $Id$ */

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<style>
.rnav {
	/*
		float:right;
		position: relative;
		margin-top:10px;
		right: 1.5%;
		margin-left: 2%;
		*/
	float: none;
	right: 1.5%;
	position: absolute;
	background-color: white;
}
.start {
	width: 60%;
}
.fa.fa-question-circle {
	color: #0070a3;
	cursor: pointer;
	height: 10px;
	width: 10px;
	font-size: small;
	vertical-align: super;
	display: inline-block;
	text-align: center;
	margin: 2px;
	padding-right: 1.5px;
	padding-top: 1px;
	padding-left: 1px;
	font-weight: normal;
}
.help-block {
	display: none;
	background-color: rgba(242, 242, 242, 0.47);
	padding: 5px;
	border-radius: 5px;
}
.btn.device {
	width: 200px;
	margin-bottom: 5px;
}
.list-group {
	max-height: 450px;
	overflow-y: auto;
	border: 1px solid #ddd;
	border-radius: 4px;
	font-size: 95%;
}
.list-group-item:first-child {
	border-top: none;
	border-bottom: 1px solid #ddd;
}
.list-group-item:last-child {
	border-top: 1px solid #ddd;
	border-bottom: none;
}
.list-group-item {
	border: none;
	border-top: 1px solid #ddd;
	border-bottom: 1px solid #ddd;
}
a.list-group-item {
	color: #555;
}
</style>
<div class="container-fluid">
  <div class="row">
		<div class="col-sm-9">
			<?php
			// If this is a popOver, we need to set it so the selection of device type does not result
			// in the popover closing because config.php thinks it was the process function. Maybe
			// the better way to do this would be to log an error or put some proper mechanism in place
			// since this is a bit of a kludge
			//
			if (!empty($_REQUEST['fw_popover']) && empty($_REQUEST['tech_hardware'])) {
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
			if(empty($_REQUEST['extdisplay'])) {
				$sipdriver = FreePBX::create()->Config->get_conf_setting('ASTSIPDRIVER');
				?>
				<h2>Add an Extension</h2>
				Please select your Device below
				<br>
				<div class="container pull-left start">
					<?php if($sipdriver == "both" || $sipdriver == "chan_pjsip") {?>
						<a href="" class="btn device"><?php echo _("Generic PJSIP Device")?></a><br/>
					<?php } ?>
					<?php if($sipdriver == "both" || $sipdriver == "chan_sip") {?>
						<a href="" class="btn device"><?php echo _("Generic CHAN SIP Device")?></a><br/>
					<?php } ?>
					<a href="" class="btn device"><?php echo _("Generic IAX2 Device")?></a><br/>
					<a href="" class="btn device"><?php echo _("Generic DAHDi Device")?></a><br/>
					<a href="" class="btn device"><?php echo _("Other (Custom) Device")?></a><br/>
					<a href="" class="btn device"><?php echo _("None (virtual exten)")?></a><br/>
				</div>
				<span class="help-block">
					<?php echo _("Select the type of device you'd like to create.")?><br/>
					<ul>
						<?php if($sipdriver == "both" || $sipdriver == "chan_pjsip") {?>
							<li><?php echo _("<strong>Generic PJSIP Device</strong>: A new SIP channel driver for Asterisk, chan_pjsip is built on the PJSIP SIP stack. A collection of resource modules provides the bulk of the SIP functionality");?></li>
						<?php } ?>
						<?php if($sipdriver == "both" || $sipdriver == "chan_sip") {?>
							<li><?php echo _("<strong>Generic CHAN SIP Device</strong>: The legacy SIP channel driver in Asterisk");?></li>
						<?php } ?>
						<li><?php echo _("<strong>Generic IAX2 Device</strong>: Inter-Asterisk eXchange (IAX) is a communications protocol native to the Asterisk private branch exchange (PBX) software, and is supported by a few other softswitches, PBX systems, and softphones. It is used for transporting VoIP telephony sessions between servers and to terminal devices");?></li>
						<li><?php echo _("<strong>Generic DAHDi Device</strong>: Short for 'Digium Asterisk Hardware Device Interface'");?></li>
						<li><?php echo _("<strong>Other (Custom) Device</strong>");?></li>
						<li><?php echo _("<strong>None (virtual exten)</strong>");?></li>
					</ul>
				</span>
				<script>
					$(".container .fa.fa-question-circle").hover(function(){
						var el = $(this).parents(".container").find(".help-block");
						el.fadeIn("fast");
					}, function(){
						var el = $(this).parents(".container").find(".help-block");
						var input = $(this).parents(".container").find(".form-control");
						if(input.length && !input.is(":focus")) {
							el.fadeOut("fast");
						}
					})
					$(".container select").focus(function() {
						var el = $(this).parents(".container").find(".help-block");
						el.fadeIn("fast");
					});
					$(".container select").blur(function() {
						var el = $(this).parents(".container").find(".help-block");
						el.fadeOut("fast");
					});
				</script>
			<?php
			} else {
				echo $currentcomponent->generateconfigpage(__DIR__."/views/extensions.php");
			} ?>
		</div>
		<div class="col-sm-3">
			<div class="list-group">
				<a href="?display=extensions" class="list-group-item <?php echo $active?>"><?php echo _("Add Extension")?></a>
				<?php
					$extens = core_users_list();
					$description = _("Extension");
					$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
					foreach($extens as $ext) {
						$active = (trim($extdisplay) == trim($ext[0])) ? 'active' : '';
						?><a href="?display=extensions&amp;extdisplay=<?php echo $ext[0]?>" class="list-group-item <?php echo $active?>"><?php echo $ext[1]?> &lt;<?php echo $ext[0]?>&gt;</a><?php
					}
				?>
			</div>
			<?php
			$extens = core_users_list();
			$description = _("Extension");
			//drawListMenu($extens, null, null, $display, $extdisplay, $description);
			?>
				<br />
			</div>
		</div>
  </div>
</div>
