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
						array($_REQUEST['ALLOW_SIP_ANON'], 'ALLOW_SIP_ANON'),
						array($_REQUEST['OPERATOR_XTN'], 'OPERATOR_XTN'),
						array(isset($_REQUEST['DIRECTORY_OPTS']) ? $_REQUEST['DIRECTORY_OPTS'] : "",'DIRECTORY_OPTS'),
						array(isset($_REQUEST['VM_OPTS']) ? $_REQUEST['VM_OPTS'] : "",'VM_OPTS'),
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
<?php echo _("r: Generate a ringing tone for the calling party")?><br>
<?php echo _("w: Allow the called user to start recording after pressing *1 (Asterisk v1.2)")?><br>
<?php echo _("W: Allow the calling user to start recording after pressing *1 (Asterisk v1.2)")?><br>
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

function general_onsubmit() {
	var msgInvalidSeconds = "<?php echo _('Please enter a valid Number of Seconds'); ?>";
	var msgInvalidDefaultFaxEmail = "<?php echo _('Please enter a valid Fax Email'); ?>";

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
	
	return true;
}

//-->
</script>
</form>

