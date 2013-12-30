<tr>
	<td colspan="2">
		<h4><?php echo _("Outgoing Settings")?><hr></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Trunk Name")?><span><?php echo _("Give this trunk a unique name.  Example: myiaxtel")?></span></a>: 
	</td><td>
		<input type="text" size="14" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td>
</tr>
<tr>
	<td colspan="2">
	<a href=# class="info"><?php echo _("PEER Details")?><span><?php echo _("Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br /><br />WARNING: Order is important as it will be retained. For example, if you use the \"allow/deny\" directives make sure deny comes first.")?></span></a>: 
	</td>
</tr>
<tr>
	<td colspan="2">
		<textarea rows="10" cols="40" name="peerdetails" tabindex="<?php echo ++$tabindex;?>"><?php echo htmlspecialchars($peerdetails) ?></textarea>
	</td>
</tr>
<tr>
	<td colspan="2">
		<h4><?php echo _("Incoming Settings")?></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("USER Context")?><span><?php echo _("This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details.")?></span></a>: 
	</td><td>
		<input type="text" size="14" name="usercontext" value="<?php echo htmlspecialchars($usercontext)  ?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td>
</tr>
<tr>
	<td colspan="2">
	<a href=# class="info"><?php echo _("USER Details")?><span><?php echo _("Modify the default USER connection parameters for your VoIP provider.")?><br><br><?php echo _("You may need to add to the default lines listed below, depending on your provider..<br /><br />WARNING: Order is important as it will be retained. For example, if you use the \"allow/deny\" directives make sure deny 
comes first.")?></span></a>: 
	</td>
</tr>
<tr>
	<td colspan="2">
		<textarea rows="10" cols="40" name="userconfig" tabindex="<?php echo ++$tabindex;?>"><?php echo htmlspecialchars($userconfig); ?></textarea>
	</td>
</tr>
<tr>
	<td colspan="2">
		<h4><?php echo _("Registration")?></h4>
	</td>
</tr>
<tr>
	<td colspan="2">
		<a href=# class="info"><?php echo _("Register String")?><span><?php echo _("Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com.<br><br>Many providers will require you to provide a DID number, ex: username:password@switch.voipprovider.com/didnumber in order for any DID matching to work.")?></span></a>: 
	</td>
</tr>
<tr>
	<td colspan="2">
		<input type="text" size="90" name="register" value="<?php echo htmlspecialchars($register) ?>" tabindex="<?php echo ++$tabindex;?>" />
	</td>
</tr>