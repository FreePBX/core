<!--NAME-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="time_group_id"><?php echo _("Time Group") ?></label>
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
