<?php 
// Zap Channel DIDs display for FreePBX
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
// Copyright 2006 Philippe Lindheimer - Astrogen LLC

$display = 'zapchandids';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] :  'setup';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] :  '';
if (isset($_REQUEST['delete'])) $action = 'delete'; 


$extdisplay  = isset($_REQUEST['extdisplay']) ? $_REQUEST['extdisplay'] : '';
$channel     = isset($_REQUEST['channel']) ? $_REQUEST['channel'] :  false;
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] :  '';
$did         = isset($_REQUEST['did']) ? $_REQUEST['did'] :  '';

switch ($action) {
	case 'add':
		if (core_zapchandids_add($description, $channel, $did)) {
			needreload();
			redirect_standard();
		}
	break;
	case 'edit':
		if (core_zapchandids_edit($description, $channel, $did)) {
			needreload();
			redirect_standard('extdisplay');
		}
	break;
	case 'delete':
		core_zapchandids_delete($channel);
		needreload();
		redirect_standard();
	break;
}


?> 
</div>

<div class="rnav"><ul>
<?php 

echo '<li><a href="config.php?display='.$display.'&type='.$type.'">'._('Add Channel').'</a></li>';

foreach (core_zapchandids_list() as $row) {
	echo '<li><a href="config.php?display='.$display.'&type='.$type.'&extdisplay='.$row['channel'].'" class="">'.$row['channel'].': '.$row['description'].'</a></li>';
}

?>
</ul></div>

<div class="content">

<?php

if ($extdisplay != '') {
	// load
	$row = core_zapchandids_get($extdisplay);
	
	$description = $row['description'];
	$channel     = $row['channel'];
	$did         = $row['did'];

	echo "<h2>"._("Edit Zap Channel: ").$channel."</h2>";
} else {
	echo "<h2>"._("Add Zap Channel")."</h2>";
}

$helptext = _("Zap Channel DIDs allow you to assign a DID to specific Zap Channels. You can supply the same DID to multiple channels. This would be a common scenario if you have multiple POTS lines that are on a hunt group from your provider. You MUST assign the channel's context to from-zaptel for these settings to have effect. It will be a line that looks like:<br /><br />context = from-zaptel<br /><br />in your zapata.conf configuration effecting the specified channel(s). Once you have assigned DIDs you can use standard Inbound Routes with the specified DIDs to route your calls.");
echo "<p>".$helptext."</p>\n";
?>
<form name="editZapchandid" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkZapchandid(editZapchandid);">
	<input type="hidden" name="extdisplay" value="<?php echo $extdisplay; ?>">
	<input type="hidden" name="channel" value="<?php echo $extdisplay; ?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay != '' ? 'edit' : 'add'); ?>">
	<table>
	<tr><td colspan="2"><h5><?php  echo ($extdisplay != '' ? _("Edit Channel").": ".$extdisplay : _("Add Channel")) ?><hr></h5></td></tr>
<?php
	if ($extdisplay == '') {
?>
	<tr>
		<td><a href="#" class="info"><?php echo _("Channel")?>:<span><?php echo _("The Zap Channel number to map to a DID")?></span></a></td>
		<td><input size="5" type="text" name="channel" value="<?php  echo $channel; ?>" tabindex="<?php echo ++$tabindex;?>"></td>
	</tr>
<?php
	}
?>
	<tr>
		<td><a href="#" class="info"><?php echo _("Description")?>:<span><?php echo _("A useful description describing this channel")?></span></a></td>
		<td><input size="40" type="text" name="description" value="<?php  echo $description; ?>" tabindex="<?php echo ++$tabindex;?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("DID")?>:<span><?php echo _("The DID that this channel represents. The incoming call on this channel will be treated as if it came in with this DID and can be managed with Inbound Routing on DIDs")?></span></a></td>
		<td><input size="40" type="text" name="did" value="<?php echo $did; ?>"  tabindex="<?php echo ++$tabindex;?>"/></td>
	</tr>

	<tr>
		<td colspan="2"><br><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>" tabindex="<?php echo ++$tabindex;?>">
			<?php if ($extdisplay != '') { echo '&nbsp;<input name="delete" type="submit" onclick="actionDelete=true;" value="'._("Delete").'">'; } ?>
		</td>		

	</tr>
	</table>
</form>
			
			
<script language="javascript">
<!--

var actionDelete = false;

function checkZapchandid(theForm) {
	var msgInvalidChannel = "<?php echo _('Invalid Channel Number, must be numeric and not blank'); ?>";
	var msgInvalidDID = "<?php echo _('Invalid DID, must be a non-blank DID'); ?>";
	var msgConfirmDIDNonStd = "<?php echo _('DID information is normally just an incoming telephone number.\n\nYou have entered a non standard DID pattern.\n\nAre you sure this is correct?'); ?>";
	var msgConfirmConvertDID = "<?php echo _('You appear to be using a converted DID in the form of zapchanNN that was automatically generated during an upgrade. You should consider assigning the DID that is normally associated with this channel to take full advantage of the inbound routing abilities. Changing the DID here will require you to make changes in the Inbound Routes tab. Do you want to continue?'); ?>";


	// If deleting we don't care what is in the elements
	if (actionDelete) {
		actionDelete = false;
		return true;
	}
	// form validation

	defaultEmptyOK = false;
	if (!isInteger(theForm.channel.value)) {
		return warnInvalid(theForm.channel, msgInvalidChannel);
	}
	if (isEmpty(theForm.did.value)) {
		return warnInvalid(theForm.did, msgInvalidDID);
	}
	if (theForm.did.value.substring(0,7) == "zapchan") {
		if (!confirm(msgConfirmConvertDID)) {
			return false;
		}

	} else if (!isDialpattern(theForm.did.value)) {
		if (!confirm(msgConfirmDIDNonStd)) {
			return false;
		}
	}

	return true;
}
//-->
</script>
