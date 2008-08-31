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

$cm =& cronmanager::create($db);


//if submitting form, update database
if ($action == 'editglobals') {
	$globalfields = array(
	          array($_REQUEST['RECORDING_STATE'],'RECORDING_STATE'),
	          array($_REQUEST['MIXMON_FORMAT'],'MIXMON_FORMAT'),
						array($_REQUEST['MIXMON_DIR'],'MIXMON_DIR'),
						array($_REQUEST['MIXMON_POST'],'MIXMON_POST'),
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
		die_freepbx($result->getMessage());
	}
	
	/* update online updates and email as appropriate
	*/
	$online_updates = isset($_REQUEST['online_updates'])? $_REQUEST['online_updates'] : 'yes';
	$update_email   = isset($_REQUEST['update_email'])  ? $_REQUEST['update_email']   : '';

	if ($online_updates == 'yes') {
		$cm->enable_updates();
	} else {
		$cm->disable_updates();
	}

	// TODO: maybe check the email address a bit better server/client side
	//
	$cm->save_email($update_email);
	
	//indicate 'need reload' link in header.php 
	needreload();
}

$online_updates = $cm->updates_enabled() ? 'yes' : 'no';
$update_email   = $cm->get_email();

//get all rows relating to selected account
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
die_freepbx($globals->getMessage());
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
<table>
	<tr><td>
	<a href=# class="info"><?php echo _("Asterisk Dial command options:")?><span><br>
<?php echo _("t: Allow the called user to transfer the call by hitting #")?><br>
<?php echo _("T: Allow the calling user to transfer the call by hitting #")?><br>
<?php echo _("r: Generate a ringing tone for the calling party")?><br>
<?php echo _("w: Allow the called user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("W: Allow the calling user to start recording after pressing *1 (Asterisk v1.2)")?><br>
	</span></a>
	</td><td align="right">
	<input type="text" size="10" name="DIAL_OPTIONS" value="<?php  echo htmlspecialchars($DIAL_OPTIONS)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
	<tr><td>
	<a href=# class="info"><?php echo _("Asterisk Outbound Dial command options:")?><span><br>
<?php echo _("t: Allow the called user to transfer the call by hitting #")?><br>
<?php echo _("T: Allow the calling user to transfer the call by hitting #")?><br>
<?php echo _("w: Allow the called user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("W: Allow the calling user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("r: You SHOULD NOT use this option on outbound trunks")?><br>
	</span></a>
	</td><td align="right">
	<input type="text" size="10" name="TRUNK_OPTIONS" value="<?php  echo htmlspecialchars($TRUNK_OPTIONS)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
</table>

<h5><?php echo _("Call Recording")?></h5>
<table>
	<tr><td>
	<a href=# class="info"><?php echo _("Extension Recording Override:")?><span>
	<?php echo _("This will override the recording settings of all extensions/users. If disabled, the system will ignore all Record Always settings of a user and will not turn on recording. This does not effect On Demand recording controlled by the dial options 'w' and 'W' above. It does not effect other recording settings in modules such as Queues and Conferences. If you don't use recordings, setting this is beneficial to system performance as it removes the check that is otherwise done on every single call."); ?></span></a>
	</td><td align="right">
	<select name="RECORDING_STATE" tabindex="<?php echo ++$tabindex;?>">
		<option value="ENABLED"  <?php  echo ($RECORDING_STATE == 'ENABLED' ? 'SELECTED' : '')?>><?php echo _("Enabled")?>
		<option value="DISABLED"   <?php  echo ($RECORDING_STATE == 'DISABLED'  ? 'SELECTED' : '')?>><?php echo _("Disabled")?>
	</select>
	</td></tr>
	<tr><td>
  <a href=# class="info"><?php echo _("Call recording format:")?><span><br>
  <?php echo _("Pick the format in which to save recorded calls")?><br>
  </span></a>
	</td><td align="right">
  <select name="MIXMON_FORMAT" tabindex="<?php echo ++$tabindex;?>">
  <option value="WAV"<?php if ($MIXMON_FORMAT == "WAV") echo " SELECTED"; ?>><?php echo _("WAV"); ?></option>
  <option value="wav"<?php if ($MIXMON_FORMAT == "wav") echo " SELECTED"; ?>><?php echo _("wav"); ?></option>
  <option value="ulaw"<?php if ($MIXMON_FORMAT == "ulaw") echo " SELECTED"; ?>><?php echo _("ulaw"); ?></option>
  <option value="alaw"<?php if ($MIXMON_FORMAT == "alaw") echo " SELECTED"; ?>><?php echo _("alaw"); ?></option>
  <option value="sln"<?php if ($MIXMON_FORMAT == "sln") echo " SELECTED"; ?>><?php echo _("sln"); ?></option>
  <option value="gsm"<?php if ($MIXMON_FORMAT == "gsm") echo " SELECTED"; ?>><?php echo _("gsm"); ?></option>
  <option value="g729"<?php if ($MIXMON_FORMAT == "g729") echo " SELECTED"; ?>><?php echo _("g729"); ?></option>
  </select>
	</td></tr>
	<tr><td>
  <a href=# class="info"><?php echo _("Recording Location:")?><span><br>
  <?php echo _("Override the default location where asterisk will store call recordings. Include the trailing /. Be sure to set proper permissions on the directory for the asterisk user.")?><br>
  </span></a>
	</td><td align="right">
  <input type="text" size="30" name="MIXMON_DIR" value="<?php  echo htmlspecialchars($MIXMON_DIR)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
	<tr><td>
  <a href=# class="info"><?php echo _("Run after record:")?><span><br>
  <?php echo _("An optional script to be run after the call is hungup. You can include channel and MixMon variables like \${CALLFILENAME}, \${MIXMON_FORMAT} and \${MIXMON_DIR}. To ensure that you variables are properly escaped, use the following notation: ^{MY_VAR}")?><br>
  </span></a>
	</td><td align="right">
  <input type="text" size="30" name="MIXMON_POST" value="<?php  echo htmlspecialchars($MIXMON_POST)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
</table>

<h5><?php echo _("Voicemail")?></h5>
<table>
	<tr><td>
  <a href=# class="info"><?php echo _("Ringtime Default:")?><span><br>
  <?php echo _("Default number of seconds to ring phones before sending callers to voicemail. This can be set per extension/user and will have no effect on phones with no voicemail.")?><br>
  </span></a>
	</td><td align="right">
	<input type="text" size="2" name="RINGTIMER" value="<?php  echo htmlspecialchars($RINGTIMER)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
	<tr><td>
  <a href=# class="info"><?php echo _("Direct Dial Voicemail Prefix:")?><span><br>
  <?php echo _("Prefix used to dial directly to someone's voicemail. Caution should be taken in choosing this prefix to avoid conflicts with featurecodes.")?><br>
  </span></a>
	</td><td align="right">
	<input type="text" size="2" name="VM_PREFIX" value="<?php  echo htmlspecialchars($VM_PREFIX)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
	<tr><td>
	<?php echo _("Direct Dial to Voicemail message type:")?>
	</td><td align="right">
	<select name="VM_DDTYPE" tabindex="<?php echo ++$tabindex;?>">
	<option value=""><?php echo _("Default"); ?></option>
	<option value="u"<?php if ($VM_DDTYPE == "u" || $VM_DDTYPE == "su") echo " SELECTED"; ?>><?php echo _("Unavailable"); ?></option>
	<option value="b"<?php if ($VM_DDTYPE == "b" || $VM_DDTYPE == "sb") echo " SELECTED"; ?>><?php echo _("Busy"); ?></option>
	<option value="s"<?php if ($VM_DDTYPE == "s") echo " SELECTED"; ?>><?php echo ("No Message"); ?></option>
	</select>
	</td></tr>
	<tr><td>
	<a href=# class="info"><?php echo _("Optional Voicemail Recording Gain:")?><span>
	<?php echo _("Use the specified amount of gain when recording the voicemail message."); ?><br><br>
	<?php echo _("The units are whole-number decibels (dB)."); ?></span></a>
	</td><td align="right">
	<input type="text" size="2" name="VM_GAIN" value="<?php  echo htmlspecialchars($VM_GAIN)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
	<tr><td>
	<a href=# class="info"><?php echo _("Do Not Play")?><span><?php echo _("Check this to remove the default message \"Please leave your message after the tone. When done, hang-up, or press the pound key.\" That is played after the voicemail greeting (the s option). This applies globally to all vm boxes.")?></span></a> <?php echo _("\"please leave message after tone to caller\"")?>
	</td><td align="right">
	<input type="checkbox" value="s" name="VM_OPTS" <?php  echo (($VM_OPTS || $VM_DDTYPE == "su" || $VM_DDTYPE == "bu") ? 'CHECKED' : '')?> tabindex="<?php echo ++$tabindex;?>"> 
	</td></tr>
</table>

<h5><?php echo _("Voicemail VmX Locator")?></h5>
	<table>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Default Context & Pri:")?><span>
			<?php echo _("Default to use if only a number/extension are provided."); ?></span></a>
			</td>
			<td align="right"><input type="text" size="18" name="VMX_CONTEXT" value="<?php  echo htmlspecialchars($VMX_CONTEXT)?>" tabindex="<?php echo ++$tabindex;?>"/>&nbsp;<small><?php echo _("context")?></small>&nbsp;</td>
			<td colspan="2" align="right"><input type="text" size="2" name="VMX_PRI" value="<?php  echo htmlspecialchars($VMX_PRI)?>" tabindex="<?php echo ++$tabindex;?>"/></td><td><small><?php echo _("pri")?></small></td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Timeout/#-press default:")?><span>
			<?php echo _("This is the default location that a caller will be sent if they don't press any key (timeout) or press # which is interpreted as a timeout. Set this to 'dovm' to go to voicemail (default)."); ?></span></a>
			</td>
			<td align="right"><input type="text" size="18" name="VMX_TIMEDEST_CONTEXT" value="<?php  echo htmlspecialchars($VMX_TIMEDEST_CONTEXT)?>" tabindex="<?php echo ++$tabindex;?>"/>&nbsp;<small><?php echo _("context")?></small>&nbsp;</td>
			<td align="right"><input type="text" size="4" name="VMX_TIMEDEST_EXT" value="<?php  echo htmlspecialchars($VMX_TIMEDEST_EXT)?>" tabindex="<?php echo ++$tabindex;?>"/>&nbsp;<small><?php echo _("exten")?></small>&nbsp;</td>
			<td align="right"><input type="text" size="2" name="VMX_TIMEDEST_PRI" value="<?php  echo htmlspecialchars($VMX_TIMEDEST_PRI)?>" tabindex="<?php echo ++$tabindex;?>"/></td><td><small><?php echo _("pri")?></small></td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Loop Exceed default:")?><span>
			<?php echo _("This is the default location that a caller will be sent if they press an invalid options too man times, as defined by the Maximum Loops count. Set this to 'dovm' to go to voicemail (default)."); ?></span></a>
			</td>
			<td align="right"><input type="text" size="18" name="VMX_LOOPDEST_CONTEXT" value="<?php  echo htmlspecialchars($VMX_LOOPDEST_CONTEXT)?>" tabindex="<?php echo ++$tabindex;?>"/>&nbsp;<small><?php echo _("context")?></small>&nbsp;</td>
			<td align="right"><input type="text" size="4" name="VMX_LOOPDEST_EXT" value="<?php  echo htmlspecialchars($VMX_LOOPDEST_EXT)?>" tabindex="<?php echo ++$tabindex;?>"/>&nbsp;<small><?php echo _("exten")?></small>&nbsp;</td>
			<td align="right"><input type="text" size="2" name="VMX_LOOPDEST_PRI" value="<?php  echo htmlspecialchars($VMX_LOOPDEST_PRI)?>" tabindex="<?php echo ++$tabindex;?>"/></td><td><small><?php echo _("pri")?></small></td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Timeout VM Msg:")?><span>
			<?php echo _("If this destination is voicemail, select whether or not to play the standard voicemail instructions or just beep."); ?></span></a>
			</td>
			<td colspan="3" align="right">
			<select name="VMX_OPTS_TIMEOUT" tabindex="<?php echo ++$tabindex;?>">
				<option value=""  <?php  echo ($VMX_OPTS_TIMEOUT == '' ? 'SELECTED' : '') ?>><?php echo _("Std Instructions")?>
				<option value="s" <?php  echo ($VMX_OPTS_TIMEOUT == 's' ? 'SELECTED' : '')?>><?php echo _("Beep Only")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Max Loop VM Msg:")?><span>
			<?php echo _("If this destination is voicemail, select whether or not to play the standard voicemail instructions or just beep."); ?></span></a>
			</td>
			<td colspan="3" align="right">
			<select name="VMX_OPTS_LOOP" tabindex="<?php echo ++$tabindex;?>">
				<option value=""  <?php  echo ($VMX_OPTS_LOOP == '' ? 'SELECTED' : '') ?>><?php echo _("Std Instructions")?>
				<option value="s" <?php  echo ($VMX_OPTS_LOOP == 's' ? 'SELECTED' : '')?>><?php echo _("Beep Only")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Direct VM Option")?><span>
			<?php echo _("If a user defined option is to go to voicemail (using the 'dovm' extension) this is the default option if not specified by the user's settings."); ?></span></a>
			</td>
			<td colspan="3" align="right">
			<select name="VMX_OPTS_DOVM" tabindex="<?php echo ++$tabindex;?>">
				<option value=""  <?php  echo ($VMX_OPTS_DOVM == '' ? 'SELECTED' : '') ?>><?php echo _("Std Instructions")?>
				<option value="s" <?php  echo ($VMX_OPTS_DOVM == 's' ? 'SELECTED' : '')?>><?php echo _("Beep Only")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Msg Timeout:")?><span>
			<?php echo _("Time ot wait after message has played to timeout and/or repeat the message if no entry pressed."); ?></span></a>
			</td>
			<td colspan="3" align="right">
			<select name="VMX_TIMEOUT" tabindex="<?php echo ++$tabindex;?>">
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
			<td colspan="3" align="right">
			<select name="VMX_REPEAT" tabindex="<?php echo ++$tabindex;?>">
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
			<td colspan="3" align="right">
			<select name="VMX_LOOPS" tabindex="<?php echo ++$tabindex;?>">
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

<h5><?php echo _("Company Directory")?></h5>
<table>
	<tr><td>
	<a href=# class="info"><?php echo _("Find users in the Company Directory by:")?><span><?php echo _("The Company Directory allows a caller to spell the user's first name, last name, or both when searching for a user. This will select which of these modes are used.")?></span></a>
	</td><td align="right">
	<select name="DIRECTORY" tabindex="<?php echo ++$tabindex;?>">
		<option value="first" <?php  echo ($DIRECTORY == 'first' ? 'SELECTED' : '')?>><?php echo _("first name")?>
		<option value="last" <?php  echo ($DIRECTORY == 'last' ? 'SELECTED' : '')?>><?php echo _("last name")?>
		<option value="both" <?php  echo ($DIRECTORY == 'both' ? 'SELECTED' : '')?>><?php echo _("first or last name")?>
	</select> 
	</td></tr>
	<tr><td>
	<a href=# class="info"><?php echo _("Announce Extension:")?><span><?php echo _("Plays a message \"Please hold while I transfer you to extension xxx\" that lets the caller know what extension to use in the future when connecting from the company directory.")?></span></a>
	</td><td align="right">
	<input type="checkbox" value="e" name="DIRECTORY_OPTS" <?php  echo ($DIRECTORY_OPTS ? 'CHECKED' : '')?> tabindex="<?php echo ++$tabindex;?>"> 
	</td></tr>
	<tr><td>
	<a href=# class="info"><?php echo _("Operator Extension:")?><span>
	<?php echo _("When users hit '0' in the directory, they are put through to this number. Note that it"); ?>
	<?php echo _(" does NOT need to be an extension, it can be a Ring Group, or even an external number."); ?></span></a>
	</td><td align="right">
	<input type="text" size="10" name="OPERATOR_XTN" value="<?php  echo htmlspecialchars($OPERATOR_XTN)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
</table>

<h5><?php echo _("Fax Machine")?></h5>
<table>
	<tr><td>
	<?php echo _("Extension of")?> <a class="info" href="#"><?php echo _("fax machine")?><span><?php echo _("Select 'system' to have the system receive and email faxes.<br>Selecting 'disabled' will result in incoming calls being answered more quickly.")?></span></a> <?php echo _("for receiving faxes:")?>
	<!--<input type="text" size="8" name="FAX_RX" value="<?php  echo $FAX_RX?>"/>-->
	</td><td align="right">
	<select name="FAX_RX" tabindex="<?php echo ++$tabindex;?>">
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
	</td></tr>
	<tr><td>
	<a class="info" href="#"><?php echo _("Email address")?><span><?php echo _("Email address used if 'system' has been chosen for the fax extension above.")?></span></a> <?php echo _("to have faxes emailed to:")?>
	</td><td align="right">
	<input type="text" size="30" name="FAX_RX_EMAIL" value="<?php  echo htmlspecialchars($FAX_RX_EMAIL)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
	<tr><td>
	<a class="info" href="#"><?php echo _("Email address")?><span><?php echo _("Email address that faxes appear to come from if 'system' has been chosen for the fax extension above.")?></span></a> <?php echo _("that faxes appear to come from:")?>
	</td><td align="right">
	<input type="text" size="30" name="FAX_RX_FROM" value="<?php  echo htmlspecialchars($FAX_RX_FROM)?>" tabindex="<?php echo ++$tabindex;?>"/>
	</td></tr>
</table>
<h5><?php echo _("International Settings")?></h5>
<table>
	<tr><td>
	<?php echo _("Country")?> <a class="info" href="#"><?php echo _("Indications")?><span><?php echo _("Select which country you are in")?></span></a>:
	</td><td align="right">
	<?php 	if (isset($TONEZONE) && strlen($TONEZONE)) 
		general_display_zones($TONEZONE); 
		else
		general_display_zones('us'); 
		?>
	</td></tr>
	<tr><td>
	<a class="info" href="#"><?php echo _("24-hour format")?><span><?php echo _("Select Yes if you use 24-hour format or No if you are using 12-hour am/pm format")?></span></a>:
	</td><td align="right">
	<select name="TIMEFORMAT" tabindex="<?php echo ++$tabindex;?>">
		<option value="IMp"><?php echo _("no"); ?></option>
		<option value="kM" <?php echo (($TIMEFORMAT=="kM") ? 'selected="selected"' : '');?>><?php echo _("yes"); ?></option>
	</select>
	</td></tr>
</table>
<h5><?php echo _("Security Settings")?></h5>
<table>
	<tr><td>
	<a href=# class="info"><?php echo _("Allow Anonymous Inbound SIP Calls?")?><span><br>
<?php echo _("** WARNING **")?><br><br>
<?php echo _("Setting this to 'yes' will potentially allow ANYBODY to call into your Asterisk server using the SIP protocol")?><br><br>
<?php echo _("It should only be used if you fully understand the impact of allowing anonymous calls into your server")?><br>
	</span></a>:
	</td><td align="right">
	<select name="ALLOW_SIP_ANON" tabindex="<?php echo ++$tabindex;?>">
	<option value="no"><?php echo _("no"); ?></option>
	<option <?php if ($ALLOW_SIP_ANON == "yes") echo "SELECTED "?>value="yes"><?php echo _("yes"); ?></option>
	</select>
	</td></tr>
</table>

<h5><?php echo _("Online Updates")?></h5>
	<table>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Check for Updates")?><span>
			<?php echo _("Choosing Yes will result in the system automatically checking for updates nighly. The resulting infomation will be displayed in the dashboard and will be optionally emailed to the address below if provided.<br />This will trasmit your FreePBX and Asterisk version numbers along with a unique but random identifier. This is used to provide proper update information and to track version usage to focus development and maintenance efforts. No private information is trasmitted."); ?></span></a>
			</td>
			<td align=right>
			<select name="online_updates" tabindex="<?php echo ++$tabindex;?>">
				<option value="yes"  <?php  echo ($online_updates == 'yes' ? 'SELECTED' : '')?>><?php echo _("Yes")?>
				<option value="no"   <?php  echo ($online_updates == 'no'  ? 'SELECTED' : '')?>><?php echo _("No")?>
			</select> 
			</td>
		</tr>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Update Email")?><span>
			<?php echo _("Email address where online updates will be sent. Leaving blank will result in no updates being sent."); ?></span></a>
			</td>
			<td>
				<input type="text" size="40" name="update_email" value="<?php  echo htmlspecialchars($update_email)?>" tabindex="<?php echo ++$tabindex;?>"/>
			</td>
		</tr>
	</table>
<h6>
	<input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>" tabindex="<?php echo ++$tabindex;?>">
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
	var msgRingOptionOutboundTrunk = "<?php echo _('You have selected the \'r\' option for your trunks. This is highly discouraged and will create problems with calls on many PRI, VoIP, ISDN and other trunks that are capable of signaling. Asterisk will generate a ringing tone until the signalling indicates the line is answered. This will result in some external IVRs being inaccessible and other strange problems.'); ?>";

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

