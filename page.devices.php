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

include 'common/php-asmanager.php';


//make sure we can connect to Asterisk Manager
checkAstMan();

$dispnum = 'devices'; //used for switch on config.php
//create vars from the request
extract($_REQUEST);

//if submitting form, update database
switch ($action) {
	case "add":
		core_devices_add($deviceid,$tech,$dial,$devicetype,$deviceuser,$description,$emergency_cid);
		//generateMeetme();
		//generateExtensions();
		needreload();
	break;
	case "del":
		core_devices_del($extdisplay);
		//generateMeetme();
		//generateExtensions();
		needreload();
	break;
	case "edit":  //just delete and re-add
		core_devices_del($extdisplay);
		core_devices_add($deviceid,$tech,$dial,$devicetype,$deviceuser,$description,$emergency_cid);
		//generateMeetme();
		//generateExtensions();
		needreload();
	break;
	case "resetall":  //form a url with this option to nuke the AMPUSER & DEVICE trees and start over.
		users2astdb();
		devices2astdb();
	break;
}
?>
</div>

<div class="rnav">
<?php 
$devices = core_devices_list();
drawListMenu($devices, $_REQUEST['skip'], $dispnum, $extdisplay, _("Device"));
?>
</div>


<div class="content">
<?php 
	if ($action == 'del') {
		echo '<br><h3>'.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else if(empty($tech) && ($extdisplay == null)) {
?>
		<h2><?php echo _("Add a Device")?></h2>
		<h5><?php echo _("Select device technology:")?></h5>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=sip"><?php echo _("SIP")?></a><br><br>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=iax2"><?php echo _("IAX2")?></a><br><br>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=zap"><?php echo _("ZAP")?></a><br><br>
		<li><a href="<?php echo $_REQUEST['PHP_SELF'].'?display='.$display; ?>&tech=custom"><?php echo _("Custom")?></a><br><br>
<?php
	} else {
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=del';
?>
<?php if ($extdisplay != null) {	
	$deviceInfo=core_devices_get($extdisplay);
	extract($deviceInfo,EXTR_PREFIX_ALL,'devinfo');
	$tech = $devinfo_tech;
	if (is_array($deviceInfo)) extract($deviceInfo);
?>
		<h2><?php echo strtoupper($tech)." "._("Device")?>: <?php echo $extdisplay; ?></h2>
		<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Device")?> <?php echo $extdisplay ?></a></p>
<?php } else { ?>
		<h2><?php echo _("Add")." ".strtoupper($tech)." "._("Device")?></h2>
<?php } ?>
		<form name="addNew" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
		<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>">
		<input type="hidden" name="tech" value="<?php echo $tech ?>">
		<table>
		
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? _('Edit Device') : _('Add Device')) ?><hr></h5></td></tr>

		<tr <?php echo ($extdisplay ? 'style="display:none"':'') ?>>
			<td>
				<a href="#" class="info"><?php echo _("Device ID")?><span><?php echo _('Give your device a unique integer ID.  The device will use this ID to authenicate to the system.')?></span></a>:
			</td>
			<td>
				<input type="text" name="deviceid" value="<?php echo $extdisplay ?>">
			</td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Description")?><span><?php echo _("The caller id name for this device will be set to this description until it is logged into.")?><br></span></a>:
			</td><td>
				<input type="text" name="description" value="<?php echo $devinfo_description ?>"/>
			</td>
		</tr>

		<tr>
			   <td>
					   <a href="#" class="info"><?php echo _("Emergency CID")?><span><?php echo _("This caller id will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other caller id settings.")?><br></span></a>:
			   </td><td>
					   <input type="text" name="emergency_cid" value="<?php echo $devinfo_emergency_cid ?>"/>
			   </td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Device Type")?><span><?php echo _('Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into (*11) and logged out of (*12) by users.')?></span></a>:</td>
			<td>
				<select name="devicetype">
					<option value="fixed" <?php  echo ($devinfo_devicetype == 'fixed' ? 'SELECTED' : '')?>><?php echo _("Fixed")?>
					<option value="adhoc" <?php  echo ($devinfo_devicetype == 'adhoc' ? 'SELECTED' : '')?>><?php echo _("Adhoc")?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td><a href="#" class="info"><?php echo _("Default User")?><span><?php echo _('Fixed devices will always mapped to this user.  Adhoc devices will be mapped to this user by default.<br><br>If selecting "New User", a new User Extension of the same Device ID will be set as the Default User.')?></span></a>:</td>
			<td>
				<select name="deviceuser">
					<option value="none" <?php echo ($devinfo_user == 'none' ? 'SELECTED' : '')?>><?php echo _("none")?>
					<option value="new"><?php echo _("New User")?>
			<?php 
				//get unique extensions
				$users = core_users_list();
				if (isset($users)) {
					foreach ($users as $auser) {
						echo '<option value="'.$auser[0].'" '.($user == $auser[0] ? 'SELECTED' : '').'>'.$auser[0];	
					}
				}
			?>
			</td>
		</tr>
		
		<tr>
			<td><br></td>
		</tr>
		
<?php
switch(strtolower($tech)) {
	case "zap":
		$basic = array(
			'channel' => '',
		);
		$advanced = array(
			'context' => 'from-internal',
			'signalling' => 'fxo_ks',
			'echocancel' => 'yes',
			'echocancelwhenbridged' => 'no',
			'echotraining' => '800',
			'busydetect' => 'no',
			'busycount' => '7',
			'callprogress' => 'no',
			'dial' => '',
			'accountcode' => '',
			'mailbox' => ''
		);
	break;
	case "iax2":
		$basic = array(
			'secret' => '',
		);
		$advanced = array(
			'notransfer' => 'yes',
			'context' => 'from-internal',
			'host' => 'dynamic',
			'type' => 'friend',
			'port' => '4569',
			'qualify' => 'no',
			'disallow' => '',
			'allow' => '',
			'dial' => '',
			'accountcode' => '',
			'mailbox' => ''
		);		
	break;
	case "sip":
		$basic = array(
			'secret' => '',
			'dtmfmode' => 'rfc2833'
		);
		$advanced = array(
			'canreinvite' => 'no',
			'context' => 'from-internal',
			'host' => 'dynamic',
			'type' => 'friend',
			'nat' => 'never',
			'port' => '5060',
			'qualify' => 'no',
			'callgroup' => '',
			'pickupgroup' => '',
			'disallow' => '',
			'allow' => '',
			'dial' => '',
			'accountcode' => '',
			'mailbox' => ''
		);
	break;
	case "custom":
		$basic = array(
			'dial' => '',
		);
		$advanced = array();
	break;
}

if($extdisplay) {
	foreach($basic as $key => $value) {
		echo "<tr><td>{$key}</td><td><input type=\"text\" name=\"{$key}\" value=\"{$$key}\"/></td></tr>";
	}
	foreach($advanced as $key => $value) {
		echo "<tr><td>{$key}</td><td><input type=\"text\" name=\"{$key}\" value=\"{$$key}\"/></td></tr>";
	}
} else {
	foreach($basic as $key => $value) {
		echo "<tr><td>{$key}</td><td><input type=\"text\" name=\"{$key}\" value=\"{$value}\"/></td></tr>";
	}
	foreach($advanced as $key => $value) {
		echo "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>";
	}
}
?>
			
		<tr>
			<td colspan=2>
				<br><br><h6><input name="Submit" type="button" value="<?php echo _("Submit")?>" onclick="javascript:if(addNew.deviceid.value=='' || parseInt(addNew.deviceid.value)!=addNew.deviceid.value) {alert('<?php echo _("Please enter a device id.")?>')} else {addNew.submit();}"></h6>
			</td>
		</tr>
		</table>
		
		</form>
<?php 		
	} //end if action == delGRP
	

?>

