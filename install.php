<?php

if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

function did_migrate($incoming){
	global $db;

	foreach ($incoming as $key => $val) { 
		${$key} = addslashes($val); 
	} 

	// Check to make sure the did is not being used elsewhere
	//
	$sql = "SELECT * FROM incoming WHERE cidnum = '' AND extension = '$extension'";
	$existing = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($existing)) {
		outn(sprintf(_("ERROR: trying to check if %s already in use"),$extension));
		return false;
	}
	if (empty($existing)) {
		$sql="INSERT INTO incoming (cidnum,extension,destination,faxexten,faxemail,answer,wait,privacyman,alertinfo, ringing, mohclass, description, grppre) values ('$cidnum','$extension','$destination','$faxexten','$faxemail','$answer','$wait','$privacyman','$alertinfo', '$ringing', '$mohclass', '$description', '$grppre')";
		sql($sql);
		return true;
	} else {
		return false;
	}
}

$fcc = new featurecode('core', 'userlogon');
$fcc->setDescription('User Logon');
$fcc->setDefault('*11');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'userlogoff');
$fcc->setDescription('User Logoff');
$fcc->setDefault('*12');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'zapbarge');
$fcc->setDescription('ZapBarge');
$fcc->setDefault('888');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'chanspy');
$fcc->setDescription('ChanSpy');
$fcc->setDefault('555');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_pstn');
$fcc->setDescription('Simulate Incoming Call');
$fcc->setDefault('7777');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_fax');
$fcc->setDescription('Dial System FAX');
$fcc->setDefault('666');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'pickup');
$fcc->setDescription('Call Pickup (Can be used with GXP-2000)');
$fcc->setDefault('**');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'blindxfer');
$fcc->setDescription('In-Call Asterisk Blind Transfer');
$fcc->setDefault('##');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'atxfer');
$fcc->setDescription('In-Call Asterisk Attended Transfer');
$fcc->setDefault('*2');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'automon');
$fcc->setDescription('In-Call Asterisk Toggle Call Recording');
$fcc->setDefault('*1');
$fcc->update();
unset($fcc);


// Version 2.5 Upgrade needs to migrate directdid user info to incoming table
//
outn(_("Checking if directdids need migrating.."));
$sql = "SELECT `directdid` FROM `users`";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(!DB::IsError($check)) {
	out(_("starting migration"));
	$errors = 0;
	$sql = "SELECT * FROM `users` WHERE `directdid` != '' AND `directdid` IS NOT NULL";
	$direct_dids_arr = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if(!DB::IsError($direct_dids_arr)) {
		foreach ($direct_dids_arr as $direct_dids) {
			$did_vars['destination'] = 'from-did-direct,'.$direct_dids['extension'].',1';
			$did_vars['extension']   = $direct_dids['directdid'];
			$did_vars['cidnum']      = '';
			$did_vars['faxexten']    = $direct_dids['faxexten'];
			$did_vars['faxemail']    = $direct_dids['faxemail'];
			$did_vars['answer']      = $direct_dids['answer'];
			$did_vars['wait']        = $direct_dids['wait'];
			$did_vars['privacyman']  = $direct_dids['privacyman'];
			$did_vars['alertinfo']   = $direct_dids['didalert'];
			$did_vars['ringing']     = '';
			$did_vars['mohclass']    = $direct_dids['mohclass'];
			$did_vars['description'] = _("User: ").$direct_dids['extension'];
			$did_vars['grppre']      = '';
			if (!did_migrate($did_vars)) {
				out(sprintf(_("ERROR: failed to insert %s for user %s"),$direct_dids['directdid'],$direct_dids['extension']));
				$errors++;
			}
		}
		if ($errors) {
			out(sprintf(_("There were %s failures migrating directdids, users table not being changed"),$errors));
		} else {
			$migrate_array = array('directdid', 'didalert', 'mohclass', 'faxexten', 'faxemail', 'answer', 'wait', 'privacyman');
			foreach ($migrate_array as $field) {
				outn(sprintf(_("Removing field %s from users table.."),$field));
				$sql = "ALTER TABLE `users` DROP `".$field."`";
				$results = $db->query($sql);
				if (DB::IsError($results)) { 
					out(_("not present"));
				} else {
					out(_("removed"));
				}
			}
		}
	} else {
		out(_("ERROR: could not access user table to migrate directdids to incoming table, aborting"));
	} 
} else {
	out(_("already done"));
}

// Add callgroup, pickupgroup to zap

outn(_("updating zap callgroup, pickupgroup.."));
$sql = "SELECT `id` FROM `devices` WHERE `tech` = 'zap'";
$results = $db->getCol($sql);
if(DB::IsError($results)) {
	$results = null;
}
$count_pickup = 0;
$count_callgroup = 0;
if (isset($results) && !empty($results)) {
	foreach ($results as $device) {
		// if the insert fails then it is already there since it will violate the primary key but that is ok
		//
		$sql = "INSERT INTO `zap` (`id`, `keyword`, `data`, `flags`) VALUES ('$device', 'callgroup', '', '0')";
		$try = $db->query($sql);
		if(!DB::IsError($try)) {
			$count_pickup++;
		}
		$sql = "INSERT INTO `zap` (`id`, `keyword`, `data`, `flags`) VALUES ('$device', 'pickupgroup', '', '0')";
		$try = $db->query($sql);
		if(!DB::IsError($try)) {
			$count_callgroup++;
		}
	}
}
if ($count_callgroup || $count_pickup) {
	out(sprintf(_("updated %s callgroups, %s pickupgroups"),$count_callgroup,$count_pickup));
} else {
	out(_("not needed"));
}

?>
