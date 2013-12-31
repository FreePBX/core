<tr>
	<td colspan="2">
		<h4><?php echo _("PJSIP Settings")?><hr></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Permanent Auth Rejection")?><span><?php echo _("Determines whether failed authentication challenges are treated as permanent failures.")?></span></a>: 
	</td><td>
		<input type="checkbox" name="auth_rejection_permanent" checked/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Expiration")?><span><?php echo _("Expiration time for registrations in seconds.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="expiration" value="3600"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Retry Interval")?><span><?php echo _("Interval used when receiving a 403 Forbidden response.")?></span></a>: 
	</td><td>
		<input type="text" name="retry_interval" value="60"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Forbidden Retry Interval")?><span><?php echo _("Maximum number of registration attempts.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="forbidden_retry_interval" value="10"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Max Retries")?><span><?php echo _("Maximum number of registration attempts.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="max_retries" value="10"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Client URI")?><span><?php echo _("Client SIP URI used when attemping outbound registration.")?></span></a>: 
	</td><td>
	<input type="text" name="client_uri" value="<?php echo $client_uri?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Contact User")?><span><?php echo _("Contact User to use in request.")?></span></a>: 
	</td><td>
		<input type="text" name="contact_user" value=""/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Outbound Proxy")?><span><?php echo _("Contact User to use in request.")?></span></a>: 
	</td><td>
		<input type="text" name="outbound_proxy" value=""/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Server URI")?><span><?php echo _("SIP URI of the server to register against.")?></span></a>: 
	</td><td>
		<input type="text" name="server_uri" value=""/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Username")?><span><?php echo _("")?></span></a>: 
	</td><td>
		<input type="text" name="username" value=""/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Secret")?><span><?php echo _("")?></span></a>: 
	</td><td>
		<input type="text" name="secret" value=""/>
	</td>
</tr>

