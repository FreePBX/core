<?php
foreach($transports as $tp) {
	$transportopts .= '<option value="'.$tp.'" '.($tp == $transport) ? 'selected' : ''.'>'.$tp.'</option>';
}
$seq = 1;
$codechtml = '<ul class="sortable">';
	foreach ($codecs as $codec => $codec_state) {
		$tabindex++;
		$codec_trans = _($codec);
		$codec_checked = $codec_state ? 'checked' : '';
		$codechtml .= '<li><a href="#">'
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
$codechtml .= '</ul>';

?>

<h3><?php echo _("PJSIP Settings")?></h3>

<ul class="nav nav-tabs" role="tablist">	
	<li role="presentation" data-name="pjsgeneral" class="active">
		<a href="#pjsgeneral" aria-controls="pjsgeneral" role="tab" data-toggle="tab">
			<?php echo _("General")?>
		</a>
	</li>
	<li role="presentation" data-name="pjsadvances" class="change-tab">
		<a href="#pjsadvanced" aria-controls="pjsadvanced" role="tab" data-toggle="tab">
			<?php echo _("Advanced")?>
		</a>
	</li>
	<li role="presentation" data-name="pjscodecs" class="change-tab">
		<a href="#pjscodecs" aria-controls="pjscodecs" role="tab" data-toggle="tab">
			<?php echo _("Codecs")?>
		</a>
	</li>
</ul>
<div class="tab-content display full-border">
	<div role="tabpanel" id="pjsgeneral" class="tab-pane active">
		<br/>
		<br/>
		<!--PERMINENT AUTH REJECTION-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="auth_rejection_permanentw"><?php echo _("Permanent Auth Rejection") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="auth_rejection_permanentw"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="checkbox" name="auth_rejection_permanent" id="auth_rejection_permanent" <?php echo ($auth_rejection_permanent == 'on') ? 'checked' : ''?>/>
								<label for="auth_rejection_permanent"><?php echo _("Enabled")?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="auth_rejection_permanentw-help" class="help-block fpbx-help-block"><?php echo _("Determines whether failed authentication challenges are treated as permanent failures.")?></span>
				</div>
			</div>
		</div>
		<!--END PERMINENT AUTH REJECTION-->
		<!--RETRY INTERVAL-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="retry_interval"><?php echo _("Retry Interval")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="retry_interval"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="retry_interval" id="retry_interval" value="<?php echo $retry_interval?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="retry_interval-help" class="help-block fpbx-help-block"><?php echo _("Maximum number of registration attempts.")?></span>
				</div>
			</div>
		</div>
		<!--END RETRY INTERVAL-->
		<!--EXPIRATION-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="expiration"><?php echo _("Expiration") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="expiration"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="expiration" id="expiration" value="<?php echo $expiration?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="expiration-help" class="help-block fpbx-help-block"><?php echo _("Expiration time for registrations in seconds.")?></span>
				</div>
			</div>
		</div>
		<!--END EXPIRATION-->
		<!--FORBIDDEN RETRY INTERVAL-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="forbidden_retry_interval"><?php echo _("Forbidden Retry Interval") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="forbidden_retry_interval"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="forbidden_retry_interval" id="forbidden_retry_interval" value="<?php echo $forbidden_retry_interval?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="forbidden_retry_interval-help" class="help-block fpbx-help-block"><?php echo _("Interval used when receiving a 403 Forbidden response.")?></span>
				</div>
			</div>
		</div>
		<!--END FORBIDDEN RETRY INTERVAL-->
		<!--MAX RETRIES-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="max_retries"><?php echo _("Max Retries") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="max_retries"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="max_retries" id="max_retries" value="<?php echo $max_retries?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="max_retries-help" class="help-block fpbx-help-block"><?php echo _("Maximum number of registration attempts.")?></span>
				</div>
			</div>
		</div>
		<!--END MAX RETRIES-->
		<!--QUALIFY FREQUENCY-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="qualify_frequency"><?php echo _("Qualify Frequency") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="qualify_frequency"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="qualify_frequency" id="qualify_frequency" value="<?php echo $qualify_frequency?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="qualify_frequency-help" class="help-block fpbx-help-block"><?php echo _("Interval at which to qualify.")?></span>
				</div>
			</div>
		</div>
		<!--END QUALIFY FREQUENCY-->
		<!--USERNAME-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="username"><?php echo _("Username") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="username"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="username" id="username" value="<?php echo $username?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="username-help" class="help-block fpbx-help-block"><?php echo _("Username for this provider")?></span>
				</div>
			</div>
		</div>
		<!--END USERNAME-->
		<!--SECRET-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="secret"><?php echo _("Secret") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="secret"></i>
							</div>
							<div class="col-md-9">
								<input type="password" class="form-control" name="secret" id="secret" value="<?php echo $secret?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="secret-help" class="help-block fpbx-help-block"><?php echo _("Password for this provider")?></span>
				</div>
			</div>
		</div>
		<!--END SECRET-->
		<!--SIP SERVER-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="sip_server"><?php echo _("SIP Server") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="sip_server"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="sip_server" id="sip_server" value="<?php echo $sip_server?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="sip_server-help" class="help-block fpbx-help-block"><?php echo _("SIP Server Address.")?></span>
				</div>
			</div>
		</div>
		<!--END SIP SERVER-->
		<!--SIP SERVER PORT-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="sip_server_port"><?php echo _("SIP Server Port") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="sip_server_port"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="sip_server_port" id="sip_server_port" value="<?php echo !empty($sip_server_port) ? $sip_server_port : '5060'?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="sip_server_port-help" class="help-block fpbx-help-block"><?php echo _("SIP Server Port.")?></span>
				</div>
			</div>
		</div>
		<!--END SIP SERVER PORT-->
		<!--OUTBOUND PROXY-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="outbound_proxy"><?php echo _("Outbound Proxy") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="outbound_proxy"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="outbound_proxy" id="outbound_proxy" value="<?php echo $outbound_proxy?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="outbound_proxy-help" class="help-block fpbx-help-block"><?php echo _("Outbound Proxy")?></span>
				</div>
			</div>
		</div>
		<!--END OUTBOUND PROXY-->
		<!--CONTACT USER-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="contact_user"><?php echo _("Contact User") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="contact_user"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="contact_user" id="contact_user" value="<?php echo $contact_user?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="contact_user-help" class="help-block fpbx-help-block"><?php echo _("Contact User to use in request.")?></span>
				</div>
			</div>
		</div>
		<!--END CONTACT USER-->
		<!--CONTEXT-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="context"><?php echo _("Context") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="context"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="context" id="context" value="<?php echo !empty($context) ? $context : 'from-pstn'?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="context-help" class="help-block fpbx-help-block"><?php echo _("Context to send the Inbound Call to.")?></span>
				</div>
			</div>
		</div>
		<!--END CONTEXT-->
		<!--TRANSPORT-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="transport"><?php echo _("Transport") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="transport"></i>
							</div>
							<div class="col-md-9">
								<select name="transport" id="transport" class="form-control">
									<?php echo $transportopts ?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="transport-help" class="help-block fpbx-help-block"><?php echo _("The Transport to use for connection")?></span>
				</div>
			</div>
		</div><!--END TRANSPORT-->
	</div><!--END GENERAL TAB-->
	<div role="tabpanel" id="pjsadvanced" class="tab-pane">
		<br/>
		<br/>
		<!--CLIENT URI-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="client_uri"><?php echo _("Client URI") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="client_uri"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="client_uri" id="client_uri" value="<?php echo $client_uri?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="client_uri-help" class="help-block fpbx-help-block"><?php echo  _("Client SIP URI used when attemping outbound registration")?></span>
				</div>
			</div>
		</div>
		<!--END CLIENT URI-->					
		<!--SERVER URI-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="server_uri"><?php echo _("Server URI") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="server_uri"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="server_uri" id="server_uri" value="<?php echo $server_uri?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="server_uri-help" class="help-block fpbx-help-block"><?php echo _("SIP URI of the server to register against")?></span>
				</div>
			</div>
		</div>
		<!--END SERVERURI-->					
		<!--AOR CONTACT-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="aor_contact"><?php echo _("AOR Contact")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="aor_contact"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="aor_contact" id="aor_contact" value="<?php echo $aor_contact?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="aor_contact-help" class="help-block fpbx-help-block"><?php echo _("Permanent contacts assigned to AoR")?></span>
				</div>
			</div>
		</div><!--END AOR CONTACT-->					
	</div><!--END ADVANCED TAB-->
	<div role="tabpanel" id="pjscodecs" class="tab-pane">
		<br/>
		<br/>
		<div class="well well-info">
			<?php echo _("Check the desired codecs, all others will be disabled. Drag to re-order.")?>
		</div>
		<br/>
		<?php echo $codechtml ?>
	</div><!--END CODECS TAB-->
</div>

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
