<tr>
	<td colspan="2">
		<h4><?php echo _("Outgoing Settings")?><hr></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Zap Identifier")?><span><?php echo _("ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero).")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
		<input type="hidden" size="14" name="usercontext" value="notneeded"/>
	</td>
</tr>