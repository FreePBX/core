<tr>
	<td colspan="2">
		<h4><?php echo _("Outgoing Settings")?><hr></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("DUNDi Mapping")?><span><?php echo _("This is the name of the DUNDi mapping as defined in the [mappings] section of remote dundi.conf peers. This corresponds to the 'include' section of the peer details in the local dundi.conf file. This requires manual configuration of DUNDi to use this trunk.")?></span></a>: 
	</td><td>
		<input type="text" size="35" maxlength="46" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
		<input type="hidden" size="14" name="usercontext" value="notneeded"/>
	</td>
</tr>	