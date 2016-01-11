<?php
$editmode = (isset($channel) && !empty($channel))?'readonly':'';
?>
<form name="editDAHDIchandid" autocomplete = "off" class="fpbx-submit" action="" method="post" data-fpbx-delete="config.php?display=dahdichandids&action=delete&channel=<?php echo $channel ?>">
<input type = "hidden" name = "action" value="<?php echo $action?>">
<!--Channel-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="channel"><?php echo _("Channel") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="channel"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" id="channel" name="channel" value="<?php  echo $channel; ?>" <?php echo $editmode?>>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="channel-help" class="help-block fpbx-help-block"><?php echo _("The DAHDI Channel number to map to a DID")?></span>
		</div>
	</div>
</div>
<!--END Channel-->
<!--Description-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="description"><?php echo _("Description")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" id="description" name="description" value="<?php  echo $description; ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="description-help" class="help-block fpbx-help-block"><?php echo _("A useful description describing this channel")?></span>
		</div>
	</div>
</div>
<!--END Description-->
<!--DID-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="did"><?php echo _("DID")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="did"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" id="did" name="did" value="<?php  echo $did; ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="did-help" class="help-block fpbx-help-block"><?php echo _("The DID that this channel represents. The incoming call on this channel will be treated as if it came in with this DID and can be managed with Inbound Routing on DIDs")?></span>
		</div>
	</div>
</div>
<!--END DID-->
</form>
