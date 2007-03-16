<?php /* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';


//if submitting form, update database
if ($action == 'editglobals') {
	$globalfields = array(
						array($_REQUEST['RINGTIMER'],'RINGTIMER'),
						array($_REQUEST['FAX_RX'],'FAX_RX'),
						array($_REQUEST['FAX_RX_EMAIL'],'FAX_RX_EMAIL'),
						array($_REQUEST['FAX_RX_FROM'],'FAX_RX_FROM'),
						array($_REQUEST['DIRECTORY'],'DIRECTORY'),
						array($_REQUEST['VM_PREFIX'],'VM_PREFIX'),
						array($_REQUEST['VM_DDTYPE'],'VM_DDTYPE'),
						array($_REQUEST['VM_GAIN'],'VM_GAIN'),
						array($_REQUEST['DIAL_OPTIONS'],'DIAL_OPTIONS'),
						array($_REQUEST['TRUNK_OPTIONS'],'TRUNK_OPTIONS'),
						array($_REQUEST['TONEZONE'], 'TONEZONE'),
						array($_REQUEST['TIMEFORMAT'], 'TIMEFORMAT'),
						array($_REQUEST['ALLOW_SIP_ANON'], 'ALLOW_SIP_ANON'),
						array($_REQUEST['OPERATOR_XTN'], 'OPERATOR_XTN'),
						array(isset($_REQUEST['DIRECTORY_OPTS']) ? $_REQUEST['DIRECTORY_OPTS'] : "",'DIRECTORY_OPTS'),
						array(isset($_REQUEST['VM_OPTS']) ? $_REQUEST['VM_OPTS'] : "",'VM_OPTS'),

						array(isset($_REQUEST['VMX_CONTEXT']) ? $_REQUEST['VMX_CONTEXT'] : "",'VMX_CONTEXT'),
						array(isset($_REQUEST['VMX_PRI']) ? $_REQUEST['VMX_PRI'] : "",'VMX_PRI'),
						array(isset($_REQUEST['VMX_TIMEDEST_CONTEXT']) ? $_REQUEST['VMX_TIMEDEST_CONTEXT'] : "",'VMX_TIMEDEST_CONTEXT'),
						array(isset($_REQUEST['VMX_TIMEDEST_EXT']) ? $_REQUEST['VMX_TIMEDEST_EXT'] : "",'VMX_TIMEDEST_EXT'),
						array(isset($_REQUEST['VMX_TIMEDEST_PRI']) ? $_REQUEST['VMX_TIMEDEST_PRI'] : "",'VMX_TIMEDEST_PRI'),
						array(isset($_REQUEST['VMX_LOOPDEST_CONTEXT']) ? $_REQUEST['VMX_LOOPDEST_CONTEXT'] : "",'VMX_LOOPDEST_CONTEXT'),
						array(isset($_REQUEST['VMX_LOOPDEST_EXT']) ? $_REQUEST['VMX_LOOPDEST_EXT'] : "",'VMX_LOOPDEST_EXT'),
						array(isset($_REQUEST['VMX_LOOPDEST_PRI']) ? $_REQUEST['VMX_LOOPDEST_PRI'] : "",'VMX_LOOPDEST_PRI'),
						array(isset($_REQUEST['VMX_OPTS_TIMEOUT']) ? $_REQUEST['VMX_OPTS_TIMEOUT'] : "",'VMX_OPTS_TIMEOUT'),
						array(isset($_REQUEST['VMX_OPTS_LOOP']) ? $_REQUEST['VMX_OPTS_LOOP'] : "",'VMX_OPTS_LOOP'),
						array(isset($_REQUEST['VMX_OPTS_DOVM']) ? $_REQUEST['VMX_OPTS_DOVM'] : "",'VMX_OPTS_DOVM'),
						array(isset($_REQUEST['VMX_TIMEOUT']) ? $_REQUEST['VMX_TIMEOUT'] : "",'VMX_TIMEOUT'),
						array(isset($_REQUEST['VMX_REPEAT']) ? $_REQUEST['VMX_REPEAT'] : "",'VMX_REPEAT'),
						array(isset($_REQUEST['VMX_LOOPS']) ? $_REQUEST['VMX_LOOPS'] : "",'VMX_LOOPS'),
						);

	$compiled = $db->prepare('UPDATE globals SET value = ? WHERE variable = ?');
	//$compiled = $db->prepare('REPLACE INTO globals (value,variable) VALUES (?, ?)');
	$result = $db->executeMultiple($compiled,$globalfields);
	if(DB::IsError($result)) {
		echo $action.'<br>';
		die($result->getMessage());
	}
	
	//indicate 'need reload' link in header.php 
	needreload();
}
	
//get all rows relating to selected account
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
die($globals->getMessage());
}

//create a set of variables that match the items in global[0]
foreach ($globals as $global) {
	${trim($global[0])} = $global[1];	
}

?>

<form name="general" action="config.php" method="post" onsubmit="return general_onsubmit();">
<input type="hidden" name="display" value="general"/>
<input type="hidden" name="action" value="editglobals"/>

<h5><?php echo _("Dialing Options")?></h5>
<p>
	<a href=# class="info"><?php echo _("Asterisk Dial command options:")?><span><br>
<?php echo _("t: Allow the called user to transfer the call by hitting #")?><br>
<?php echo _("T: Allow the calling user to transfer the call by hitting #")?><br>
<?php echo _("r: Generate a ringing tone for the calling party")?><br>
<?php echo _("w: Allow the called user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("W: Allow the calling user to start recording after pressing *1 (Asterisk v1.2)")?><br>
	</span></a>
	<input type="text" size="2" name="DIAL_OPTIONS" value="<?php  echo htmlspecialchars($DIAL_OPTIONS)?>"/>
	<br><br>
	<a href=# class="info"><?php echo _("Asterisk Outbound Dial command options:")?><span><br>
<?php echo _("t: Allow the called user to transfer the call by hitting #")?><br>
<?php echo _("T: Allow the calling user to transfer the call by hitting #")?><br>
<?php echo _("w: Allow the called user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("W: Allow the calling user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("r: You SHOULD NOT use this option on outbound trunks")?><br>
	</span></a>
	<input type="text" size="2" name="TRUNK_OPTIONS" value="<?php  echo htmlspecialchars($TRUNK_OPTIONS)?>"/>
</p>

<h5><?php echo _("Voicemail")?></h5>
<p>
	<?php echo _("Number of seconds to ring phones before sending callers to voicemail:")?>
	<input type="text" size="2" name="RINGTIMER" value="<?php  echo htmlspecialchars($RINGTIMER)?>"/>
	<br><br>
	<?php echo _("Extension prefix for dialing direct to voicemail:")?>
	<input type="text" size="2" name="VM_PREFIX" value="<?php  echo htmlspecialchars($VM_PREFIX)?>"/>
	<br><br>
	<?php echo _("Direct Dial to Voicemail message type:")?>
	<select name="VM_DDTYPE">
	<option value=""><?php echo _("Default"); ?></option>
	<option value="u"<?php if ($VM_DDTYPE == "u") echo " SELECTED"; ?>><?php echo _("Unavailable"); ?></option>
	<option value="su"<?php if ($VM_DDTYPE == "su") echo " SELECTED"; ?>><?php echo _("Unavailable")."--"._("no instructions"); ?></option>
	<option value="b"<?php if ($VM_DDTYPE == "b") echo " SELECTED"; ?>><?php echo _("Busy"); ?></option>
	<option value="sb"<?php if ($VM_DDTYPE == "sb") echo " SELECTED"; ?>><?php echo _("Busy")."--"._("no instructions"); ?></option>
	<option value="s"<?php if ($VM_DDTYPE == "s") echo " SELECTED"; ?>><?php echo ("No Message"); ?></option>
	</select>
	<br><br>
	<a href=# class="info"><?php echo _("Use gain when recording the voicemail message (optional):")?><span>
	<?php echo _("Use the specified amount of gain when recording the voicemail message."); ?><br><br>
	<?php echo _("The units are whole-number decibels (dB)."); ?></span></a>
	<input type="text" size="2" name="VM_GAIN" value="<?php  echo htmlspecialchars($VM_GAIN)?>"/>
	<br><br>
	<input type="checkbox" value="s" name="VM_OPTS" <?php  echo ($VM_OPTS ? 'CHECKED' : '')?>> <a href=# class="info"><?php echo _("Do Not Play")?><span><?php echo _("Check this to remove the default message \"Please leave your message after the tone. When done, hang-up, or press the pound key.\" That is played after the voicemail greeting (the s option). This applies globally to all vm boxes.")?></span></a> <?php echo _("please leave message after tone to caller")?>
</p>

<h5><?php echo _("Voicemail VmX Locator")?></h5>
	<table>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Default Context & Pri:")?><span>
			<?php echo _("Default to use if only an number/extension are provided."); ?></span></a>
			</td>
			<td><input type="text" size="18" name="VMX_CONTEXT" value="<?php  echo htmlspecialchars($VMX_CONTEXT)?>"/><small><?php echo _("context")?></small></td>
			<td></td>
			<td><input type="text" size="2" name="VMX_PRI" value="<?php  echo htmlspecialchars($VMX_PRI)?>"/><small><?php echo _("pri")?></small></td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Timeout/#-press default:")?><span>
			<?php echo _("This is the default location that a caller will be sent if they don't press any key (timeout) or press # which is interpreted as a timeout. Set this to 'dovm' to go to voicemail (default)."); ?></span></a>
			</td>
			<td><input type="text" size="18" name="VMX_TIMEDEST_CONTEXT" value="<?php  echo htmlspecialchars($VMX_TIMEDEST_CONTEXT)?>"/><small><?php echo _("context")?></small></td>
			<td><input type="text" size="4" name="VMX_TIMEDEST_EXT" value="<?php  echo htmlspecialchars($VMX_TIMEDEST_EXT)?>"/><small><?php echo _("exten")?></small></td>
			<td><input type="text" size="2" name="VMX_TIMEDEST_PRI" value="<?php  echo htmlspecialchars($VMX_TIMEDEST_PRI)?>"/><small><?php echo _("pri")?></small></td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Loop Exceed default:")?><span>
			<?php echo _("This is the default location that a caller will be sent if they press an invalid options too man times, as defined by the Maximum Loops count. Set this to 'dovm' to go to voicemail (default)."); ?></span></a>
			</td>
			<td><input type="text" size="18" name="VMX_LOOPDEST_CONTEXT" value="<?php  echo htmlspecialchars($VMX_LOOPDEST_CONTEXT)?>"/><small><?php echo _("context")?></small></td>
			<td><input type="text" size="4" name="VMX_LOOPDEST_EXT" value="<?php  echo htmlspecialchars($VMX_LOOPDEST_EXT)?>"/><small><?php echo _("exten")?></small></td>
			<td><input type="text" size="2" name="VMX_LOOPDEST_PRI" value="<?php  echo htmlspecialchars($VMX_LOOPDEST_PRI)?>"/><small><?php echo _("pri")?></small></td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Timeout VM Msg:")?><span>
			<?php echo _("If this destination is voicemail, select whether or not to play the standard voicemail instructions or just beep."); ?></span></a>
			</td>
			<td align=right>
			<select name="VMX_OPTS_TIMEOUT">
				<option value=""  <?php  echo ($VMX_OPTS_TIMEOUT == '' ? 'SELECTED' : '') ?>><?php echo _("Std Instrucitons")?>
				<option value="s" <?php  echo ($VMX_OPTS_TIMEOUT == 's' ? 'SELECTED' : '')?>><?php echo _("Beep Only")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Max Loop VM Msg:")?><span>
			<?php echo _("If this destination is voicemail, select whether or not to play the standard voicemail instructions or just beep."); ?></span></a>
			</td>
			<td align=right>
			<select name="VMX_OPTS_LOOP">
				<option value=""  <?php  echo ($VMX_OPTS_LOOP == '' ? 'SELECTED' : '') ?>><?php echo _("Std Instrucitons")?>
				<option value="s" <?php  echo ($VMX_OPTS_LOOP == 's' ? 'SELECTED' : '')?>><?php echo _("Beep Only")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Direct VM Option")?><span>
			<?php echo _("If a user defined option is to go to voicmail (using the 'dovm' extension) this is the default option if not specified by the user's settings."); ?></span></a>
			</td>
			<td align=right>
			<select name="VMX_OPTS_DOVM">
				<option value=""  <?php  echo ($VMX_OPTS_DOVM == '' ? 'SELECTED' : '') ?>><?php echo _("Std Instrucitons")?>
				<option value="s" <?php  echo ($VMX_OPTS_DOVM == 's' ? 'SELECTED' : '')?>><?php echo _("Beep Only")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Msg Timeout:")?><span>
			<?php echo _("Time ot wait after message has played to timeout and/or repeat the message if no entry pressed."); ?></span></a>
			</td>
			<td align=right>
			<select name="VMX_TIMEOUT">
<?php
			for ($i=0;$i<16;$i++) { 
				$VMX_TIMEOUT = (!isset($VMX_TIMEOUT) || $VMX_TIMEOUT == '')?2:$VMX_TIMEOUT;
?>
				<option value="<?php echo $i?>"  <?php  echo ($VMX_TIMEOUT == $i ? 'SELECTED' : '') ?>><?php echo $i?>
<?php
				}
?>
			</select></td><td><small><?php echo _("seconds")?></small>
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Msg Play:")?><span>
			<?php echo _("Number of times to play the recorded message if the caller does not press any options and it times out."); ?></span></a>
			</td>
			<td align=right>
			<select name="VMX_REPEAT">
<?php
			for ($i=1;$i<5;$i++) { 
				$VMX_REPEAT = (!isset($VMX_REPEAT) || $VMX_REPEAT == '')?1:$VMX_REPEAT;
?>
				<option value="<?php echo $i?>"  <?php  echo ($VMX_REPEAT == $i ? 'SELECTED' : '') ?>><?php echo $i?>
<?php
				}
?>
			</select></td><td><small><?php echo _("times")?></small> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Error Re-tries:")?><span>
			<?php echo _("Number of times to play invalid options and repeat the message upon receiving an undefined option."); ?></span></a>
			</td>
			<td align=right>
			<select name="VMX_LOOPS">
<?php
			for ($i=1;$i<5;$i++) { 
				$VMX_REPEAT = (!isset($VMX_LOOPS) || $VMX_LOOPS == '')?1:$VMX_LOOPS;
?>
				<option value="<?php echo $i?>"  <?php  echo ($VMX_LOOPS == $i ? 'SELECTED' : '') ?>><?php echo $i?>
<?php
				}
?>
			</select></td><td><small><?php echo _("times")?></small> 
			</td>
		</tr>
	</table>
<p>

</p>

<h5><?php echo _("Company Directory")?></h5>
<p>
	<?php echo _("Find users in the Company Directory by:")?>
	<select name="DIRECTORY">
		<option value="first" <?php  echo ($DIRECTORY == 'first' ? 'SELECTED' : '')?>><?php echo _("first name")?>
		<option value="last" <?php  echo ($DIRECTORY == 'last' ? 'SELECTED' : '')?>><?php echo _("last name")?>
		<option value="both" <?php  echo ($DIRECTORY == 'both' ? 'SELECTED' : '')?>><?php echo _("first or last name")?>
	</select> 
	<br><br>
	<input type="checkbox" value="e" name="DIRECTORY_OPTS" <?php  echo ($DIRECTORY_OPTS ? 'CHECKED' : '')?>> <a href=# class="info"><?php echo _("Play extension number")?><span><?php echo _("Plays a message \"Please hold while I transfer you to extension xxx\" that lets the caller know what extension to use in the future.")?></span></a> <?php echo _("to caller before transferring call")?><br><br>
	<a href=# class="info"><?php echo _("Operator Extension:")?><span>
	<?php echo _("When users hit '0' in the directory, they are put through to this number. Note that it"); ?>
	<?php echo _(" does NOT need to be an extension, it can be a Ring Group, or even an external number."); ?></span></a>
	<input type="text" size="10" name="OPERATOR_XTN" value="<?php  echo htmlspecialchars($OPERATOR_XTN)?>"/>
</p>
<h5><?php echo _("Fax Machine")?></h5>
<p>
	<?php echo _("Extension of")?> <a class="info" href="#"><?php echo _("fax machine")?><span><?php echo _("Select 'system' to have the system receive and email faxes.<br>Selecting 'disabled' will result in incoming calls being answered more quickly.")?></span></a> <?php echo _("for receiving faxes:")?>
	<!--<input type="text" size="8" name="FAX_RX" value="<?php  echo $FAX_RX?>"/>-->
	<select name="FAX_RX">
		<option value="disabled" <?php  echo ($FAX_RX == 'disabled' ? 'SELECTED' : '')?>><?php echo _("disabled")?>
		<option value="system" <?php  echo ($FAX_RX == 'system' ? 'SELECTED' : '')?>><?php echo _("system")?>
<?php 
	//get unique devices
	$devices = core_devices_list();
	if (isset($devices)) {
		foreach ($devices as $device) {
			echo '<option value="'.$device[0].'" '.($FAX_RX == $device[0] ? 'SELECTED' : '').'>'.$device[1].' &lt;'.$device[0].'&gt;';
		}
	}
?>	
	</select>
	
</p>
<p>
	<a class="info" href="#"><?php echo _("Email address")?><span><?php echo _("Email address used if 'system' has been chosen for the fax extension above.")?></span></a> <?php echo _("to have faxes emailed to:")?>
	<input type="text" size="20" name="FAX_RX_EMAIL" value="<?php  echo htmlspecialchars($FAX_RX_EMAIL)?>"/>
</p>
<p>
	<a class="info" href="#"><?php echo _("Email address")?><span><?php echo _("Email address that faxes appear to come from if 'system' has been chosen for the fax extension above.")?></span></a> <?php echo _("that faxes appear to come from:")?>
	<input type="text" size="20" name="FAX_RX_FROM" value="<?php  echo htmlspecialchars($FAX_RX_FROM)?>"/>
</p>
<h5><?php echo _("International Settings")?></h5>
<p>
	<?php echo _("Country")?> <a class="info" href="#"><?php echo _("Indications")?><span><?php echo _("Select which country you are in")?></span></a>&nbsp;
	<?php 	if (isset($TONEZONE) && strlen($TONEZONE)) 
		general_display_zones($TONEZONE); 
		else
		general_display_zones('us'); 
		?>
</p>
<p>
	<a class="info" href="#"><?php echo _("24-hour format")?><span><?php echo _("Select Yes if you use 24-hour format or No if you are using 12-hour am/pm format")?></span></a>&nbsp;
	<select name="TIMEFORMAT">
		<option value="IMp"><?php echo _("no"); ?></option>
		<option value="kM" <?php echo (($TIMEFORMAT=="kM") ? 'selected="selected"' : '');?>><?php echo _("yes"); ?></option>
	</select>
</p>
<h5><?php echo _("Security Settings")?></h5>
<p>
	<a href=# class="info"><?php echo _("Allow Anonymous Inbound SIP Calls?")?><span><br>
<?php echo _("** WARNING **")?><br><br>
<?php echo _("Setting this to 'yes' will potentially allow ANYBODY to call into your Asterisk server using the SIP protocol")?><br><br>
<?php echo _("It should only be used if you fully understand the impact of allowing anonymous calls into your server")?><br>
	</span></a>&nbsp;
	<select name="ALLOW_SIP_ANON">
	<option value="no"><?php echo _("no"); ?></option>
	<option <?php if ($ALLOW_SIP_ANON == "yes") echo "SELECTED "?>value="yes"><?php echo _("yes"); ?></option>
	</select>
</p>
<h6>
	<input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>">
</h6>
<script language="javascript">
<!--

var theForm = document.general;

function hasRing (s) {
	if (s.indexOf('r') >= 0) {
		return true;
	} else {
		return false;
	}
}

function warnConfirm (theField, s) {
    theField.focus();
    theField.select();
		return confirm(s);
}

function general_onsubmit() {
	var msgInvalidSeconds = "<?php echo _('Please enter a valid Number of Seconds'); ?>";
	var msgInvalidDefaultFaxEmail = "<?php echo _('Please enter a valid Fax Email'); ?>";
	var msgRingOptionOutboundTrunk = "<?php echo _('You have selected the \'r\' option for your trunks. This is highly discouraged and will create problems with calls on many PRI, VoIP, ISDN and other trunks that are capable of signalling. Asterisk will generate a ringing tone until the signalling indicates the line is answered. This will result in some external IVRs being inaccessible and other strange problems.'); ?>";

	defaultEmptyOK = false;
	if (!isInteger(theForm.RINGTIMER.value))
		return warnInvalid(theForm.RINGTIMER, msgInvalidSeconds);

	// Fax email must be present if selected 'system', otherwise optional
	if (theForm.FAX_RX.value == "system") {
		defaultEmptyOK = false;
	} else {
		defaultEmptyOK = true;
	}
	if (!isEmail(theForm.FAX_RX_EMAIL.value))
		return warnInvalid(theForm.FAX_RX_EMAIL, msgInvalidDefaultFaxEmail);

	if (hasRing(theForm.TRUNK_OPTIONS.value))
		return warnConfirm(theForm.TRUNK_OPTIONS, msgRingOptionOutboundTrunk);
	
	return true;
}

//-->
</script>
</form>

