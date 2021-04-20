<?php
	foreach($transports as $tp) {
		$transportopts .= '<option value="'.$tp.'" '.(($tp == $transport) ? 'selected' : '').'>'.$tp.'</option>';
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
	$ast_ge_12 = version_compare(\FreePBX::Config()->get("ASTVERSION"), "13.0", "ge");
?>

<h3><?php echo _("PJSIP Settings")?></h3>

<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" data-name="pjsgeneral" class="change-tab active">
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
<div class="tab-content display">
	<div role="tabpanel" id="pjsgeneral" class="tab-pane active">
		<!--USERNAME-->
		<div class="element-container">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="username"><?php echo _("Username") ?></label>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" name="username" id="username" value="<?php echo $username?>"/>
					</div>
				</div>
			</div>
		</div>
		<!--END USERNAME-->
		<div class="element-container">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="secret"><?php echo _("Auth username") ?></label>
						 <i class="fa fa-question-circle fpbx-help-icon" data-for="auth_username"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" name="auth_username" id="auth_username" value="<?php echo $auth_username ?>">
					</div>
				</div>
				<div class="col-md-12">
					<span id="auth_username-help" class="help-block fpbx-help-block"><?php echo _("This need to be set only  when auth username not same as the username. ")?> </span>
				</div>
			</div>
		</div>
		<!--SECRET-->
		<div class="element-container">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="secret"><?php echo _("Secret") ?></label>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control password-meter confidential" name="secret" id="secret" value="<?php echo $secret?>"/>
					</div>
				</div>
			</div>
		</div>
		<!--END SECRET-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-3">
					<label class="control-label" for="authentication"><?php echo _("Authentication") ?></label>
					<i class="fa fa-question-circle fpbx-help-icon" data-for="authentication"></i>
				</div>
				<div class="col-md-9 radioset">
					<input type="radio" name="authentication" id="authenticationoutbound" value="outbound" <?php echo ($authentication == "outbound")?"CHECKED":"" ?>>
					<label for="authenticationoutbound"><?php echo _("Outbound");?></label>
					<input type="radio" name="authentication" id="authenticationinbound" value="inbound" <?php echo ($authentication == "inbound")?"CHECKED":"" ?>>
					<label for="authenticationinbound"><?php echo _("Inbound");?></label>
					<input type="radio" name="authentication" id="authenticationboth" value="both" <?php echo ($authentication == "both")?"CHECKED":"" ?>>
					<label for="authenticationboth"><?php echo _("Both");?></label>
					<input type="radio" name="authentication" id="authenticationoff" value="off" <?php echo ($authentication == "off")?"CHECKED":"" ?>>
					<label for="authenticationoff"><?php echo _("None");?></label>
				</div>
				<div class="col-md-12">
					<span id="authentication-help" class="help-block fpbx-help-block"><?php echo _("Usually, this will be set to 'Outbound', which authenticates calls going out, and allows unauthenticated calls in from the other server. If you select 'None', all calls from or to the specified SIP Server are unauthenticated. <strong>Setting this to 'None' may be insecure!</strong>")?></span>
				</div>
			</div>
		</div>
		<div class="element-container">
			<div class="row">
				<div class="col-md-3">
					<label class="control-label" for="registration"><?php echo _("Registration") ?></label>
					<i class="fa fa-question-circle fpbx-help-icon" data-for="registration"></i>
				</div>
				<div class="col-md-9 radioset">
					<input type="radio" name="registration" id="registrationtx" value="send" <?php echo ($registration == "send")?"CHECKED":"" ?>>
					<label for="registrationtx"><?php echo _("Send");?></label>
					<input type="radio" name="registration" id="registrationrx" value="receive" <?php echo ($registration == "receive")?"CHECKED":"" ?>>
					<label for="registrationrx"><?php echo _("Receive");?></label>
					<input type="radio" name="registration" id="registrationnone" value="none" <?php echo ($registration == "none")?"CHECKED":"" ?>>
					<label for="registrationnone"><?php echo _("None");?></label>
				</div>
				<div class="col-md-12">
					<span id="registration-help" class="help-block fpbx-help-block">
						<?php echo _("You normally <strong>Send</strong> registration, which tells the remote server where to send your calls. If the other server is not on a fixed address, it will need to register to this server (<strong>Receive</strong>), so this server can send calls to it. You would select <strong>None</strong> if both machines have a fixed address and do not require registration.")."<br>"._("<strong>Warning:</strong> If you select 'None', registration attempts for the Username and Secret specified above will be rejected. Setting this incorrectly may result in firewall services detecting this as an attack and blocking the machine trying to register. Do not change this unless you control both servers, and are sure it is required!")?>
					</span>
				</div>
			</div>
		</div>
		<div class="element-container">
			<div class="row">
				<div class="col-md-3">
					<label class="control-label" for="language"><?php echo _("Language Code") ?></label>
					<i class="fa fa-question-circle fpbx-help-icon" data-for="language"></i>
				</div>
				<div class="col-md-9">
					<?php if(\FreePBX::Modules()->checkStatus("soundlang")) {?>
						<?php $langs = \FreePBX::Soundlang()->getLanguages(); $langs = is_array($langs) ? $langs : array();?>
						<select name="language" class="form-control">
							<option value=""><?php echo _("Default")?></option>
							<?php foreach($langs as $key => $lang) { ?>
								<option value="<?php echo $key?>" <?php echo ($language == $key) ? "selected" : ""?>><?php echo $lang?></option>
							<?php } ?>
						</select>
					<?php } else { ?>
						<input name="language" class="form-control" value="<?php echo $language?>">
					<?php } ?>
				</div>
				<div class="col-md-12">
					<span id="language-help" class="help-block fpbx-help-block"><?php echo _("This will cause all messages and voice prompts to use the selected language if installed. Languages can be added or removed in the Sound Languages module")?></span>
				</div>
			</div>
		</div>
		<!--SIP SERVER-->
		<div class="element-container">
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
				<div class="col-md-12">
					<span id="sip_server-help" class="help-block fpbx-help-block"><?php echo _("SIP Server Address. This is ignored when Registration is set to 'Receive'.")?></span>
				</div>
			</div>
		</div>
		<!--END SIP SERVER-->
		<!--SIP SERVER PORT-->
		<div class="element-container">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="sip_server_port"><?php echo _("SIP Server Port") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="sip_server_port"></i>
					</div>
					<div class="col-md-9">
						<input type="number" class="form-control" name="sip_server_port" id="sip_server_port" value="<?php echo !empty($sip_server_port) ? $sip_server_port : ''?>"/>
					</div>
				</div>
				<div class="col-md-12">
					<span id="sip_server_port-help" class="help-block fpbx-help-block"><?php echo _("SIP Server Port. This is ignored when Registration is set to 'Receive'.")?></span>
				</div>
			</div>
		</div>
		<!--END SIP SERVER PORT-->
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
		<!--TRANSPORT-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="dtmfmode"><?php echo _("DTMF Mode") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="dtmfmode"></i>
							</div>
							<div class="col-md-9">
								<select name="dtmfmode" id="dtmfmode" class="form-control">
									<?php if($ast_ge_12) {?>
										<option value="auto" <?php echo isset($dtmfmode) && $dtmfmode == "auto" ? "selected" : ""?>><?php echo _("Auto")?></option>
									<?php } ?>
									<option value="rfc4733" <?php echo isset($dtmfmode) && $dtmfmode == "rfc4733" ? "selected" : ""?>>RFC 4733</option>
									<option value="inband" <?php echo isset($dtmfmode) && $dtmfmode == "inband" ? "selected" : ""?>><?php echo _("Inband")?></option>
									<option value="info" <?php echo isset($dtmfmode) && $dtmfmode == "info" ? "selected" : ""?>><?php echo _("Info")?></option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="dtmfmode-help" class="help-block fpbx-help-block"><?php echo sprintf(_("The DTMF signaling mode used by this trunk, usually RFC for most trunks<ul><li>Auto [%s] - DTMF is sent as RFC 4733 if the other side supports it or as INBAND if not.</li><li>rfc4733 - DTMF is sent out of band of the main audio stream.This supercedes the older RFC-2833 used within the older chan_sip.</li><li>inband - DTMF is sent as part of audio stream.</li><li>info - DTMF is sent as SIP INFO packets.</li></ul>"),"Asterisk 13")?></span>
				</div>
			</div>
		</div><!--END TRANSPORT-->
		<?php
		$ver_list = array("13.24.0", "16.1.0", "18.0.0");
		if(version_min(\FreePBX::Config()->get('ASTVERSION'),$ver_list) == true) { ?>
		<!--PJSIP TRUNK LINE ENABLE / DISABLE-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="pjsip line"><?php echo _("Send Line in Registration") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="pjsip_linew"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="pjsip_line" id="pjsip_lineyes" value="true" <?php echo ($pjsip_line == "true" || empty($pjsip_line)?"CHECKED":"") ?>>
								<label for="pjsip_lineyes"><?php echo _("Yes")?></label>
								<input type="radio" name="pjsip_line" id="pjsip_lineno" value="false" <?php echo ($pjsip_line == "false" ?"CHECKED":"") ?>>
								<label for="pjsip_lineno"><?php echo _("No")?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="pjsip_linew-help" class="help-block fpbx-help-block"><?php echo _("If this option is enabled, a “line” parameter is added to the outgoing “Contact” header during registration.")?></span>
				</div>
			</div>
		</div>
		<!--PJSIP TRUNK LINE ENABLE / DISABLE-->
		<!--SEND CONNECTED LINE-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="send_connected_linew"><?php echo _("Send Connected Line") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="send_connected_linew"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="send_connected_line" id="send_connected_lineyes" value="true" <?php echo ($send_connected_line == "true"?"CHECKED":"") ?>>
								<label for="send_connected_lineyes"><?php echo _("Yes")?></label>
								<input type="radio" name="send_connected_line" id="send_connected_lineno" value="false" <?php echo ($send_connected_line == "false" || empty($send_connected_line) ?"CHECKED":"") ?>>
								<label for="send_connected_lineno"><?php echo _("No")?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="send_connected_linew-help" class="help-block fpbx-help-block"><?php echo _("Send Connected Line updates to this endpoint. It can be bad for some providers. False by default.")?></span>
				</div>
			</div>
		</div>
		<!--END SEND CONNECTED LINE-->
		<?php } ?>
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
								<input type="radio" name="auth_rejection_permanent" id="auth_rejection_permanentyes" value="on" <?php echo ($auth_rejection_permanent == "on"?"CHECKED":"") ?>>
								<label for="auth_rejection_permanentyes"><?php echo _("Yes")?></label>
								<input type="radio" name="auth_rejection_permanent" id="auth_rejection_permanentno" value="off" <?php echo ($auth_rejection_permanent == "off"?"CHECKED":"") ?>>
								<label for="auth_rejection_permanentno"><?php echo _("No")?></label>
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
								<div class="input-group">
									<input type="number" class="form-control" name="forbidden_retry_interval" id="forbidden_retry_interval" value="<?php echo $forbidden_retry_interval?>"/>
									<span class="input-group-addon"><?php echo _("Seconds")?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="forbidden_retry_interval-help" class="help-block fpbx-help-block"><?php echo _("How long to wait before retry when receiving a 403 Forbidden response.")?></span>
				</div>
			</div>
		</div>
		<!--END FORBIDDEN RETRY INTERVAL-->
		<!--FATAL RETRY INTERVAL-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="fatal_retry_interval"><?php echo _("Fatal Retry Interval") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="fatal_retry_interval"></i>
							</div>
							<div class="col-md-9">
								<div class="input-group">
									<input type="number" class="form-control" name="fatal_retry_interval" id="fatal_retry_interval" value="<?php echo $fatal_retry_interval?>"/>
									<span class="input-group-addon"><?php echo _("Seconds")?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="fatal_retry_interval-help" class="help-block fpbx-help-block"><?php echo _("How long to wait before retry when receiving a fatal response. If 'Forbidden Retry Interval' is also set then 'Forbidden Retry Interval' takes precedence over this one when a 403 is received. Also, if 'Permanent Auth Rejection' is enabled then a 401 and 407 become subject to this retry interval.")?></span>
				</div>
			</div>
		</div>
		<!--END FATAL RETRY INTERVAL-->
		<!--RETRY INTERVAL-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="retry_interval"><?php echo _("General Retry Interval")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="retry_interval"></i>
							</div>
							<div class="col-md-9">
								<div class="input-group">
									<input type="number" class="form-control" name="retry_interval" id="retry_interval" value="<?php echo $retry_interval?>"/>
									<span class="input-group-addon"><?php echo _("Seconds")?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="retry_interval-help" class="help-block fpbx-help-block"><?php echo _("The number of seconds Asterisk will wait before attempting to send another REGISTER request to the registrar")?></span>
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
								<div class="input-group">
									<input type="number" class="form-control" name="expiration" id="expiration" value="<?php echo $expiration?>"/>
									<span class="input-group-addon"><?php echo _("Seconds")?></span>
								</div>
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
					<span id="max_retries-help" class="help-block fpbx-help-block"><?php echo _("How many times Asterisk will attempt to re-attempt registration before permanently giving up. Maximum of 1000000.")?></span>
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
								<div class="input-group">
									<input type="number" class="form-control" name="qualify_frequency" id="qualify_frequency" value="<?php echo $qualify_frequency?>"/>
									<span class="input-group-addon"><?php echo _("Seconds")?></span>
								</div>
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
		<!--USER EQ PHONE-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="user_eq_phone"><?php echo _("User = Phone") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="user_eq_phone"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="user_eq_phone" id="user_eq_phoneyes" value="yes" <?php echo ($user_eq_phone == "yes") ? "CHECKED" : "" ?>>
								<label for="user_eq_phoneyes"><?php echo _("Yes")?></label>
								<input type="radio" name="user_eq_phone" id="user_eq_phoneno" value="no" <?php echo ($user_eq_phone == "no" || empty($user_eq_phone)) ? "CHECKED" : "" ?>>
								<label for="user_eq_phoneno"><?php echo _("No")?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="user_eq_phone-help" class="help-block fpbx-help-block"><?php echo _("Determines whether a user=phone parameter is placed into the request URI if the user is determined to be a phone number.")?></span>
				</div>
			</div>
		</div>
		<!--END USER EQ PHONE-->
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
		<!--FROM DOMAIN-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="from_domain"><?php echo _("From Domain")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="from_domain"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="from_domain" id="from_domain" value="<?php echo $from_domain?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="from_domain-help" class="help-block fpbx-help-block"><?php echo _("Domain to use in From header for requests to this trunk")?></span>
				</div>
			</div>
		</div><!--END FROM DOMAIN-->
		<!--FROM USER-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="from_user"><?php echo _("From User")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="from_user"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="from_user" id="from_user" value="<?php echo $from_user?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="from_user-help" class="help-block fpbx-help-block"><?php echo _("Username to use in From header for requests to this trunk")?></span>
				</div>
			</div>
		</div><!--END FROM USER-->
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
					<span id="client_uri-help" class="help-block fpbx-help-block">
						<?php echo  _("Client SIP URI used when attemping outbound registration. This setting is automatically generated by the PBX if left blank").'<br/>'._("sip:[username]@[ip]:[port]")?>
					</span>
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
					<span id="server_uri-help" class="help-block fpbx-help-block">
						<?php echo _("SIP URI of the server to register against. This setting is automatically generated by the PBX if left blank").'<br/>'._("sip:[username]@[ip]:[port]")?>
					</span>
				</div>
			</div>
		</div>
		<!--END SERVERURI-->
        <!--MEDIA ADDRESS -->
        <div class="element-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="media_address"><?php echo _("Media Address") ?></label>
                                <i class="fa fa-question-circle fpbx-help-icon" data-for="media_address"></i>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="media_address" id="media_address" value="<?php echo $media_address?>"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
					<span id="media_address-help" class="help-block fpbx-help-block">
						<?php echo _("This address will be provided to clients. If blank, will use the default settings")?>
					</span>
                </div>
            </div>
        </div>
        <!--END MEDIA ADDRESS-->
                <!--AOR -->
                <div class="element-container">
                        <div class="row">
                                <div class="col-md-12">
                                        <div class="row">
                                                <div class="form-group">
                                                        <div class="col-md-3">
                                                                <label class="control-label" for="aors"><?php echo _("AOR")?></label>
                                                                <i class="fa fa-question-circle fpbx-help-icon" data-for="aors"></i>
                                                        </div>
                                                        <div class="col-md-9">
                                                                <input type="text" class="form-control" name="aors" id="aors" value="<?php echo $aors?>"/>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                        </div>
                        <div class="row">
                                <div class="col-md-12">
                                        <span id="aors-help" class="help-block fpbx-help-block"><?php echo _("AOR to use in trunk. This setting is automatically generated by the PBX if left blank").'<br/>'._("trunk_name")?></span>
                                </div>
                        </div>
                </div>
                <!--END AOR-->
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
					<span id="aor_contact-help" class="help-block fpbx-help-block">
						<?php echo _("Permanent contacts assigned to AoR. This setting is automatically generated by the PBX if left blank").'<br/>'._("sip:[username]@[ip]:[port]")?>
					</span>
				</div>
			</div>
		</div>
		<!--END AOR CONTACT-->
		<!--MATCH-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="match"><?php echo _("Match (Permit)")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="match"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="match" id="match" value="<?php echo $match?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="match-help" class="help-block fpbx-help-block"><?php echo _("IP addresses or networks to match against. The value is a comma-delimited list of IP addresses. IP addresses may have a subnet mask appended. The subnet mask may be written in either CIDR or dot-decimal notation. Separate the IP address and subnet mask with a slash ('/'). This setting is automatically generated by the PBX if left blank")?></span>
				</div>
			</div>
		</div><!--MATCH-->
		<!--Support Path-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="support_path"><?php echo _("Support Path") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="support_path"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="support_path" id="support_pathyes" value="yes" <?php echo ($support_path == "yes"?"CHECKED":"") ?>>
								<label for="support_pathyes"><?php echo _("Yes");?></label>
								<input type="radio" name="support_path" id="support_pathno" value="no" <?php echo ($support_path == "yes"?"":"CHECKED") ?>>
								<label for="support_pathno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="support_path-help" class="help-block fpbx-help-block"><?php echo _("When this option is enabled, outbound REGISTER requests will advertise support for Path headers so that intervening proxies can add to the Path header as necessary.")?></span>
				</div>
			</div>
		</div>
		<!--END Support Path-->
		<!--Support T.38 UDPTL-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="t38_udptl"><?php echo _("Support T.38 UDPTL") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="t38_udptl"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="t38_udptl" id="t38_udptlyes" value="yes" <?php echo ($t38_udptl == "yes"?"CHECKED":"") ?>>
								<label for="t38_udptlyes"><?php echo _("Yes");?></label>
								<input type="radio" name="t38_udptl" id="t38_udptlno" value="no" <?php echo ($t38_udptl == "yes"?"":"CHECKED") ?>>
								<label for="t38_udptlno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="t38_udptl-help" class="help-block fpbx-help-block"><?php echo _("Whether T.38 UDPTL support is enabled or not")?></span>
				</div>
			</div>
		</div>
		<!--END Support T.38 UDPTL-->
		<!--T.38 UDPTL Error Correction-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="t38_udptl_ec"><?php echo _("T.38 UDPTL Error Correction") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="t38_udptl_ec"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="t38_udptl_ec" id="t38_udptl_ecnone" value="none" <?php echo (!isset($t38_udptl_ec) || $t38_udptl_ec == "none" || $t38_udptl_ec == "" ?"CHECKED":"") ?>>
								<label for="t38_udptl_ecnone"><?php echo _("None");?></label>
								<input type="radio" name="t38_udptl_ec" id="t38_udptl_ecfec" value="fec" <?php echo ($t38_udptl_ec == "fec"?"CHECKED":"") ?>>
								<label for="t38_udptl_ecfec"><?php echo _("Forward");?></label>
								<input type="radio" name="t38_udptl_ec" id="t38_udptl_ecred" value="redundancy" <?php echo ($t38_udptl_ec == "redundancy"?"CHECKED":"") ?>>
								<label for="t38_udptl_ecred"><?php echo _("Redundancy");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="t38_udptl_ec-help" class="help-block fpbx-help-block"><?php echo _("T.38 UDPTL error correction method")?></span>
				</div>
			</div>
		</div>
		<!--END T.38 UDPTL Error Correction-->
		<!--T.38 UDPTL NAT-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="t38_udptl_nat"><?php echo _("T.38 UDPTL NAT") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="t38_udptl_nat"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="t38_udptl_nat" id="t38_udptl_natyes" value="yes" <?php echo ($t38_udptl_nat == "yes"?"CHECKED":"") ?>>
								<label for="t38_udptl_natyes"><?php echo _("Yes");?></label>
								<input type="radio" name="t38_udptl_nat" id="t38_udptl_natno" value = "no" <?php echo ($t38_udptl_nat == "yes"?"":"CHECKED") ?>>
								<label for="t38_udptl_natno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="t38_udptl_nat-help" class="help-block fpbx-help-block"><?php echo _("Whether NAT support is enabled on UDPTL sessions")?></span>
				</div>
			</div>
		</div>
		<!--END T.38 UDPTL NAT-->
		<!--T.38 UDPTL MAXDATAGRAM-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="t38_udptl_maxdatagram"><?php echo _("T.38 UDPTL MAXDATAGRAM") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="t38_udptl_maxdatagram"></i>
							</div>
							<div class="col-md-9">
								<input type="number" class="form-control" name="t38_udptl_maxdatagram" min=0 id="t38_udptl_maxdatagram" value="<?php echo $t38_udptl_maxdatagram?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="t38_udptl_maxdatagram-help" class="help-block fpbx-help-block"><?php echo _("T.38 UDPTL maximum datagram size.")?></span>
				</div>
			</div>
		</div>
		<!--END T.38 UDPTL MAXDATAGRAM-->
		<!--Fax Detect-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="fax_detect"><?php echo _("Fax Detect") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="fax_detect"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="fax_detect" id="fax_detectyes" value="yes" <?php echo ($fax_detect == "yes"?"CHECKED":"") ?>>
								<label for="fax_detectyes"><?php echo _("Yes");?></label>
								<input type="radio" name="fax_detect" id="fax_detectno" value="no" <?php echo ($fax_detect == "yes"?"":"CHECKED") ?>>
								<label for="fax_detectno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="fax_detect-help" class="help-block fpbx-help-block"><?php echo _("This option can be set to send the session to the fax extension when a CNG tone is detected.")?></span>
				</div>
			</div>
		</div>
		<!--END Fax Detect-->
		<!--Trust RPID/PAI-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="trust_rpid"><?php echo _("Trust RPID/PAI") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="trust_rpid"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="trust_rpid" id="trust_rpidyes" value="yes" <?php echo ($trust_rpid == "yes"?"CHECKED":"") ?>>
								<label for="trust_rpidyes"><?php echo _("Yes");?></label>
								<input type="radio" name="trust_rpid" id="trust_rpidno" value="no" <?php echo ($trust_rpid == "yes"?"":"CHECKED") ?>>
								<label for="trust_rpidno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="trust_rpid-help" class="help-block fpbx-help-block"><?php echo _("Trust the Remote-Party-ID and/or P-Asserted-Identity header")?></span>
				</div>
			 </div>
		</div>
		<!--END Trust RPID/PAI-->
		<!--Send RPID/PAI-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="sendrpid"><?php echo _("Send RPID/PAI") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="sendrpid"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="sendrpid" id="sendrpidno" value="no" <?php echo ($sendrpid == "no"?"CHECKED":"") ?>>
								<label for="sendrpidno"><?php echo _("No")?></label>
								<input type="radio" name="sendrpid" id="sendrpidyes" value="yes" <?php echo ($sendrpid == "yes"?"CHECKED":"") ?>>
								<label for="sendrpidyes"><?php echo _("Send Remote-Party-ID header")?></label>
								<input type="radio" name="sendrpid" id="sendrpidpai" value="pai" <?php echo ($sendrpid == "pai"?"CHECKED":"") ?>>
								<label for="sendrpidpai"><?php echo _("Send P-Asserted-Identity header")?></label>
								<input type="radio" name="sendrpid" id="sendrpidboth" value="both" <?php echo ($sendrpid == "both"?"CHECKED":"") ?>>
								<label for="sendrpidboth"><?php echo _("Both")?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="sendrpid-help" class="help-block fpbx-help-block"><?php echo _("Send the P-Asserted-Identity and/or Remote-Party-ID header")?></span>
				</div>
			</div>
		</div>
		<!--END Send RPID/PAI-->
		<!--Trust outbound CID-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="trust_id_outbound"><?php echo _("Send Private CallerID Information") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="trust_id_outbound"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="trust_id_outbound" id="trust_id_outboundyes" value="yes" <?php echo ($trust_id_outbound == "yes"?"CHECKED":"") ?>>
								<label for="trust_id_outboundyes"><?php echo _("Yes");?></label>
								<input type="radio" name="trust_id_outbound" id="trust_id_outboundno" value = "no" <?php echo ($trust_id_outbound == "yes"?"":"CHECKED") ?>>
								<label for="trust_id_outboundno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="trust_id_outbound-help" class="help-block fpbx-help-block"><?php echo _("Send private CallerID to outbound trust even when using hidden CallerID.  PJSIP trust_id_outbound")?></span>
				</div>
			</div>
		</div>
		<!--END Trust outbound CID-->
		<!--Match Inbound Authentication-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="identify_by"><?php echo _("Match Inbound Authentication") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="identify_by"></i>
							</div>
							<div class="col-md-9">
								<select name="identify_by" id="identify_by" class="form-control">
									<option value="default" <?php echo isset($identify_by) && $identify_by == "default"  ? "selected" : ""?>><?php echo _("Default")?></option>
									<option value="username" <?php echo isset($identify_by) && $identify_by == "username" ? "selected" : ""?>><?php echo _("Username")?></option>
									<option value="auth_username" <?php echo isset($identify_by) && $identify_by == "auth_username" ? "selected" : ""?>><?php echo _("Auth Username")?></option>
									<option value="ip" <?php echo isset($identify_by) && $identify_by == "ip" ? "selected" : ""?>><?php echo _("Ip")?></option>
									<option value="header" <?php echo isset($identify_by) && $identify_by == "header" ? "selected" : ""?>><?php echo _("Header")?></option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="identify_by-help" class="help-block fpbx-help-block"><?php echo _("Matches the endpoint based on the selected options.")?></span>
				</div>
			</div>
		</div>
		<!--END Match Inbound Authentication-->
		<!--Send Inband_progress-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="inband_progress"><?php echo _("Inband Progress") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="inband_progress"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="inband_progress" id="inband_progressyes" value="yes" <?php echo ($inband_progress == "yes"?"CHECKED":"") ?>>
								<label for="inband_progressyes"><?php echo _("Yes");?></label>
								<input type="radio" name="inband_progress" id="inband_progressno" value = "no" <?php echo ($inband_progress == "yes"?"":"CHECKED") ?>>
								<label for="inband_progressno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="inband_progress-help" class="help-block fpbx-help-block"><?php echo _("Determines whether chan_pjsip will indicate ringing using inband progress.")?></span>
				</div>
			</div>
		</div>
		<!--END inpand_progress-->
		<!--Send direct_media-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="direct_media"><?php echo _("Direct Media") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="direct_media"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="direct_media" id="direct_mediayes" value="yes" <?php echo ($direct_media == "yes"?"CHECKED":"") ?>>
								<label for="direct_mediayes"><?php echo _("Yes");?></label>
								<input type="radio" name="direct_media" id="direct_mediano" value = "no" <?php echo ($direct_media == "yes"?"":"CHECKED") ?>>
								<label for="direct_mediano"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="direct_media-help" class="help-block fpbx-help-block"><?php echo _("Determines whether media may flow directly between endpoints.")?></span>
				</div>
			</div>
		</div>
		<!--END direct_media-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="rrewrite_contact"><?php echo _("Rewrite Contact") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="rewrite_contact"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="rewrite_contact" id="rewrite_contactyes" value="yes" <?php echo ($rewrite_contact == "yes"?"CHECKED":"") ?>>
								<label for="rewrite_contactyes"><?php echo _("Yes");?></label>
								<input type="radio" name="rewrite_contact" id="rewrite_contactno" value = "no" <?php echo ($rewrite_contact == "yes"?"":"CHECKED") ?>>
								<label for="rewrite_contactno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="rewrite_contact-help" class="help-block fpbx-help-block"><?php echo _("Allow Contact header to be rewritten with the source IP address-port")?></span>
				</div>
			</div>
		</div>
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="rtp_symmetric"><?php echo _("RTP Symmetric") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="rtp_symmetric"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="rtp_symmetric" id="rtp_symmetricyes" value="yes" <?php echo ($rtp_symmetric == "yes"?"CHECKED":"") ?>>
								<label for="rtp_symmetricyes"><?php echo _("Yes");?></label>
								<input type="radio" name="rtp_symmetric" id="rtp_symmetricno" value = "no" <?php echo ($rtp_symmetric == "yes"?"":"CHECKED") ?>>
								<label for="rtp_symmetricno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="rtp_symmetric-help" class="help-block fpbx-help-block"><?php echo _("Enforce that RTP must be symmetric. This should almost always be on.")?></span>
				</div>
			</div>
		</div>
		<!--END direct_media-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label for="media_encryption"><?php echo _('Media Encryption')?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="media_encryption"></i>
							</div>
							<div class="col-md-9">
								<select name="media_encryption" class="form-control " id="media_encryption">
									<option value="no" <?php echo (empty($media_encryption) || $media_encryption == "no"?"selected":"")?>><?php echo _('None')?></option>
									<option value="sdes" <?php echo ($media_encryption == "sdes"?"selected":"")?>><?php echo _('SRTP via in-SDP (recommended)')?></option>
									<!--<option value="dtls">DTLS-SRTP (not recommended)</option>-->
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="media_encryption-help" class="help-block fpbx-help-block"><?php echo sprintf(_('Determines whether res_pjsip will use and enforce usage of media encryption for this endpoint. %s'),'[media_encryption]')?></span>
				</div>
			</div>
		</div>
		<!--MATCH-->
		<!--Send force_rport-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="force_rport"><?php echo _("Force rport") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="force_rport"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="force_rport" id="force_rportyes" value="yes" <?php echo ($force_rport != "no"?"CHECKED":"") ?>>
								<label for="force_rportyes"><?php echo _("Yes");?></label>
								<input type="radio" name="force_rport" id="force_rportno" value = "no" <?php echo ($force_rport == "no"?"CHECKED":"") ?>>
								<label for="force_rportno"><?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="force_rport-help" class="help-block fpbx-help-block"><?php echo _("Force RFC3581 compliant behavior even when no rport parameter exists. Basically always send SIP responses back to the same port we received SIP requests from.")?></span>
				</div>
			</div>
		</div>
		<!--END force_rport-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="message_context"><?php echo _("Message Context")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="message_context"></i>
							</div>
							<div class="col-md-9">
								<input type="text" class="form-control" name="message_context" id="message_context" value="<?php echo $message_context?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="message_context-help" class="help-block fpbx-help-block"><?php echo _("Context to route incoming MESSAGE requests to")?></span>
				</div>
			</div>
		</div><!--MATCH-->
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

$(document).ready(function() {
	// When the document is loaded, check the auth/reg buttons and update the inputs as required.
	checkAuthButtons();
	checkRegButtons();

	// Also check them whenever the Auth input is clicked.
	$("input[name=authentication]").click(function() { checkAuthButtons(); });

	// If someone tries to set registration to 'send' or 'recieve' with no auth,
	// don't let them.
	$("input[name=registration]").click(function(e) { checkRegButtons(e); });
});

function checkRegButtons(e) {
	var t;
	if (typeof e === "undefined") {
		t = $("input[name=registration]:checked");
	} else {
		t = $(e.target);
	}

	if ($("input[name=authentication]:checked").val() == "off") {
		$("#sip_server,#sip_server_port").prop("readonly", false);
		if (t.val() !== "none") {
			if (typeof e !== "undefined") {
				e.preventDefault();
			}
		}
	} else {
		if (t.val() === "receive") {
			$("#sip_server,#sip_server_port").prop("readonly", true);
		} else {
			$("#sip_server,#sip_server_port").prop("readonly", false);
		}
	}
}
function checkAuthButtons() {
	// If 'Authentication' is set to none, 'Registration' is set to none, and
	// username/secret is disabled.
	var a = $("input[name=authentication]:checked").val();
	if (a === "off") {
		$("#username,#secret,#auth_username").attr('placeholder', '<?php echo _("Authentication Disabled"); ?>');
		if ($("#username").val().length) {
			// It's set to something. Remove it.
			$("#username").data("origval", $("#username").val());
		}
		if ($("#auth_username").val().length) {
			// It's set to something. Remove it.
			$("#auth_username").data("origval", $("#auth_username").val());
		}
		if ($("#secret").val().length) {
			// It's set to something. Remove it.
			$("#secret").data("origval", $("#secret").val());
		}
		$("#secret").removeClass("clicktoedit");
		$("#username,#secret,#auth_username").val("").prop("readonly", true);
		$("#registrationnone").click();
	} else if (a === "inbound" || a === "both") {
		// Username is not settable, as it is the trunk name that is used for auth
		$("#secret").addClass("clicktoedit");
		$("#secret").attr('placeholder', '');
		if ($("#username").val().length) {
			// It's set to something. Remove it.
			$("#username").data("origval", $("#username").val());
		}
		if ($("#auth_username").val().length) {
			// It's set to something. Remove it.
			$("#auth_username").data("origval", $("#auth_username").val());
		}
		$("#username,#auth_username").attr('placeholder', '<?php echo _("username is trunk name"); ?>').prop("readonly", true).val("");
		$("#auth_username").attr('placeholder', '<?php echo _("Auth Username is trunk name"); ?>').prop("readonly", true).val("");
		$("#secret").prop("readonly", false).attr('placeholder', '');
		if (typeof $("#secret").data("origval") !== "undefined" && $("#secret").data("origval") !== false) {
			$("#secret").val($("#secret").data("origval"));
			$("#secret").data("origval", false);
		}
	} else {
		// Make sure they're not readonly...
		$("#secret").addClass("clicktoedit");
		$("#username,#secret,#auth_username").prop("readonly", false).attr('placeholder', '');
		// If they had anything previously, put them back.
		if (typeof $("#username").data("origval") !== "undefined" && $("#username").data("origval") !== false) {
			$("#username").val($("#username").data("origval"));
			$("#username").data("origval", false);
		}
		if (typeof $("#secret").data("origval") !== "undefined" && $("#secret").data("origval") !== false) {
			$("#secret").val($("#secret").data("origval"));
			$("#secret").data("origval", false);
		}
		if (typeof $("#auth_username").data("origval") !== "undefined" && $("#auth_username").data("origval") !== false) {
			$("#auth_username").val($("#auth_username").data("origval"));
			$("#auth_username").data("origval", false);
		}
	}
}

</script>
