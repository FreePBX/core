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
?>

<?php 

$action = $_REQUEST['action'];
$dispnum = "incoming"; //used for switch on config.php


//if submitting form, update database
if ($action == 'editglobals') {
	$globalfields = array(array($_REQUEST['INCOMING'],'INCOMING'),
						array($_REQUEST['REGTIME'],'REGTIME'),
						array($_REQUEST['REGDAYS'],'REGDAYS'),
						array($_REQUEST['AFTER_INCOMING'],'AFTER_INCOMING'),
						array($_REQUEST['IN_OVERRIDE'],'IN_OVERRIDE'));

	$compiled = $db->prepare('UPDATE globals SET value = ? WHERE variable = ?');
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

//query for exisiting aa_N contexts
$unique_aas = getaas();

//get unique extensions
$extens = getextens();

//get unique Ring Groups
$gresults = getgroups();

//get unique queues
$queues = getqueues();
?>

<form name="incoming" action="config.php" method="post">
<input type="hidden" name="display" value="<?php echo $dispnum?>"/>
<input type="hidden" name="action" value="editglobals"/>
<h5><?php echo _("Send")?> <a href="#" class="info"><?php echo _("Incoming Calls")?><span><?php echo _("Dial 7777 from an internal extension to simulate an incoming call")?>.</span></a> <?php echo _("from the")?> <a href="#" class="info"><?php echo _("PSTN")?><span><?php echo _("Public Switched Telephone Network (ie: the phone company)")?></span></a> <?php echo _("to:")?></h5>
<p>
	<?php echo _("regular hours:")?>
	<a href="#" class="info"><b><?php echo _("times")?></b>
		<span><?php echo _("Enter a range, using 24-hour time format. For example, for 8:00am to 5:00pm, type:<br><br>&nbsp;&nbsp;&nbsp;&nbsp;<b>8:00-17:00</b><br><br>An asterisk (*) matches all hours.")?></span>
	</a>
	<input type="text" size="10" name="REGTIME" value="<?php  echo $REGTIME ?>"> 
	<a href="#" class="info"><b><?php echo _("days")?></b>
		<span><?php echo _("Enter a range, using 3 letter abbreviations. For example, for Monday to Friday, type:<br><br>&nbsp;&nbsp;&nbsp;&nbsp;<b>mon-fri</b><br><br>An asterisk (*) matches all days.")?></span>
	</a>
	<input type="text" size="8" name="REGDAYS" value="<?php  echo $REGDAYS ?>">:
</p>
<p> 
	<input type="radio" name="in_indicate" value="ivr" onclick="javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_IVR.options[document.incoming.INCOMING_IVR.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.INCOMING.value=document.incoming.INCOMING_IVR.options[document.incoming.INCOMING_IVR.options.selectedIndex].value;" <?php  echo strpos($INCOMING,'aa_') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Digital Receptionist:")?> 
	<input type="hidden" name="INCOMING" value="<?php  echo $INCOMING; ?>">
	<select name="INCOMING_IVR" onchange="javascript:if (document.incoming.in_indicate[0].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_IVR.options[document.incoming.INCOMING_IVR.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.in_indicate[0].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_IVR.options[document.incoming.INCOMING_IVR.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($unique_aas)) {
		foreach ($unique_aas as $unique_aa) {
			$menu_num = substr($unique_aa[0],strrpos($unique_aa[0],'_')+1);
			$menu_name = $unique_aa[1];
			echo '<option value="'.$unique_aa[0].'" '.($INCOMING == $unique_aa[0] ? 'SELECTED' : '').'>'.($menu_name ? $menu_name : _("Menu #").$menu_num);
		}
	}
?>
	</select><br>
	<input type="radio" name="in_indicate" value="extension" onclick="javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_EXTEN.options[document.incoming.INCOMING_EXTEN.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.INCOMING.value=document.incoming.INCOMING_EXTEN.options[document.incoming.INCOMING_EXTEN.options.selectedIndex].value;"  <?php  echo strpos($INCOMING,'EXT') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Extension:")?>
	<select name="INCOMING_EXTEN" onchange="javascript:if (document.incoming.in_indicate[1].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_EXTEN.options[document.incoming.INCOMING_EXTEN.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.in_indicate[1].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_EXTEN.options[document.incoming.INCOMING_EXTEN.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($extens)) {
		foreach ($extens as $exten) {
			echo '<option value="EXT-'.$exten[0].'" '.($INCOMING == 'EXT-'.$exten[0] ? 'SELECTED' : '').'>'.$exten[1].' <'.$exten[0].'>';
		}
	}
?>		
	</select><br>
	<input type="radio" name="in_indicate" value="group" onclick="javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_GRP.options[document.incoming.INCOMING_GRP.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.INCOMING.value=document.incoming.INCOMING_GRP.options[document.incoming.INCOMING_GRP.options.selectedIndex].value;" <?php  echo strpos($INCOMING,'GR') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Ring Group:")?>
	<select name="INCOMING_GRP" onchange="javascript:if (document.incoming.in_indicate[2].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_GRP.options[document.incoming.INCOMING_GRP.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.in_indicate[2].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_GRP.options[document.incoming.INCOMING_GRP.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($gresults)) {
		foreach ($gresults as $gresult) {
			echo '<option value="GRP-'.$gresult[0].'" '.($INCOMING == 'GRP-'.$gresult[0] ? 'SELECTED' : '').'>#'.$gresult[0];
		}
	}
?>			
	</select><br>
	<input type="radio" name="in_indicate" value="queue" onclick="javascript:document.incoming.INCOMING.value=document.incoming.INCOMING_QUEUE.options[document.incoming.INCOMING_QUEUE.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.INCOMING.value=document.incoming.INCOMING_QUEUE.options[document.incoming.INCOMING_QUEUE.options.selectedIndex].value;" <?php  echo strpos($INCOMING,'QUE') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Queue:")?> 
	<select name="INCOMING_QUEUE" onchange="javascript:if (document.incoming.in_indicate[3].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_QUEUE.options[document.incoming.INCOMING_QUEUE.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.in_indicate[3].checked) document.incoming.INCOMING.value=document.incoming.INCOMING_QUEUE.options[document.incoming.INCOMING_QUEUE.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($queues)) {
		foreach ($queues as $queue) {
			echo '<option value="QUE-'.$queue[0].'" '.($INCOMING == 'QUE-'.$queue[0] ? 'SELECTED' : '').'>'.$queue[0].':'.$queue[1];
		}
	}
?>			
	</select><br>
</p>

<p>
	<?php echo _("after hours:")?> 
</p>
<p> 
	<input type="radio" name="after_in_indicate" value="ivr" onclick="javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_IVR.options[document.incoming.AFTER_INCOMING_IVR.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_IVR.options[document.incoming.AFTER_INCOMING_IVR.options.selectedIndex].value;" <?php  echo strpos($AFTER_INCOMING,'aa_') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Digital Receptionist:")?>
	<input type="hidden" name="AFTER_INCOMING" value="<?php  echo $AFTER_INCOMING; ?>">
	<select name="AFTER_INCOMING_IVR" onchange="javascript:if (document.incoming.after_in_indicate[0].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_IVR.options[document.incoming.AFTER_INCOMING_IVR.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.after_in_indicate[0].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_IVR.options[document.incoming.AFTER_INCOMING_IVR.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($unique_aas)) {
		foreach ($unique_aas as $unique_aa) {
			$menu_num = substr($unique_aa[0],strrpos($unique_aa[0],'_')+1);
			$menu_name = $unique_aa[1];
			echo '<option value="'.$unique_aa[0].'" '.($AFTER_INCOMING == $unique_aa[0] ? 'SELECTED' : '').'>'.($menu_name ? $menu_name : _("Menu #").$menu_num);
		}
	}
?>
	</select><br>
	<input type="radio" name="after_in_indicate" value="extension" onclick="javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_EXTEN.options[document.incoming.AFTER_INCOMING_EXTEN.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_EXTEN.options[document.incoming.AFTER_INCOMING_EXTEN.options.selectedIndex].value;" <?php  echo strpos($AFTER_INCOMING,'EXT') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Extension:")?>
	<select name="AFTER_INCOMING_EXTEN" onchange="javascript:if (document.incoming.after_in_indicate[1].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_EXTEN.options[document.incoming.AFTER_INCOMING_EXTEN.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.after_in_indicate[1].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_EXTEN.options[document.incoming.AFTER_INCOMING_EXTEN.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($extens)) {
		foreach ($extens as $exten) {
			echo '<option value="EXT-'.$exten[0].'" '.($AFTER_INCOMING == 'EXT-'.$exten[0] ? 'SELECTED' : '').'>'.$exten[1].' <'.$exten[0].'>';
		}
	}
?>		
	</select><br>
	<input type="radio" name="after_in_indicate" value="group" onclick="javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_GRP.options[document.incoming.AFTER_INCOMING_GRP.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_GRP.options[document.incoming.AFTER_INCOMING_GRP.options.selectedIndex].value;" <?php  echo strpos($AFTER_INCOMING,'GR') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Ring Group:")?>
	<select name="AFTER_INCOMING_GRP" onchange="javascript:if (document.incoming.after_in_indicate[2].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_GRP.options[document.incoming.AFTER_INCOMING_GRP.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.after_in_indicate[2].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_GRP.options[document.incoming.AFTER_INCOMING_GRP.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($gresults)) {
		foreach ($gresults as $gresult) {
			echo '<option value="GRP-'.$gresult[0].'" '.($AFTER_INCOMING == 'GRP-'.$gresult[0] ? 'SELECTED' : '').'>#'.$gresult[0];
		}
	}
?>			
	</select><br>
	<input type="radio" name="after_in_indicate" value="queue" onclick="javascript:document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_QUEUE.options[document.incoming.AFTER_INCOMING_QUEUE.options.selectedIndex].value;" onkeypress="javascript:if (event.keyCode == 0 || (document.all && event.keyCode == 13)) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_QUEUE.options[document.incoming.AFTER_INCOMING_QUEUE.options.selectedIndex].value;" <?php  echo strpos($AFTER_INCOMING,'QUE') === false ? '' : 'CHECKED=CHECKED';?>/> <?php echo _("Queue:")?> 
	<select name="AFTER_INCOMING_QUEUE" onchange="javascript:if (document.incoming.after_in_indicate[3].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_QUEUE.options[document.incoming.AFTER_INCOMING_QUEUE.options.selectedIndex].value;" onkeypress="javascript:setTimeout('if (document.incoming.after_in_indicate[3].checked) document.incoming.AFTER_INCOMING.value=document.incoming.AFTER_INCOMING_QUEUE.options[document.incoming.AFTER_INCOMING_QUEUE.options.selectedIndex].value;', 100);"/>
<?php 
	if (isset($queues)) {
		foreach ($queues as $queue) {
			echo '<option value="QUE-'.$queue[0].'" '.($AFTER_INCOMING == 'QUE-'.$queue[0] ? 'SELECTED' : '').'>'.$queue[0].':'.$queue[1];
		}
	}
?>			
	</select><br>
</p>

<h5><?php echo _("Override Incoming Calls Settings")?></h5>
<p>
	<input type="radio" name="IN_OVERRIDE" value="none" <?php  echo $IN_OVERRIDE == 'none' ? 'CHECKED=CHECKED' : '' ?>> <?php echo _("no override (obey the above settings)")?><br>
	<input type="radio" name="IN_OVERRIDE" value="forcereghours"<?php  echo $IN_OVERRIDE == 'forcereghours' ? 'CHECKED=CHECKED' : '' ?>> <a href="#" class="info"><?php echo _("force regular hours")?><span><?php echo _("Select this box if you would like to force the above regular hours setting to always take effect.<br><br>  This is useful for occasions when your office needs to remain open after-hours. (ie: open late on Thursday, or open all day on Sunday).")?></span></a><br>
	<input type="radio" name="IN_OVERRIDE" value="forceafthours"<?php  echo $IN_OVERRIDE == 'forceafthours' ? 'CHECKED=CHECKED' : '' ?>> <a href="#" class="info"><?php echo _("force after hours")?><span><?php echo _("Select this box if you would like to force the above after hours setting to always take effect.<br><br>  This is useful for holidays that fall in the 'regular hours' range above (ie: a holiday Monday).")?></span></a>
</p>

<br>
<h6>
	<input name="Submit" type="button" value="<?php echo _("Submit Changes")?>" onclick="checkIncoming(incoming)">
</h6>
</form>

<br><br><br><br><br>
