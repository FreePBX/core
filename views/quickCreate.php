<div class="form-group">
	<label for="tech"><?php echo _('Type')?> <i class="fa fa-question-circle fpbx-help-icon" data-for="tech"></i></label>
	<select class="form-control" name="tech" data-for="tech">
		<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
			<option value="<?php echo $driver['rawName']?>" <?php echo ($driver['rawName'] == "sip" || $driver['rawName'] == "pjsip") ? 'selected' : ''?>><?php echo $driver['shortName']?></option>
		<?php } ?>
	</select>
	<span id="tech-help" class="help-block fpbx-help-block">
		<?php echo _("Please select the type of device you want to create");?><br/>
		<ul>
		<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
			<li><strong><?php echo $driver['shortName']?>:</strong> <?php echo $driver['description']?></li>
		<?php } ?>
		</ul>
	</span>
</div>
<div class="form-group">
	<label for="extension"><?php echo _('Extension Number')?> <i class="fa fa-question-circle fpbx-help-icon" data-for="extension"></i></label>
	<input type="number" class="form-control" id="extension" name="extension" placeholder="<?php echo _('Enter Extension')?>" data-for="extension" value="<?php echo $startExt?>">
	<span id="extension-help" class="help-block fpbx-help-block"><?php echo _('The extension number to dial to reach this user.')?></span>
</div>
<div class="form-group">
	<label for="name"><?php echo _('Display Name')?> <i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i></label>
	<input type="text" class="form-control" id="name" name="name" placeholder="<?php echo _('Enter name')?>" data-for="name">
	<span id="name-help" class="help-block fpbx-help-block"><?php echo _('The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.')?></span>
</div>
<div class="form-group">
	<label for="outboundcid"><?php echo _('Outbound Caller ID')?> <i class="fa fa-question-circle fpbx-help-icon" data-for="outboundcid"></i></label>
	<input type="text" class="form-control" name="outboundcid" id="outboundcid" placeholder="<?php echo _('Enter Caller ID')?>" data-for="outboundcid">
	<span id="outboundcid-help" class="help-block fpbx-help-block"><?php echo _('Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.<br><br>Format: <b>"caller name" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound CallerID feature for this user.')?></span>
</div>
<div class="form-group">
	<label for="email"><?php echo _('Email Address')?> <i class="fa fa-question-circle fpbx-help-icon" data-for="email"></i></label>
	<input type="email" class="form-control" id="email" name="email" placeholder="<?php echo _('Enter email')?>" data-for="email">
	<span id="email-help" class="help-block fpbx-help-block"><?php echo _('Email address to use for services such as Voicemail, User Management and Fax')?></span>
</div>
