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

/* User specific stuff */

$dispnum = 'extensions'; //used for switch in config.php

//create vars from the request
extract($_REQUEST);

// Clean up warnings for undef'ed vars
if (!isset($extdisplay)) $extdisplay='';
if (!isset($record_in)) $record_in='';
if (!isset($record_out)) $record_out='';
if (!isset($emergencycid)) $emergencycid='';
if (!isset($outboundcid)) $outboundcid='';

//make sure we can connect to Asterisk Manager
checkAstMan();

//read in the voicemail.conf and set appropriate variables for display
$uservm = getVoicemail();
$vmcontexts = array_keys($uservm);
$vm=false;
foreach ($vmcontexts as $vmcontext) {
	if(isset($extdisplay) && isset($uservm[$vmcontext][$extdisplay])){
		//echo $extdisplay.' found in context '.$vmcontext.'<hr>';
		$incontext = $vmcontext;  //the context for the current extension
		$vmpwd = $uservm[$vmcontext][$extdisplay]['pwd'];
		$name = $uservm[$vmcontext][$extdisplay]['name'];
		$email = $uservm[$vmcontext][$extdisplay]['email'];
		$pager = $uservm[$vmcontext][$extdisplay]['pager'];
		//loop through all options
		$options="";
		if (is_array($uservm[$vmcontext][$extdisplay]['options'])) {
			$alloptions = array_keys($uservm[$vmcontext][$extdisplay]['options']);
			if (isset($alloptions)) {
				foreach ($alloptions as $option) {
					if ( ($option!="attach") && ($option!="envelope") && ($option!="saycid") && ($option!="delete") && ($option!='') )
						$options .= $option.'='.$uservm[$vmcontext][$extdisplay]['options'][$option].'|';
				}
				$options = rtrim($options,'|');
				// remove the = sign if there are no options set
				$options = rtrim($options,'=');
				
			}
			extract($uservm[$vmcontext][$extdisplay]['options'], EXTR_PREFIX_ALL, "vmops");
		}
		$vm=true;
	}
}

$vmcontext = $_SESSION["AMP_user"]->_deptname; //AMP Users can only add to their department's context
if (empty($vmcontext)) 
	if (isset($incontext))
		$vmcontext = isset($_REQUEST['vmcontext'])?$_REQUEST['vmcontext']:$incontext;
if (empty($vmcontext))
	$vmcontext = 'default';

//check if the extension is within range for this user
if (isset($extension) && !checkRange($extension)){
	echo "<script>javascript:alert('". _("Warning! Extension")." ".$extension." "._("is not allowed for your account").".');</script>";
} else {
	//deviceid and extension are the same and fixed
	$deviceid = $deviceuser = isset($extension)?$extension:'';
	$description = isset($_REQUEST['description'])?$_REQUEST['description']:'';
	
	//user name should be equal to device description
	$_REQUEST['name'] = $description;

	//if submitting form, update database
	if (!isset($action)) 
		$action='';
	switch ($action) {
		case "add":
			core_users_add($_REQUEST,$vmcontext);
			core_devices_add($deviceid,$tech,$dial,$devicetype,$deviceuser,$description,$emergency_cid);
			needreload();
		break;
		case "del":
			core_devices_del($extdisplay);
			core_users_del($extdisplay,$incontext,$uservm);
			core_users_cleanastdb($extdisplay);
			needreload();
		break;
		case "edit":  //just delete and re-add
			core_devices_del($extdisplay);
			core_devices_add($deviceid,$tech,$dial,$devicetype,$deviceuser,$description,$emergency_cid);
			core_users_edit($extdisplay,$_REQUEST,$vmcontext,$incontext,$uservm);
			// Need to re-propogate all the vm info here, because it could have changed
			$uservm = getVoicemail();
			$vmcontexts = array_keys($uservm);
			$vm=false;
			foreach ($vmcontexts as $vmcontext) {
				if(isset($uservm[$vmcontext][$extdisplay])){
					$incontext = $vmcontext;  //the context for the current extension
					$vmpwd = $uservm[$vmcontext][$extdisplay]['pwd'];
					$name = $uservm[$vmcontext][$extdisplay]['name'];
					$email = $uservm[$vmcontext][$extdisplay]['email'];
					$pager = $uservm[$vmcontext][$extdisplay]['pager'];
					$options="";
					if (is_array($uservm[$vmcontext][$extdisplay]['options'])) {
						$alloptions = array_keys($uservm[$vmcontext][$extdisplay]['options']);
						if (isset($alloptions)) {
							foreach ($alloptions as $option) {
								if ( ($option!="attach") && ($option!="envelope") && ($option!="saycid") && ($option!="delete") && ($option!='') )
								$options .= $option.'='.$uservm[$vmcontext][$extdisplay]['options'][$option].'|';
							}
							$options = rtrim($options,'|');
							// remove the = sign if there are no options set
							$options = rtrim($options,'=');
						}
						extract($uservm[$vmcontext][$extdisplay]['options'], EXTR_PREFIX_ALL, "vmops");
					}
					$vm=true;
				}
			}
			needreload();
                break;
		case "resetall":  //form a url with this option to nuke the AMPUSER & DEVICE trees and start over.
			core_users2astdb();
			core_devices2astdb();
		break;
	}
}
?>
</div>

<div class="rnav">
<?php 
$devices = core_devices_list();
drawListMenu($devices, isset($_REQUEST['skip'])?$_REQUEST['skip']:0, $dispnum, isset($extdisplay)?$extdisplay:null, _("Extension"));
?>
</div>


<div class="content">
<?php 
	if ($action == 'del') {
		echo '<br><h3>'.$extdisplay.' '._("deleted").'!</h3><br><br><br><br><br><br><br><br>';
	} else if( (isset($tech)?$tech:'') == '' && (isset($extdisplay)?$extdisplay:'') == '' ) {
?>
		<h2><?php echo _("Add an Extension")?></h2>
		<h5><?php echo _("Select device technology:")?></h5>
		<li><a href="<?php echo $_SERVER['PHP_SELF'].'?display='.$display; ?>&tech=sip"><?php echo _("SIP")?></a><br><br>
		<li><a href="<?php echo $_SERVER['PHP_SELF'].'?display='.$display; ?>&tech=iax2"><?php echo _("IAX2")?></a><br><br>
		<li><a href="<?php echo $_SERVER['PHP_SELF'].'?display='.$display; ?>&tech=zap"><?php echo _("ZAP")?></a><br><br>
		<li><a href="<?php echo $_SERVER['PHP_SELF'].'?display='.$display; ?>&tech=custom"><?php echo _("Custom")?></a><br><br>
<?php
	} else {
		$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=del';
?>
<?php if ($extdisplay != null) {	
	$deviceInfo=core_devices_get($extdisplay);
	extract($deviceInfo,EXTR_PREFIX_ALL,'devinfo');
	$extenInfo=core_users_get($extdisplay);
	extract($extenInfo);
	$tech = $devinfo_tech;
	if (is_array($deviceInfo)) extract($deviceInfo);
?>
		<h2><?php echo strtoupper($tech)." "._("Extension")?>: <?php echo $extdisplay; ?></h2>
		<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Extension")?> <?php echo $extdisplay ?></a></p>
<?php 
		// implementation of module hook
		echo $module_hook->hookHtml;
} else { ?>
		<h2><?php echo _("Add")." ".strtoupper($tech)." "._("Extension")?></h2>
<?php } ?>
<?php
?>
		<form name="addNew" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return addNew_onsubmit();">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
		<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>">
		<input type="hidden" name="tech" value="<?php echo $tech ?>">
		<table>
		
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? _('Edit Extension') : _('Add Extension')) ?><hr></h5></td></tr>

		<tr <?php echo ($extdisplay ? 'style="display:none"':'') ?>>
			<td>
				<a href="#" class="info"><?php echo _("Extension Number")?><span><?php echo _('Use a unique number.  The device will use this number to authenicate to the system, and users will dial it to ring the device.').' '._('You can not use 0')?></span></a>:
			</td>
			<td>
				<input type="text" name="extension" value="<?php echo $extdisplay ?>">
			</td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Display Name")?><span><?php echo _("The caller id name for this device will be set to this.")?><br></span></a>:
			</td><td>
				<input type="text" name="description" value="<?php echo htmlspecialchars($devinfo_description) ?>"/>
			</td>
		</tr>
		<input type="hidden" name="devicetype" value="fixed"/>
		<input type="hidden" name="deviceuser" value="same"/>
		<input type="hidden" name="password" value=""/>
		
		<tr>
			<td colspan="2">
				<h5><br><?php echo _("Extension Options")?><hr></h5>
			</td>
		</tr>
		
		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Direct DID")?><span><?php echo _("The direct DID that is associated with this extension. The DID should be in the same format as provided by the provider (e.g. full number, 4 digits for 10x4, etc).<br><br>Format should be: <b>XXXXXXXXXX</b><br><br>Leave this field blank to disable the direct DID feature for this extension. All non-numeric characters will be stripped.")?><br></span></a>:
			</td><td>
				<input type="text" name="directdid" value="<?php echo htmlentities($directdid) ?>"/>
			</td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("DID Alert Info")?><span><?php echo _("Alert Info can be used for distinctive ring on SIP phones. Set this value to the desired Alert Info to be sent to the phone when this DID is called. Leave blank to use default values. Will have no effect if no Direct DID is set")?><br></span></a>:
			</td><td>
				<input type="text" name="didalert" value="<?php echo htmlentities($didalert) ?>"/>
			</td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Outbound CID")?><span><?php echo _("Overrides the caller id when dialing out a trunk. Any setting here will override the common outbound caller id set in the Trunks admin.<br><br>Format: <b>\"caller name\" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound callerid feature for this extension.")?><br></span></a>:
			</td><td>
				<input type="text" name="outboundcid" value="<?php echo htmlentities($outboundcid) ?>"/>
			</td>
		</tr>

		<tr>
			   <td>
					   <a href="#" class="info"><?php echo _("Emergency CID")?><span><?php echo _("This caller id will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other caller id settings.")?><br></span></a>:
			   </td><td>
					   <input type="text" name="emergency_cid" value="<?php echo htmlspecialchars($emergency_cid) ?>"/>
			   </td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Record Incoming")?><span><?php echo _("Record all inbound calls received at this extension.")?><br></span></a>:
			</td><td> &nbsp;
				<select name="record_in"/>
					<option value="Adhoc" <?php  echo ($record_in == "On-Demand") ? 'selected' : '' ?>><?php echo _("On Demand")?>
					<option value="Always" <?php  echo ($record_in == "Always") ? 'selected' : '' ?>><?php echo _("Always")?>
					<option value="Never" <?php  echo ($record_in == "Never") ? 'selected' : '' ?>><?php echo _("Never")?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Record Outgoing")?><span><?php echo _("Record all outbound calls received at this extension.")?><br></span></a>:
			</td><td> &nbsp;
				<select name="record_out"/>
					<option value="Adhoc" <?php  echo ($record_out == "On-Demand") ? 'selected' : '' ?>><?php echo _("On Demand")?>
					<option value="Always" <?php  echo ($record_out == "Always") ? 'selected' : '' ?>><?php echo _("Always")?>
					<option value="Never" <?php  echo ($record_out == "Never") ? 'selected' : '' ?>><?php echo _("Never")?>
				</select>
			</td>
		</tr>

			<td><br></td>

		<tr><td colspan="2"><h5><?php echo _("Fax Handling")?><hr></h5></td></tr>
		<tr>
			<td>
				<a class="info" href="#"><?php echo _("Fax Extension")?><span><?php echo _("Select 'system' to have the system receive and email faxes.<br><br>The freePBX default is defined in General Settings.")?></span></a>:
			</td>
			<td>&nbsp;
				<select name="faxexten">
<?php 
// Cleaning up warnings. I should do this a better way.
if (!isset($faxexten))
	$faxexten = null;
if (!isset($faxemail))
	$faxemail = null;
if (!isset($answer))
	$answer = '0';
?>
					<option value="default" <?php  echo ($faxexten == 'default' ? 'SELECTED' : '')?>><?php echo _("freePBX default")?>
					<option value="disabled" <?php  echo ($faxexten == 'disabled' ? 'SELECTED' : '')?>><?php echo _("disabled")?>
					<option value="system" <?php  echo ($faxexten == 'system' ? 'SELECTED' : '')?>><?php echo _("system")?>
			<?php 
				//get unique devices
				$devices = core_devices_list();
				if (isset($devices)) {
					foreach ($devices as $device) {
						echo '<option value="'.$device[0].'" '.($faxexten == $device[0] ? 'SELECTED' : '').'>'.$device[1].' &lt;'.$device[0].'&gt;';
					}
				}
			?>	
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<a class="info" href="#"><?php echo _("Fax Email")?><span><?php echo _("Email address is used if 'system' has been chosen for the fax extension above.<br><br>Leave this blank to use the freePBX default in General Settings.")?></span></a>:
			</td>
			<td>
				<input type="text" size="20" name="faxemail" value="<?php echo htmlspecialchars($faxemail)?>"/>
			</td>
		</tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Fax Detection Type")?><span><?php echo _('Selecting Zaptel or NVFax will immediately answer the call and play ringing tones to the caller for the number of seconds in Pause below. Use NVFax on SIP or IAX trunks.')?></span></a>:</td>
			<td>&nbsp;
				<select name="answer">
					<option value="0" <?php  echo ($answer == '0' ? 'SELECTED' : '')?>><?php echo _("None")?>
					<option value="1" <?php  echo ($answer == '1' ? 'SELECTED' : '')?>><?php echo _("Zaptel")?>
					<option value="2" <?php  echo ($answer == '2' ? 'SELECTED' : '')?>><?php echo _("NVFax")?>
				</select>
			</td>
		</tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Pause after answer")?><span><?php echo _('The number of seconds we should wait after performing an Immediate Answer. The primary purpose of this is to pause and listen for a fax tone before allowing the call to proceed.')?></span></a>:</td>
			<td><input type="text" name="wait" size="3" value="<?php echo isset($wait)?$wait:'' ?>"></td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
<?php
if (!isset($privacyman))
	$privacyman = '0';
?>
		<tr><td colspan="2"><h5><?php echo _("Privacy")?><hr></h5></td></tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Privacy Manager")?><span><?php echo _('If no Caller ID is sent, Privacy Manager will asks the caller to enter their 10 digit phone number. The caller is given 3 attempts.')?></span></a>:</td>
			<td>
				<select name="privacyman">
					<option value="0" <?php  echo ($privacyman == '0' ? 'SELECTED' : '')?>><?php echo _("No")?>
					<option value="1" <?php  echo ($privacyman == '1' ? 'SELECTED' : '')?>><?php echo _("Yes")?>
				</select>
			</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>		
		
		<tr>
			<td colspan="2">
				<h5><br><?php echo _("Device Options")?><hr></h5>
			</td>
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

if($extdisplay != '') {
	foreach($basic as $key => $value) {
		echo "<tr><td>"._("{$key}")."</td><td><input type=\"text\" name=\"{$key}\" value=\"{$$key}\"/></td></tr>";
	}
	foreach($advanced as $key => $value) {
		echo "<tr><td>"._("{$key}")."</td><td><input type=\"text\" name=\"{$key}\" value=\"{$$key}\"/></td></tr>";
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

			<tr><td colspan=2>
				<h5><br><br><?php echo _("Voicemail & Directory:")?>&nbsp;&nbsp;&nbsp;&nbsp;
					<select name="vm" onchange="checkVoicemail(addNew);">
						<option value="enabled" <?php  echo ($vm) ? 'selected' : '' ?>><?php echo _("Enabled");?></option> 
						<option value="disabled" <?php  echo (!$vm) ? 'selected' : '' ?>><?php echo _("Disabled");?></option> 
					</select>
				<hr></h5>
			</td></tr>
			<tr><td colspan=2>
				<table id="voicemail" <?php  echo ($vm) ? '' : 'style="display:none;"' ?>>
				<tr>
					<td>
						<a href="#" class="info"><?php echo _("voicemail password")?><span><?php echo _("This is the password used to access the voicemail system.<br><br>This password can only contain numbers.<br><br>A user can change the password you enter here after logging into the voicemail system (*98) with a phone.")?><br><br></span></a>: 
					</td><td>
						<input size="10" type="text" name="vmpwd" value="<?php echo isset($vmpwd)?$vmpwd:'' ?>"/>
					</td>
				</tr>
				<tr>
					<td><a href="#" class="info"><?php echo _("email address")?><span><?php echo _("The email address that voicemails are sent to.")?></span></a>: </td>
					<td><input type="text" name="email" value="<?php echo htmlspecialchars(isset($email)?$email:''); ?>"/></td>
				</tr>
				<tr>
					<td><a href="#" class="info"><?php echo _("pager email address")?><span><?echo _("Pager/mobile email address that short voicemail notifcations are sent to.")?></span></a>: </td>
					<td><input type="text" name="pager" value="<?php echo htmlspecialchars(isset($pager)?$pager:''); ?>"/></td>
				</tr>
				<tr>
 					<td><a href="#" class="info"><?php echo _("email attachment")?><span><?php echo _("Option to attach voicemails to email.")?></span></a>: </td>
 					<?php if (isset($vmops_attach) && $vmops_attach == "yes"){?>
 					<td><input  type="radio" name="attach" value="attach=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="attach" value="attach=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="attach" value="attach=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="attach" value="attach=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>
 
				<tr>
 					<td><a href="#" class="info"><?php echo _("Play CID")?><span><?php echo _("Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left.")?></span></a>: </td>
 					<?php if (isset($vmops_saycid) && $vmops_saycid == "yes"){?>
 					<td><input  type="radio" name="saycid" value="saycid=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="saycid" value="saycid=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="saycid" value="saycid=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="saycid" value="saycid=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Play Envelope")?><span><?php echo _("Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message. This settng does not affect the operation of the envelope option in the advanced voicemail menu.")?></span></a>: </td>
 					<?php if (isset($vmops_envelope) && $vmops_envelope == "yes"){?>
 					<td><input  type="radio" name="envelope" value="envelope=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="envelope" value="envelope=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="envelope" value="envelope=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="envelope" value="envelope=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Delete Vmail")?><span><?php echo _("If set to \"yes\" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER.")?>
</span></a>: </td>
 					<?php if (isset($vmops_delete) && $vmops_delete == "yes"){?>
 					<td><input  type="radio" name="delete" value="delete=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="delete" value="delete=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="delete" value="delete=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="delete" value="delete=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>
 				
 				<tr>
					<td><a href="#" class="info"><?php echo _("vm options")?><span><?php echo _("Separate options with pipe ( | )")?><br><br><?php echo _("ie: review=yes|maxmessage=60")?></span></a>: </td>
					<td><input size="20" type="text" name="options" value="<?php  echo htmlspecialchars(isset($options)?$options:''); ?>" /></td>
				</tr>
				<tr>
					<td><?php echo _("vm context:")?> </td>
					<td><input size="20" type="text" name="vmcontext" value="<?php  echo $vmcontext; ?>" /></td>
				</tr>
			</table>
		</td></tr>
		
		
		<tr>
			<td colspan=2>
				<br><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit")?>">
			</td>
		</tr>
		</table>

<script language="javascript">
<!--

var theForm = document.addNew;
if (theForm.extension.value == "") {
	theForm.extension.focus();
} else {
	theForm.description.focus();
}

function addNew_onsubmit() {
	var msgInvalidExtNum = "<?php echo _('Please enter a valid extension number.'); ?>";
	var msgInvalidDevDesc = "<?php echo _('Please enter a valid Description for this device'); ?>";
	var msgInvalidOutboundCID = "<?php echo _('Please enter a valid Outbound CID'); ?>";
	var msgInvalidEmergCID = "<?php echo _('Please enter a valid Emergency CID'); ?>";
	var msgInvalidDTMFMODE = "<?php echo _('Please enter the dtmfmode for this device'); ?>";
	var msgInvalidChannel = "<?php echo _('Please enter the channel for this device'); ?>";
	var msgInvalidVmPwd = "<?php echo _('Please enter a valid Voicemail Password, using digits only'); ?>";
	var msgInvalidEmail = "<?php echo _('Please enter a valid Email Address'); ?>";
	var msgInvalidPager = "<?php echo _('Please enter a valid Pager Email Address'); ?>";
	var msgInvalidVMContext = "<?php echo _('VM Context cannot be blank'); ?>";
	var msgConfirmSecret = "<?php echo _('You have not entered a Secret for this device, although this is possible it is generally bad practice to not assign a Secret to a device.\n\nAre you sure you want to leave the Secret empty?'); ?>";

	defaultEmptyOK = false;
	if (!isInteger(theForm.extension.value))
		return warnInvalid(theForm.extension, msgInvalidExtNum);

	if (!isCallerID(theForm.description.value))
		return warnInvalid(theForm.description, msgInvalidDevDesc);

	defaultEmptyOK = true;
	if (!isCallerID(theForm.outboundcid.value))
		return warnInvalid(theForm.outboundcid, msgInvalidOutboundCID);

	if (!isCallerID(theForm.emergency_cid.value))
		return warnInvalid(theForm.emergency_cid, msgInvalidEmergCID);

	defaultEmptyOK = false;
	if (theForm.dtmfmode != undefined && isEmpty(theForm.dtmfmode.value))
		return warnInvalid(theForm.dtmfmode, msgInvalidDTMFMODE);

	if (theForm.channel != undefined && isEmpty(theForm.channel.value))
		return warnInvalid(theForm.channel, msgInvalidChannel);

	// voicemail stuff
	if (theForm.vm.value == "enabled") {
		defaultEmptyOK = false;
		if (!isInteger(theForm.vmpwd.value))
			return warnInvalid(theForm.vmpwd, msgInvalidVmPwd);
		
		defaultEmptyOK = true;
		if (!isEmail(theForm.email.value))
			return warnInvalid(theForm.email, msgInvalidEmail);
			
		if (!isEmail(theForm.pager.value))
			return warnInvalid(theForm.pager, msgInvalidPager);
			
		defaultEmptyOK = false;
		if (isEmpty(theForm.vmcontext.value) || isWhitespace(theForm.vmcontext.value))
			return warnInvalid(theForm.vmcontext, msgInvalidVMContext);
		
	}

	if (theForm.secret != undefined && isEmpty(theForm.secret.value)) {
		var cnf = confirm(msgConfirmSecret);
		if (!cnf) {
			theForm.secret.focus();
			return false;
		}
	}

	return true;
}

function checkVoicemail(theForm) {
	$vm = theForm.elements["vm"].value;
	if ($vm == 'disabled') {
		document.getElementById('voicemail').style.display='none';
		theForm.vmpwd.value = '';
		theForm.email.value = '';
		theForm.pager.value = '';
	} else {
		document.getElementById('voicemail').style.display='block';
	}
}

//-->
</script>

		</form>
<?php 		
	} //end if action == delGRP
	

?>


