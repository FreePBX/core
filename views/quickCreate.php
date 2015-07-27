<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="tech"><?php echo _('Type')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="tech"></i>
					</div>
					<div class="col-md-9">	<select class="form-control" name="tech" id="tech">
							<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
								<option value="<?php echo $driver['rawName']?>" <?php echo ($driver['rawName'] == "sip" || $driver['rawName'] == "pjsip") ? 'selected' : ''?>><?php echo $driver['shortName']?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="tech-help" class="help-block fpbx-help-block">
				<?php echo _("Please select the type of device you want to create");?><br/>
				<ul>
				<?php foreach(FreePBX::Core()->getAllDriversInfo() as $driver) {?>
					<li><strong><?php echo $driver['shortName']?>:</strong> <?php echo isset($driver['description'])?$driver['description']:''?></li>
				<?php } ?>
				</ul>
			</span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="extension"><?php echo _('Extension Number')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="extension"></i>
					</div>
					<div class="col-md-9"><input type="number" class="form-control" id="extension" name="extension" placeholder="<?php echo _('Enter Extension')?>" data-for="extension" value="<?php echo $startExt?>"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="extension-help" class="help-block fpbx-help-block"><?php echo _('The extension number to dial to reach this user.')?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="name"><?php echo _('Display Name')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i>
					</div>
					<div class="col-md-9"><input type="text" class="form-control" id="name" name="name" placeholder="<?php echo _('Enter name')?>" data-for="name"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="name-help" class="help-block fpbx-help-block"><?php echo _('The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.')?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="outboundcid"><?php echo _('Outbound Caller ID')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="outboundcid"></i>
					</div>
					<div class="col-md-9"><input type="text" class="form-control" name="outboundcid" id="outboundcid" placeholder='"<?php echo _('Caller Name')?>" <#######>' data-for="outboundcid"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="outboundcid-help" class="help-block fpbx-help-block"><?php echo _('Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.<br><br>Format: <b>"caller name" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound CallerID feature for this user.')?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="email"><?php echo _('Email Address')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="email"></i>
					</div>
					<div class="col-md-9"><input type="email" class="form-control" id="email" name="email" placeholder="<?php echo _('name@domain.tld')?>" data-for="email"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="email-help" class="help-block fpbx-help-block"><?php echo _('Email address to use for services such as Voicemail, User Management and Fax')?></span>
		</div>
	</div>
</div>
