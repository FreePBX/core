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

function getIncoming(){
	global $db;
	$sql = "SELECT extension,cidnum FROM incoming";
	$results = $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	return $results;
}

function getIncomingInfo($extension="",$cidnum=""){
	global $db;
	$sql = "SELECT * FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	return $results;
}

function delIncoming($extension,$cidnum){
	global $db;
	$sql="DELETE FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
	$results = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	// now delete from extensions table
	
	if(empty($extension)) {
		$extension = "s";
		$catchaccount = "_X.".(empty($cidnum)?"":"/".$cidnum);
	}
	$account = $extension.(empty($cidnum)?"":"/".$cidnum);
	
	$sql="DELETE FROM extensions WHERE context = \"ext-did\" AND extension = \"$account\"";
	$results = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	if ($catchaccount) {
		$sql="DELETE FROM extensions WHERE context = \"ext-did\" AND extension = \"$catchaccount\"";
		$results = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
	}
}

function addIncoming($incoming){
	global $db;
	extract($incoming); // create variables from request
	$existing=getIncomingInfo($extension,$cidnum);
	if (empty($existing)) {
		$destination=buildActualGoto($incoming,0); //temporary workaround to get the actual goto destination string
		$sql="INSERT INTO incoming (cidnum,extension,destination,faxexten,faxemail,answer,wait,privacyman) values (\"$cidnum\",\"$extension\",\"$destination\",\"$faxexten\",\"$faxemail\",\"$answer\",\"$wait\",\"$privacyman\")";
		$results = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
		
		//now write the priorities to the extensions table - This section will change in AMP2
		
		//sub a blank extension with 's'
		$extension = (empty($extension)?"s":$extension);
		$account = $extension.(empty($cidnum)?"":"/".$cidnum); //if a CID num is defined, add it
		if ($extension == "s") {  //if the exten is s, then also make a catchall for undefined DIDs
			$catchaccount = "_X.".(empty($cidnum)?"":"/".$cidnum);
			$addarray[] = array('ext-did',$catchaccount,"1",'Goto',$account,'','0');
		}
		$i=1;
		$addarray[] = array('ext-did',$account,$i++,'SetVar','FROM_DID='.$account,'','0');
		if ($faxexten != "default") {
			$addarray[] = array('ext-did',$account,$i++,'SetVar','FAX_RX='.$faxexten,'','0');
		}
		if (!empty($faxemail)) {
			$addarray[] = array('ext-did',$account,$i++,'SetVar','FAX_RX_EMAIL='.$faxemail,'','0');
		}
		if ($answer == "1") {
			$addarray[] = array('ext-did',$account,$i++,'Answer','','','0');
			$addarray[] = array('ext-did',$account,$i++,'Wait',$wait,'','0');	
		}
		if ($privacyman == "1") {
			$addarray[] = array('ext-did',$account,$i++,'PrivacyManager','','','0');	
		}
		
		if (empty($destination)) { //temporary use of 'incoming calls' until a time of day module is created
			$addarray[] = array('ext-did',$account,$i++,'Goto','from-pstn,s,1','','0');
		} else {
			$addarray[] = array('ext-did',$account,$i++,'Goto',$destination,'','0');
		}
		foreach($addarray as $add) {
			addextensions($add);
		}

	} else {
		echo "<script>javascript:alert('"._("A route for this DID/CID already exists!")."')</script>";
	}
}


//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	
$action = $_REQUEST['action'];
$extdisplay=$_REQUEST['extdisplay'];
$dispnum = 7; //used for switch on config.php

$account = $_REQUEST['account'];	
$goto = $_REQUEST['goto0'];

//update db if submiting form
switch ($action) {
	case 'addIncoming':
		//create variables from request
		extract($_REQUEST);
		//add details to teh 'incoming' table
		addIncoming($_REQUEST);
		exec($wScript1);
		needreload();
	break;
	case 'delIncoming':
		$extarray=explode('/',$extdisplay,2);
		delIncoming($extarray[0],$extarray[1]);
		exec($wScript1);
		needreload();
	break;
	case 'edtIncoming':
		$extarray=explode('/',$extdisplay,2);
		delIncoming($extarray[0],$extarray[1]);
		addIncoming($_REQUEST);
		$extdisplay=$_REQUEST['extension']."/".$_REQUEST['cidnum'];
		exec($wScript1);
		needreload();
	break;
}

?>
</div>

<div class="rnav">
    <li><a id="<?php echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>"><?php echo _("Add Incoming Route")?></a><br></li>
<?php 
//get unique incoming routes
$inroutes = getIncoming();
if (isset($inroutes)) {
	foreach ($inroutes as $inroute) {
		echo "<li><a id=\"".($extdisplay==$inroute['extension']."/".$inroute['cidnum'] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay={$inroute['extension']}/{$inroute['cidnum']}\">{$inroute['extension']}/{$inroute['cidnum']}</a></li>";
	}
}
?>
</div>

<div class="content">
<?php 
	
	if ($action == 'delDID') {
		echo '<br><h3>Route '.$extdisplay.' deleted!</h3><br><br><br><br><br><br><br><br>';
	} else {
		
		$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delIncoming';
?>
<?php if ($extdisplay) {	
	//create variables for the selected route's settings
	$extarray=explode('/',$extdisplay,2);
	$ininfo=getIncomingInfo($extarray[0],$extarray[1]);
	if (is_array($ininfo)) extract($ininfo);
?>
		<h2><?php echo _("Route")?>: <?php echo $extdisplay; ?></h2>
		<p><a href="<?php echo $delURL ?>"><?php echo _("Delete Route")?> <?php echo $extdisplay ?></a></p>
<?php } else { ?>
		<h2><?php echo _("Add Incoming Route")?></h2>
<?php } ?>
		<form name="editGRP" action="<?php $_REQUEST['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="<?php echo $dispnum?>">
		<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edtIncoming' : 'addIncoming') ?>">
		<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>">
		<table>
		<tr><td colspan="2"><h5><?php echo ($extdisplay ? _('Edit Incoming Route') : _('Add Incoming Route')) ?><hr></h5></td></tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("DID Number")?><span><?php echo _('Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.')?></span></a>:</td>
			<td><input type="text" name="extension" value="<?php echo $extension ?>"></td>
		</tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Caller ID Number")?><span><?php echo _('Define the Caller ID Number to be matched on incoming calls.<br><br>Leave this field blank to match any or no CID info.')?></span></a>:</td>
			<td><input type="text" name="cidnum" value="<?php echo $cidnum ?>"></td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr><td colspan="2"><h5><?php echo _("Fax Handling")?><hr></h5></td></tr>
		<tr>
			<td>
				<a class="info" href="#"><?php echo _("Fax Extension")?><span><?php echo _("Select 'system' to have the system receive and email faxes.<br><br>The AMP Default is defined in General Settings.")?></span></a>:
			</td>
			<td>
				<select name="faxexten">
					<option value="default" <?php  echo ($faxexten == 'default' ? 'SELECTED' : '')?>><?php echo _("AMP default")?>
					<option value="disabled" <?php  echo ($faxexten == 'disabled' ? 'SELECTED' : '')?>><?php echo _("disabled")?>
					<option value="system" <?php  echo ($faxexten == 'system' ? 'SELECTED' : '')?>><?php echo _("system")?>
			<?php 
				//get unique devices
				$devices = getdevices();
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
				<a class="info" href="#"><?php echo _("Fax Email")?><span><?php echo _("Email address is used if 'system' has been chosen for the fax extension above.<br><br>Leave this blank to use the AMP default in General Settings.")?></span></a>:
			</td>
			<td>
				<input type="text" size="20" name="faxemail" value="<?php echo $faxemail?>"/>
			</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr><td colspan="2"><h5><?php echo _("Options")?><hr></h5></td></tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Immediate Answer")?><span><?php echo _('Answer calls the moment they are detected?  Note: If using a "Fax Extension" (above) you may wish to enable this so that we can listen for a fax tone.')?></span></a>:</td>
			<td>
				<select name="answer">
					<option value="0" <?php  echo ($answer == '0' ? 'SELECTED' : '')?>><?php echo _("No")?>
					<option value="1" <?php  echo ($answer == '1' ? 'SELECTED' : '')?>><?php echo _("Yes")?>
				</select>
			</td>
		</tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Pause after answer")?><span><?php echo _('The number of seconds we should wait after performing an Immediate Answer. The primary purpose of this is to pause and listen for a fax tone before allowing the call to proceed.')?></span></a>:</td>
			<td><input type="text" name="wait" size="3" value="<?php echo $wait ?>"></td>
		</tr>
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
		
		<tr><td colspan="2"><h5><?php echo _("Set Destination")?><hr></h5></td></tr>
		
<?php 
//draw goto selects
echo drawselects('editGRP',$destination,0);
?>
		
		<tr><td colspan=2>
		<input type="radio" name="goto_indicate0" value="" <?php echo empty($destination) ? 'CHECKED=CHECKED' : '';?> /> 
			<?php echo _("Use 'Incoming Calls' settings");?><br>
			<br>				
		</td></tr>
		
		<tr>
		<td colspan="2"><br><h6>
			<input name="Submit" type="button" value="Submit" onclick="checkDID(editGRP);">
		</h6></td>		
		
		</tr>
		</table>
		</form>
<?php 		
	} //end if action == delGRP
	

?>





