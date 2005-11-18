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



//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';

//script to write meetme_additional.conf file from mysql 
$wMeetScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_meetme_conf_from_mysql.pl';

$dispnum = 'users'; //used for switch in config.php

//create vars from the request
extract($_REQUEST);

//make sure we can connect to Asterisk Manager
checkAstMan();

//read in the voicemail.conf and set appropriate variables for display
$uservm = getVoicemail();
$vmcontexts = array_keys($uservm);
$vm=false;
foreach ($vmcontexts as $vmcontext) {
	if(isset($uservm[$vmcontext][$extdisplay])){
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
	$vmcontext = ($_REQUEST['vmcontext'] ? $_REQUEST['vmcontext'] : $incontext);
if (empty($vmcontext))
	$vmcontext = 'default';

//check if the extension is within range for this user
if (isset($extension) && !checkRange($extension)){
	echo "<script>javascript:alert('". _("Warning! Extension")." ".$extension." "._("is not allowed for your account").".');</script>";
} else {

	//if submitting form, update database
	switch ($action) {
		case "add":
			core_users_add($_REQUEST,$vmcontext);

			//write out extensions_additional.conf
			exec($wScript1);			
			//write out meetme_additional.conf
			exec($wMeetScript);
			needreload();
		break;
		case "del":
			core_users_del($extdisplay,$incontext,$uservm);
			//write out extensions_additional.conf
			exec($wScript1);			
			//write out meetme_additional.conf
			exec($wMeetScript);
			needreload();
		break;
		case "edit":  //just delete and re-add
			core_users_del($extdisplay,$incontext,$uservm);
			core_users_add($_REQUEST,$vmcontext);
			//write out extensions_additional.conf
			exec($wScript1);			
			//write out meetme_additional.conf
			exec($wMeetScript);
			needreload();
		break;
	}
}

?>
</div>

<div class="rnav">
<?php 
$extens = core_users_list();
drawListMenu($extens, $_REQUEST['skip'], $dispnum, $extdisplay, _("User"));
?>
</div>


<div class="content">
<?php 
	if ($action == 'del') {
		echo '<br><h3>'.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else {
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=del';
?>
<?php if ($extdisplay) {	
	$extenInfo=core_users_get($extdisplay);
	extract($extenInfo);
	if (is_array($deviceInfo)) extract($deviceInfo);
?>
		<h2><?php echo _("User")?>: <?php echo $extdisplay; ?></h2>
		<p><a href="<?php echo $delURL ?>"><?php echo _("Delete User")?> <?php echo $extdisplay ?></a></p>
<?php } else { ?>
		<h2><?php echo _("Add User/Extension")?></h2>
<?php } ?>
		<form name="addNew" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add') ?>">
		<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>">
		<table>
		
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? _('Edit User') : _('Add User')) ?><hr></h5></td></tr>

		<tr <?php echo ($extdisplay ? 'style="display:none"':'') ?>>
			<td>
				<a href="#" class="info"><?php echo _("User Extension")?><span><?php echo _("The extension number to dial to reach this user.")?></span></a>:
			</td>
			<td>
				<input type="text" name="extension" value="<?php echo $extdisplay ?>">
			</td>
		</tr>
		
		<tr>
			<td>
				<a href="#" class="info"><?php echo _("User Password")?><span><?php echo _("A user will enter this password when logging onto a device. *11 logs into a device.  *12 logs out of a device.")?><br></span></a>:
			</td><td>
				<input type="text" name="password" value="<?php echo $password ?>"/>
			</td>
		</tr>

		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Display Name")?><span><?php echo _("The caller id name for calls from this user will be set to this name.")?><br></span></a>:
			</td><td>
				<input type="text" name="name" value="<?php echo $name ?>"/>
			</td>
		</tr>
		
		<tr>
			<td colspan="2">
				<h5><br>Extension Options<hr></h5>
			</td>
		</tr>
		
		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Outbound CID")?><span><?php echo _("Overrides the caller id when dialing out a trunk. Any setting here will override the common outbound caller id set in the Trunks admin.<br><br>Format: <b>\"caller name\" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound callerid feature for this user.")?><br></span></a>:
			</td><td>
				<input type="text" name="outboundcid" value="<?php echo htmlentities($outboundcid) ?>"/>
			</td>
		</tr>
		
		<!--<tr>
			<td>
				<a href="#" class="info"><?php echo _("Ring Timer")?><span><?php echo _("Number of seconds to ring the extension before giving up.")?><br></span></a>:
			</td><td>
				<input type="text" name="ringtimer" value="<?php echo $ringtimer ?>"/>
			</td>
		</tr>-->
		
		<tr>
			<td>
				<a href="#" class="info"><?php echo _("Record Incoming")?><span><?php echo _("Record all inbound calls received at this extension.")?><br></span></a>:
			</td><td>
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
			</td><td>
				<select name="record_out"/>
					<option value="Adhoc" <?php  echo ($record_out == "On-Demand") ? 'selected' : '' ?>><?php echo _("On Demand")?>
					<option value="Always" <?php  echo ($record_out == "Always") ? 'selected' : '' ?>><?php echo _("Always")?>
					<option value="Never" <?php  echo ($record_out == "Never") ? 'selected' : '' ?>><?php echo _("Never")?>
				</select>
			</td>
		</tr>
		
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
						<input size="10" type="text" name="vmpwd" value="<?php echo $vmpwd ?>"/>
					</td>
				</tr>
				<tr>
					<td><a href="#" class="info"><?php echo _("email address")?><span><?php echo _("The email address that voicemails are sent to.")?></span></a>: </td>
					<td><input type="text" name="email" value="<?php  echo $email; ?>"/></td>
				</tr>
				<tr>
					<td><a href="#" class="info"><?php echo _("pager email address")?><span><?echo _("Pager/mobile email address that short voicemail notifcations are sent to.")?></span></a>: </td>
					<td><input type="text" name="pager" value="<?php  echo $pager; ?>"/></td>
				</tr>
				<tr>
 					<td><a href="#" class="info"><?php echo _("email attachment")?><span><?php echo _("Option to attach voicemails to email.")?></span></a>: </td>
 					<?php if ($vmops_attach == "yes"){?>
 					<td><input  type="radio" name="attach" value="attach=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="attach" value="attach=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="attach" value="attach=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="attach" value="attach=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>
 
				<tr>
 					<td><a href="#" class="info"><?php echo _("Play CID")?><span><?php echo _("Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left.")?></span></a>: </td>
 					<?php if ($vmops_saycid == "yes"){?>
 					<td><input  type="radio" name="saycid" value="saycid=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="saycid" value="saycid=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="saycid" value="saycid=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="saycid" value="saycid=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Play Envelope")?><span><?php echo _("Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message. This settng does not affect the operation of the envelope option in the advanced voicemail menu.")?></span></a>: </td>
 					<?php if ($vmops_envelope == "yes"){?>
 					<td><input  type="radio" name="envelope" value="envelope=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="envelope" value="envelope=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="envelope" value="envelope=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="envelope" value="envelope=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>

				<tr>
 					<td><a href="#" class="info"><?php echo _("Delete Vmail")?><span><?php echo _("If set to \"yes\" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER.")?>
</span></a>: </td>
 					<?php if ($vmops_delete == "yes"){?>
 					<td><input  type="radio" name="delete" value="delete=yes" checked=checked/> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input  type="radio" name="delete" value="delete=no"/> <?php echo _("no");?></td>
 					<?php } else{ ?>
 					<td><input  type="radio" name="delete" value="delete=yes" /> <?php echo _("yes");?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="delete" value="delete=no" checked=checked /> <?php echo _("no");?></td> <?php }?>
 				</tr>
 				
 				<tr>
					<td><a href="#" class="info">vm options<span><?php echo _("Separate options with pipe ( | )")?><br><br>ie: review=yes|maxmessage=60</span></a>: </td>
					<td><input size="20" type="text" name="options" value="<?php  echo $options; ?>" /></td>
				</tr>
				<tr>
					<td><?php echo _("vm context:")?> </td>
					<td><input size="20" type="text" name="vmcontext" value="<?php  echo $vmcontext; ?>" /></td>
				</tr>
			</table>
		</td></tr>
		<tr>
			<td colspan=2>
				<br><br><h6><input name="Submit" type="button" value="<?php echo _("Submit")?>" onclick="javascript:if(addNew.extension.value=='' || parseInt(addNew.extension.value)!=addNew.extension.value) {alert('<?php echo _("Please enter an extension number.")?>')} else {addNew.submit();}"></h6>
			</td>
		</tr>
		</table>
		
		</form>
<?php 		
	} //end if action-del
?>

