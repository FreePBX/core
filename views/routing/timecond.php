<?php
$iscal = FreePBX::Modules()->checkStatus('calendar');
$iscal = false; //commented out this is a nightmare: https://github.com/asterisk/asterisk/blob/13.9/configs/samples/extensions.conf.sample#L202
$groups = $iscal ? FreePBX::Calendar()->listGroups() : array();
$calendars = $iscal ? FreePBX::Calendar()->listCalendars() : array();
?>
<!--
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="mode"><?php echo _("Time Match Mode") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mode"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" name="time_mode" id="mode_disabled" value="" <?php echo ($time_mode == ""?"CHECKED":"") ?>>
							<label for="mode_disabled"><?php echo _("Disabled");?></label>
							<input type="radio" name="time_mode" id="mode_legacy" value="time-group" <?php echo ($time_mode == "time-group"?"CHECKED":"") ?>>
							<label for="mode_legacy"><?php echo _("Time Group Mode");?></label>
							<input type="radio" name="time_mode" id="mode_calendar" class="<?php echo !$iscal ? 'hidden' : ''?>" value="calendar-group" <?php echo ($time_mode == "calendar-group"?"CHECKED":"") ?>>
							<label for="mode_calendar" class="<?php echo !$iscal ? 'hidden' : ''?>"><?php echo _("Calendar Mode");?></label>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mode-help" class="help-block fpbx-help-block"><?php echo _("If this route should only be available during certain times then Select a Time Group created under Time Groups. The route will be ignored outside of times specified in that Time Group. If left as default of Permanent Route then it will always be available.")?></span>
		</div>
	</div>
</div>
-->
<!--Timezone-->
<div class="element-container timezone-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="timezone"><?php echo _("Time Match Time Zone:")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="timezone"></i>
					</div>
					<div class="col-md-9">
						<select id="timezone" class="chosenselect form-control" name="timezone" id="timezone">
							<option value="default" <?php echo (isset($timezone) && $timezone == $tz ? 'selected' : ''); ?>><?php echo _("Use System Timezone")?>
							<?php foreach(DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $tz) {?>
								<option value="<?php echo $tz?>" <?php echo (isset($timezone) && $timezone == $tz ? 'selected' : ''); ?>><?php echo $tz?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="timezone-help" class="help-block fpbx-help-block"><?php echo _("Specify the time zone by name if the destinations are in a different time zone than the server. Type two characters to start an auto-complete pick-list. <br/><strong>Important</strong>: Your selection here <strong>MUST</strong> appear in the pick-list or in the /usr/share/zoneinfo/ directory.") ?></span>
		</div>
	</div>
</div>
<!--END Timezone-->
<!--
<div class="element-container calendar-container <?php echo (!$iscal || $time_mode != "calendar-group") ? 'hidden' : ''?>">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="calendar_id"><?php echo _("Time Match Calendar") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="calendar_id"></i>
					</div>
					<div class="col-md-9">
						<select class="form-control" id="calendar_id" name="calendar_id">
							<option value=""><?php echo _("--Select a Calendar--")?></option>
							<?php foreach($calendars as $id=> $group) { ?>
								<option value="<?php echo $id?>" <?php echo ($calendar_id == $id) ? "selected" : ""?>><?php echo $group['name']?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="calendar_id-help" class="help-block fpbx-help-block"><?php echo sprintf(_("If set the hint will be INUSE if the time condition is matched, and NOT_INUSE if it fails"),$tcval)?></span>
		</div>
	</div>
</div>
<div class="element-container calendar-container <?php echo (!$iscal || $time_mode != "calendar-group") ? 'hidden' : ''?>">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="calendar_group_id"><?php echo _("Time Match Calendar Group") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="calendar_group_id"></i>
					</div>
					<div class="col-md-9">
						<select class="form-control" id="calendar_group_id" name="calendar_group_id">
							<option value=""><?php echo _("--Select a Group--")?></option>
							<?php foreach($groups as $id=> $group) { ?>
								<option value="<?php echo $id?>" <?php echo ($calendar_group_id == $id) ? "selected" : ""?>><?php echo $group['name']?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="calendar_group_id-help" class="help-block fpbx-help-block"><?php echo sprintf(_("If set the hint will be INUSE if the time condition is matched, and NOT_INUSE if it fails"),$tcval)?></span>
		</div>
	</div>
</div>
-->
<!--NAME-->
<div class="element-container time-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="time_group_id"><?php echo _("Time Match Time Group") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="time_group_id"></i>
					</div>
					<div class="col-md-9">
						<?php echo timeconditions_timegroups_drawgroupselect('time_group_id', (isset($time_group_id) ? $time_group_id : ''), true, '', _('---Permanent Route---')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="time_group_id-help" class="help-block fpbx-help-block"><?php echo _("If this route should only be available during certain times then Select a Time Group created under Time Groups. The route will be ignored outside of times specified in that Time Group. If left as default of Permanent Route then it will always be available.")?></span>
		</div>
	</div>
</div>
<!--END NAME-->
<script>
	$("input[name=time_mode]").change(function() {
		if($(this).val() == "time-group") {
			$(".calendar-container").addClass("hidden");
			$(".time-container").removeClass("hidden");
			$(".timezone-container").removeClass("hidden");
		} else if($(this).val() == "calendar-group") {
			$(".calendar-container").removeClass("hidden");
			$(".time-container").addClass("hidden");
			$(".timezone-container").removeClass("hidden");
		} else {
			$(".calendar-container").addClass("hidden");
			$(".time-container").addClass("hidden");
			$(".timezone-container").addClass("hidden");
		}
	});

	$("#calendar_id, #calendar_group_id").change(function() {
		if($("#calendar_id").val() !== "" && $("#calendar_group_id").val() !== "") {
			$(this).val("");
			warnInvalid($(this),_("You cant set both a group and a calendar"));
		}
	});
</script>
