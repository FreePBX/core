<!--DUNDI-->
<input type="hidden" name="usercontext" value="notneeded"/>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="channelid"><?php echo _("DUNDi Mapping")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="channelid"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" id="channelid" name="channelid" value="<?php echo $channelid ?>" tabindex="<?php echo ++$tabindex;?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="channelid-help" class="help-block fpbx-help-block"><?php echo _("This is the name of the DUNDi mapping as defined in the [mappings] section of remote dundi.conf peers. This corresponds to the 'include' section of the peer details in the local dundi.conf file. This requires manual configuration of DUNDi to use this trunk.")?></span>
		</div>
	</div>
</div>
<!--DUNDI-->
