<tr>
	<td colspan="2">
		<h4><?php echo _("Outgoing Settings")?><hr></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Custom Dial String")?><span><?php echo _("Define the custom Dial String.  Include the token")?> $OUTNUM$ <?php echo _("wherever the number to dial should go.<br><br><b>examples:</b><br>")?>CAPI/XXXXXXXX/$OUTNUM$<br>H323/$OUTNUM$@XX.XX.XX.XX<br>OH323/$OUTNUM$@XX.XX.XX.XX:XXXX<br>vpb/1-1/$OUTNUM$</span></a>: 
	</td><td>
		<input type="text" size="35" maxlength="46" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
		<input type="hidden" size="14" name="usercontext" value="notneeded"/>
	</td>
</tr>	