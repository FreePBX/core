<?php /* $Id$ */
// This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
//    Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$tabindex = 0;

$cm =& cronmanager::create($db);


//if submitting form, update database
if ($action == 'editglobals') {
	$globalfields = array(
						array($_REQUEST['TONEZONE'], 'TONEZONE'),
						array($_REQUEST['TIMEFORMAT'], 'TIMEFORMAT'),
						array($_REQUEST['ALLOW_SIP_ANON'], 'ALLOW_SIP_ANON'),
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
<br />
<a href="<?php echo $_SERVER['PHP_SELF'] ?>?display=advancedsettings">
	<img src="images/cog.png" style="border: none">
	<?php echo _('Advanced Settings') ?>
</a>
<form name="general" action="config.php" method="post">
<input type="hidden" name="display" value="general"/>
<input type="hidden" name="action" value="editglobals"/>

<h5><?php echo _("International Settings")?></h5>
<table>
	<tr><td>
	<a class="info" href="#"><?php echo _("Country Indications")?><span><?php echo _("Select which country you are in")?></span></a>:
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
	<a href=# class="info"><?php echo _("Allow Anonymous Inbound SIP Calls?")?><span>
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
			<?php echo _("Choosing Yes will result in the system automatically checking for updates nightly. The resulting information will be displayed in the dashboard and will be optionally emailed to the address below if provided.<br />This will transmit your FreePBX and Asterisk version numbers along with a unique but random identifier. This is used to provide proper update information and to track version usage to focus development and maintenance efforts. No private information is transmitted."); ?></span></a>
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

function warnConfirm (theField, s) {
    theField.focus();
    theField.select();
		return confirm(s);
}


//-->
</script>
</form>

