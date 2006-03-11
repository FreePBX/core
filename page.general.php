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
	$globalfields = array(array($_REQUEST['RINGTIMER'],'RINGTIMER'),
						array($_REQUEST['FAX_RX'],'FAX_RX'),
						array($_REQUEST['FAX_RX_EMAIL'],'FAX_RX_EMAIL'),
						array($_REQUEST['DIRECTORY'],'DIRECTORY'),
						array($_REQUEST['VM_PREFIX'],'VM_PREFIX'),
						array($_REQUEST['DIAL_OPTIONS'],'DIAL_OPTIONS'),
						array(isset($_REQUEST['DIRECTORY_OPTS']) ? $_REQUEST['DIRECTORY_OPTS'] : "",'DIRECTORY_OPTS'),
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

<form name="general" action="config.php" method="post">
<input type="hidden" name="display" value="general"/>
<input type="hidden" name="action" value="editglobals"/>

<h5><?php echo _("Dialing Options")?></h5>
<p>
	<?php echo _("Number of seconds to ring phones before sending callers to voicemail:")?>
	<input type="text" size="2" name="RINGTIMER" value="<?php  echo htmlspecialchars($RINGTIMER)?>"/>
	<br><br>
	<?php echo _("Extension prefix for dialing direct to voicemail:")?>
	<input type="text" size="2" name="VM_PREFIX" value="<?php  echo htmlspecialchars($VM_PREFIX)?>"/>
	<br><br>
	<a href=# class="info"><?php echo _("Asterisk Dial command options:")?><span><br>
t: Allow the called user to transfer the call by hitting #<br>
T: Allow the calling user to transfer the call by hitting #<br>
r: Generate a ringing tone for the calling party<br>
w: Allow the called user to start recording after pressing *1 (Asterisk v1.2)<br>
W: Allow the calling user to start recording after pressing *1 (Asterisk v1.2)<br>
	</span></a>
	<input type="text" size="2" name="DIAL_OPTIONS" value="<?php  echo htmlspecialchars($DIAL_OPTIONS)?>"/>
</p>

<h5><?php echo _("Company Directory")?></h5>
<p>
	<?php echo _("Find users in the")?> <a href=# class="info"><?php echo _("Company Directory")?><span><br>
	<?php echo _("Callers who are greeted by a Digital Receptionist can dial pound (#) to access the Company Directory.<br><br>Internal extensions can dial *411 to access the Company Directory.")?></span></a> <?php echo _("by:")?>
	<select name="DIRECTORY">
		<option value="first" <?php  echo ($DIRECTORY == 'first' ? 'SELECTED' : '')?>><?php echo _("first name")?>
		<option value="last" <?php  echo ($DIRECTORY == 'last' ? 'SELECTED' : '')?>><?php echo _("last name")?>
		<option value="both" <?php  echo ($DIRECTORY == 'both' ? 'SELECTED' : '')?>><?php echo _("first or last name")?>
	</select> 
	<br><br>
	<input type="checkbox" value="e" name="DIRECTORY_OPTS" <?php  echo ($DIRECTORY_OPTS ? 'CHECKED' : '')?>> <a href=# class="info"><?php echo _("Play extension number")?><span><?php echo _("Plays a message \"Please hold while I transfer you to extension xxx\" that lets the caller know what extension to use in the future.")?></span></a> <?php echo _("to caller before transferring call")?>
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
<br>
<h6>
	<input name="Submit" type="button" value="<?php echo _("Submit Changes")?>" onclick="checkGeneral(general)">
</h6>
</form>
