<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" data-name="sipoutset" class="change-tab active">
		<a href="#sipoutset" aria-controls="sipoutset" role="tab" data-toggle="tab">
			<?php echo _("Outgoing")?>
		</a>
	</li>
	<li role="presentation" data-name="sipinset" class="change-tab">
		<a href="#sipinset" aria-controls="sipinset" role="tab" data-toggle="tab">
			<?php echo _("Incoming")?>
		</a>
	</li>
</ul>
<div class="tab-content display">
	<div role="tabpanel" id="sipoutset" class="tab-pane active">
		<!--Trunk Name-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="channelid"><?php echo _("Trunk Name") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="channelid"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="channelid" id="channelid" value="<?php echo $channelid ?>" tabindex="<?php echo ++$tabindex;?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="channelid-help" class="help-block fpbx-help-block"><?php echo _("Give this trunk a unique name.  Example: myiaxtel. Note this is uses as the trunk context within asterisk")?></span>
				</div>
			</div>
		</div>
		<!--END Trunk Name-->
		<!--PEER Details-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="peerdetails"><?php echo _("PEER Details") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="peerdetails"></i>
							</div>
							<div class="col-md-9">
								<textarea rows="10" cols="40" name="peerdetails" id="peerdetails" class="form-control" tabindex="<?php echo ++$tabindex;?>"><?php echo $peerdetails ?></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="peerdetails-help" class="help-block fpbx-help-block"><?php echo _("Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br /><br />WARNING: Order is important as it will be retained. For example, if you use the \"allow/deny\" directives make sure deny comes first.")?></span>
				</div>
			</div>
		</div>
		<!--END PEER Details-->
	</div>
	<div role="tabpanel" id="sipinset" class="tab-pane">
		<!--USER Context-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="usercontext"><?php echo _("USER Context") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="usercontext"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="usercontext" id="usercontext" value="<?php echo $usercontext  ?>" tabindex="<?php echo ++$tabindex;?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="usercontext-help" class="help-block fpbx-help-block"><?php echo _("This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details.")?></span>
				</div>
			</div>
		</div>
		<!--END USER Context-->
		<!--USER Details-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="userconfig"><?php echo _("USER Details") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="userconfig"></i>
							</div>
							<div class="col-md-9">
								<textarea rows="10" cols="40" class="form-control" name="userconfig" id="userconfig" tabindex="<?php echo ++$tabindex;?>"><?php echo $userconfig; ?></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="userconfig-help" class="help-block fpbx-help-block"><?php echo _("Modify the default USER connection parameters for your VoIP provider.")?></span>
				</div>
			</div>
		</div>
		<!--END USER Details-->
		<!--Register String-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="register"><?php echo _("Register String") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="register"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="register" id="register" value="<?php echo $register ?>" tabindex="<?php echo ++$tabindex;?>" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="register-help" class="help-block fpbx-help-block"><?php echo _("Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com.<br><br>Many providers will require you to provide a DID number, ex: username:password@switch.voipprovider.com/didnumber in order for any DID matching to work.")?></span>
				</div>
			</div>
		</div>
		<!--END Register String-->
	</div>
</div>
