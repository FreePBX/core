<tr>
	<td colspan="2">
		<h4><?php echo _("PJSIP Settings")?><hr></h4>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Configuration Mode")?><span><?php echo _("")?></span></a>: 
	</td><td>
		<select id="configmode" name="configmode">
			<option value="simple">Simple</option>
			<option value="advanced">Advanced</option>
		</select>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Permanent Auth Rejection")?><span><?php echo _("Determines whether failed authentication challenges are treated as permanent failures.")?></span></a>: 
	</td><td>
		<input type="checkbox" name="auth_rejection_permanent" <?php echo ($auth_rejection_permanent == 'on') ? 'checked' : ''?>/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Retry Interval")?><span><?php echo _("Maximum number of registration attempts.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="retry_interval" value="<?php echo $retry_interval?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Expiration")?><span><?php echo _("Expiration time for registrations in seconds.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="expiration" value="<?php echo $expiration?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Forbidden Retry Interval")?><span><?php echo _("Interval used when receiving a 403 Forbidden response.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="forbidden_retry_interval" value="<?php echo $forbidden_retry_interval?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Max Retries")?><span><?php echo _("Maximum number of registration attempts.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="max_retries" value="<?php echo $max_retries?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Qualify Frequency")?><span><?php echo _("Interval at which to qualify.")?></span></a>: 
	</td><td>
		<input type="text" size="8" name="qualify_frequency" value="<?php echo $qualify_frequency?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Username")?><span><?php echo _("")?></span></a>: 
	</td><td>
		<input type="text" name="username" value="<?php echo $username?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Secret")?><span><?php echo _("")?></span></a>: 
	</td><td>
		<input type="text" name="secret" value="<?php echo $secret?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("SIP Server")?><span><?php echo _("SIP Server Address.")?></span></a>: 
	</td><td>
	<input type="text" name="sip_server" value="<?php echo $sip_server?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("SIP Server Port")?><span><?php echo _("SIP Server Port.")?></span></a>: 
	</td><td>
		<input type="text" name="sip_server_port" value="<?php echo !empty($sip_server_port) ? $sip_server_port : '5060'?>"/>
	</td>
</tr>
<tr class="advancedpjsip" style="display:none;">
	<td>
		<a href=# class="info"><?php echo _("Client URI")?><span><?php echo _("Client SIP URI used when attemping outbound registration")?></span></a>: 
	</td><td>
	<input type="text" name="client_uri" value="<?php echo $client_uri?>"/>
	</td>
</tr>
<tr class="advancedpjsip" style="display:none;">
	<td>
		<a href=# class="info"><?php echo _("Server URI")?><span><?php echo _("SIP URI of the server to register against")?></span></a>: 
	</td><td>
		<input type="text" name="server_uri" value="<?php echo $server_uri?>"/>
	</td>
</tr>
<tr class="advancedpjsip" style="display:none;">
	<td>
		<a href=# class="info"><?php echo _("AOR Contact")?><span><?php echo _("Permanent contacts assigned to AoR")?></span></a>: 
	</td><td>
		<input type="text" name="aor_contact" value="<?php echo $aor_contact?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Outbound Proxy")?><span><?php echo _("Outbound Proxy.")?></span></a>: 
	</td><td>
		<input type="text" name="outbound_proxy" value="<?php echo $outbound_proxy?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Contact User")?><span><?php echo _("Contact User to use in request.")?></span></a>: 
	</td><td>
		<input type="text" name="contact_user" value="<?php echo $contact_user?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Context")?><span><?php echo _("Context to send the Inbound Call to.")?></span></a>: 
	</td><td>
		<input type="text" name="context" value="<?php echo !empty($context) ? $context : 'from-pstn'?>"/>
	</td>
</tr>
<tr>
	<td>
		<a href=# class="info"><?php echo _("Transport")?><span><?php echo _("The Transport to use for connection")?></span></a>: 
	</td>
	<td>
		<select name="transport">
		<?php foreach($transports as $tp) {?>
			<option value="<?php echo $tp?>" <?php echo ($tp == $transport) ? 'selected' : ''?>><?php echo $tp?></option>
		<?php } ?>
	</select>
	</td>
</tr>
<tr>
	<td valign='top'><a href="#" class="info"><?php echo _("Codecs")?>:<span><?php echo _("Check the desired codecs, all others will be disabled. Drag to re-order.")?></span></a></td>
	<td>
<?php
  $seq = 1;
echo '<ul class="sortable">';
  foreach ($codecs as $codec => $codec_state) {
    $tabindex++;
    $codec_trans = _($codec);
    $codec_checked = $codec_state ? 'checked' : '';
	echo '<li><a href="#">'
		. '<img src="assets/sipsettings/images/arrow_up_down.png" height="16" width="16" border="0" alt="move" style="float:none; margin-left:-6px; margin-bottom:-3px;cursor:move" /> '
		. '<input type="checkbox" '
		. ($codec_checked ? 'value="'. $seq++ . '" ' : '')
		. 'name="codec[' . $codec . ']" '
		. 'id="'. $codec . '" '
		. 'class="audio-codecs" tabindex="' . $tabindex. '" '
		. $codec_checked
		. ' />'
		. '<label for="'. $codec . '"> '
		. '<small>' . $codec_trans . '</small>'
		. ' </label></a></li>';
  }
echo '</ul>';
?>

    </td>
  </tr>
<script>
$(function() {
	$( ".sortable" ).sortable();
});
$('#configmode').change(function(e){
	switch_view();
})
switch_view();
function switch_view() {
	if($('#configmode').val() == 'simple') {
		$('.advancedpjsip').hide();
		$('.simplepjsip').show();
	} else {
		$('.advancedpjsip').show();
		$('.simplepjsip').hide();
	}
}
</script>