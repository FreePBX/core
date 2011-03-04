<?php
//This file is part of FreePBX.
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
//    Copyright 2007 Philippe Lindheimer
//
//for translation only
if (false) {
_("Core");
_("User Logon");
_("User Logoff");
_("ZapBarge");
_("ChanSpy");
_("Simulate Incoming Call");
_("Directed Call Pickup");
_("Asterisk General Call Pickup");
_("In-Call Asterisk Blind Transfer");
_("In-Call Asterisk Attended Transfer");
_("In-Call Asterisk Toggle Call Recording");
_("In-Call Asterisk Disconnect Code");
}

function did_migrate($incoming){
	global $db;

	foreach ($incoming as $key => $val) { 
		${$key} = $db->escapeSimple($val); 
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

$freepbx_conf =& freepbx_conf::create();

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
$fcc->setProvideDest();
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'chanspy');
$fcc->setDescription('ChanSpy');
$fcc->setDefault('555');
$fcc->setProvideDest();
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_pstn');
$fcc->setDescription('Simulate Incoming Call');
$fcc->setDefault('7777');
$fcc->setProvideDest();
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'pickup');
$fcc->setDescription('Directed Call Pickup');
$fcc->setDefault('**');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'pickupexten');
$fcc->setDescription('Asterisk General Call Pickup');
$fcc->setDefault('*8');
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

// AUTOMIXMON
//
$set['value'] = false;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 0;
$set['name'] = 'Use Automixmon for One-Touch Recording';
$set['description'] = "Starting with Asterisk 1.6, one-touch-recording can be toggled on and off during a call if the dial options had 'x' and/or 'X' options set. When this is set to true, the 'In-Call Asterisk Toggle Call Recording' will use the asterisk 'automixmon' option instead of the 'automon' option to set this. Only one or the other can be set from the GUI. You need to set the proper options of 'x' and/or 'X' when using this, or 'w' and/or 'W' if using the older 'automon' version. Setting this to true will have no effect on systems running Asterisk 1.4 or earlier.";
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('AUTOMIXMON',$set);

$fcc = new featurecode('core', 'disconnect');
$fcc->setDescription('In-Call Asterisk Disconnect Code');
$fcc->setDefault('**');
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

// 2.5 new field
//
outn(_("checking for delay_answer field .."));
$sql = "SELECT `delay_answer` FROM `incoming`";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	$sql = "ALTER TABLE `incoming` ADD `delay_answer` INT(2) DEFAULT NULL";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		out(_("fatal error"));
		die_freepbx($result->getDebugInfo()); 	
	} else {
		out(_("added"));
	}
} else {
	out(_("already exists"));
}

outn(_("checking for pricid field .."));
$sql = "SELECT `pricid` FROM `incoming`";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	$sql = "ALTER TABLE `incoming` ADD `pricid` VARCHAR(20) DEFAULT NULL";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		out(_("fatal error"));
		die_freepbx($result->getDebugInfo()); 	
	} else {
		out(_("added"));
	}
} else {
	out(_("already exists"));
}

// Add variable RECORDING_STATE use to globally disable recording
// TODO: move this to the upgrade script so it only has to be done
//       once in a major upgrade when we move to next major release
//
outn(_("Checking for Global var RECORDING_STATE.."));
$nrows = $db->getOne("SELECT count(*) from globals where variable='RECORDING_STATE'");
if (!$nrows) {
	$db->query("insert into globals values ('RECORDING_STATE', 'ENABLED')");
	out(_("Created"));
} else {
	out(_("Already exists!"));
}

// This next set of functions and code are used to migrate from the old
// global variable storage of trunk data to the new trunk table and trunk
// pattern table for localprefixes.conf
//

//Sort trunks for sqlite
function __sort_trunks($a,$b)  {
        global $unique_trunks;
        preg_match("/OUT_([0-9]+)/",$unique_trunks[$a][0],$trunk_num1);
        preg_match("/OUT_([0-9]+)/",$unique_trunks[$b][0],$trunk_num2);
        return ($trunk_num1[1] >= $trunk_num2[1]? 1:-1);
}

function __migrate_trunks_to_table() {

	global $db;
	global $amp_conf;

	$sql = "
	CREATE TABLE `trunks` 
	( 
		`trunkid` INTEGER,
		`name` VARCHAR( 50 ) NOT NULL DEFAULT '', 
		`tech` VARCHAR( 20 ) NOT NULL , 
		`outcid` VARCHAR( 40 ) NOT NULL DEFAULT '', 
		`keepcid` VARCHAR( 4 ) DEFAULT 'off',
		`maxchans` VARCHAR( 6 ) DEFAULT '',
		`failscript` VARCHAR( 255 ) NOT NULL DEFAULT '', 
		`dialoutprefix` VARCHAR( 255 ) NOT NULL DEFAULT '', 
		`channelid` VARCHAR( 255 ) NOT NULL DEFAULT '', 
		`usercontext` VARCHAR( 255 ) NULL, 
		`provider` VARCHAR( 40 ) NULL, 
		`disabled` VARCHAR( 4 ) DEFAULT 'off',
	
		PRIMARY KEY  (`trunkid`, `tech`, `channelid`) 
	) 
	";
	$check = $db->query($sql);
	if(DB::IsError($check)) {
		if($check->getCode() == DB_ERROR_ALREADY_EXISTS) {
			//echo ("already exists\n");
			return false; 
		} else {
			die_freepbx($check->getDebugInfo());	
		}
	}
	
	// sqlite doesn't support the syntax required for the SQL so we have to do it the hard way
	if ($amp_conf["AMPDBENGINE"] == "sqlite3") {
		$sqlstr = "SELECT variable, value FROM globals WHERE variable LIKE 'OUT\_%' ESCAPE '\'";
		$my_unique_trunks = sql($sqlstr,"getAll",DB_FETCHMODE_ASSOC);

		$sqlstr = "SELECT variable, value FROM globals WHERE variable LIKE 'OUTDISABLE\_%' ESCAPE '\'";
		$disable_states = sql($sqlstr,"getAll",DB_FETCHMODE_ASSOC);

		foreach($disable_states as $arr)  {
			$disable_states_assoc[$arr['variable']] = $arr['value'];
		}
		global $unique_trunks;
		$unique_trunks = array();

		foreach ($my_unique_trunks as $this_trunk) {

			$trunk_num = substr($this_trunk['variable'],4);
			$this_state = (isset($disable_states_assoc['OUTDISABLE_'.$trunk_num]) ? $disable_states_assoc['OUTDISABLE_'.$trunk_num] : 'off');
			$unique_trunks[] = array($this_trunk['variable'], $this_trunk['value'], $this_state);
		}
		// sort this array using a custom function __sort_trunks(), defined above
		uksort($unique_trunks,"__sort_trunks");
		// re-index the newly sorted array
		foreach($unique_trunks as $arr) {
			$unique_trunks_t[] = array($arr[0],$arr[1],$arr[2]);
		}
		$unique_trunks = $unique_trunks_t;

	} else {
		$sqlstr  = "SELECT t.variable, t.value, d.value state FROM `globals` t ";
		$sqlstr .= "JOIN (SELECT x.variable, x.value FROM globals x WHERE x.variable LIKE 'OUTDISABLE\_%') d ";
		$sqlstr .= "ON substring(t.variable,5) = substring(d.variable,12) WHERE t.variable LIKE 'OUT\_%' ";
		$sqlstr .= "UNION ALL ";
		$sqlstr .= "SELECT v.variable, v.value, concat(substring(v.value,1,0),'off') state  FROM `globals` v ";
		$sqlstr .= "WHERE v.variable LIKE 'OUT\_%' AND concat('OUTDISABLE_',substring(v.variable,5)) NOT IN ";
		$sqlstr .= " ( SELECT variable from globals WHERE variable LIKE 'OUTDISABLE\_%' ) ";
		$sqlstr .= "ORDER BY variable";
		$unique_trunks = sql($sqlstr,"getAll");
	}

	$trunkinfo = array();
	foreach ($unique_trunks as $trunk) {
		list($tech,$name) = explode('/',$trunk[1]);
		$trunkid = ltrim($trunk[0],'OUT_');

		$sqlstr = "
			SELECT `variable`, `value` FROM `globals` WHERE `variable` IN (
				'OUTCID_$trunkid', 'OUTFAIL_$trunkid', 'OUTKEEPCID_$trunkid',
				'OUTMAXCHANS_$trunkid', 'OUTPREFIX_$trunkid')
		";
		$trunk_attribs = sql($sqlstr,'getAll',DB_FETCHMODE_ASSOC);
		$trunk_attrib_hash = array();
		foreach ($trunk_attribs as $attribs) {
			$trunk_attrib_hash[$attribs['variable']] = $attribs['value'];
		}

		switch ($tech) {
			case 'SIP':
				$tech = 'sip';
				$user = sql("SELECT `data` FROM `sip` WHERE `id` = '99999$trunkid' AND `keyword` = 'account'",'getOne');
				break;
			case 'IAX':
			case 'IAX2':
				$tech = 'iax';
				$user = sql("SELECT `data` FROM `iax` WHERE `id` = '99999$trunkid' AND `keyword` = 'account'",'getOne');
				break;
			case 'ZAP':
			case 'DUNDI':
			case 'ENUM':
				$tech = strtolower($tech);
				$user = '';
				break;
			default:
				if (substr($tech,0,4) == 'AMP:') {
					$tech='custom';
					$name = substr($trunk[1],4);
				} else {
					$tech = strtolower($tech);
				}
				$user = '';
		}

		$trunkinfo[] = array(
			'trunkid' =>       $trunkid,
			'tech' =>          $tech,
			'outcid' =>        $trunk_attrib_hash['OUTCID_'.$trunkid],
			'keepcid' =>       $trunk_attrib_hash['OUTKEEPCID_'.$trunkid],
			'maxchans' =>      $trunk_attrib_hash['OUTMAXCHANS_'.$trunkid],
			'failscript' =>    $trunk_attrib_hash['OUTFAIL_'.$trunkid],
			'dialoutprefix' => $trunk_attrib_hash['OUTPREFIX_'.$trunkid],
			'channelid' =>     $name,
			'usercontext' =>   $user,
			'disabled' =>      $trunk[2], // disable state
		);	

		$sqlstr = "INSERT INTO `trunks` 
			( trunkid, tech, outcid, keepcid, maxchans, failscript, dialoutprefix, channelid, usercontext, disabled) 
			VALUES (
				'".$db->escapeSimple($trunkid)."',
				'".$db->escapeSimple($tech)."',
				'".$db->escapeSimple($trunk_attrib_hash['OUTCID_'.$trunkid])."',
				'".$db->escapeSimple($trunk_attrib_hash['OUTKEEPCID_'.$trunkid])."',
				'".$db->escapeSimple($trunk_attrib_hash['OUTMAXCHANS_'.$trunkid])."',
				'".$db->escapeSimple($trunk_attrib_hash['OUTFAIL_'.$trunkid])."',
				'".$db->escapeSimple($trunk_attrib_hash['OUTPREFIX_'.$trunkid])."',
				'".$db->escapeSimple($name)."',
				'".$db->escapeSimple($user)."',
				'".$db->escapeSimple($trunk[2])."'
		  )
		";
		sql($sqlstr);
	}

	return $trunkinfo;
}

// __migrate_trunks_to_table will return false if the trunks table already exists and
// no migration is needed
//
outn(_("Checking if trunk table migration required.."));
$trunks = __migrate_trunks_to_table();
if ($trunks !== false) {
	outn(_("migrating.."));
	foreach ($trunks as $trunk) {
		$tech = $trunk['tech'];
		$trunkid = $trunk['trunkid'];
		switch ($tech) {
			case 'sip':
			case 'iax':
				$sql = "UPDATE `$tech` SET `id` = 'tr-peer-$trunkid' WHERE `id` = '9999$trunkid'";
				sql($sql);
				$sql = "UPDATE `$tech` SET `id` = 'tr-user-$trunkid' WHERE `id` = '99999$trunkid'";
				sql($sql);
				$sql = "UPDATE `$tech` SET `id` = 'tr-reg-$trunkid' WHERE `id` = '9999999$trunkid' AND `keyword` = 'register'";
				sql($sql);
				break;
			default:
				break;
		}
	}
	outn(_("removing globals.."));
	// Don't do this above, in case something goes wrong
	//
	// At this point we have created our trunks table and update the sip and iax files
	// time to get rid of the old globals which will not be auto-generated
	//
	foreach ($trunks as $trunk) {
		$trunkid = $trunk['trunkid'];

		$sqlstr = "
			DELETE FROM `globals` WHERE `variable` IN (
				'OUTCID_$trunkid', 'OUTFAIL_$trunkid', 'OUTKEEPCID_$trunkid',
				'OUTMAXCHANS_$trunkid', 'OUTPREFIX_$trunkid', 'OUT_$trunkid',
				'OUTDISABLE_$trunkid'
			)
		";
		sql($sqlstr);
	}
	out(_("done"));
} else {
	out(_("not needed"));
}

outn(_("Checking if privacy manager options exists.."));
$check = $db->query('SELECT pmmaxretries FROM incoming');
if(DB::IsError($check)){
	$result = $db->query('alter table incoming add pmmaxretries varchar(2), add pmminlength varchar(2);');
	if(DB::IsError($result)) {
		die_freepbx($result->getDebugInfo().'fatal error adding fields to incoming table');	
	} else {
	  out(_("Added pmmaxretries and pmminlength"));
  }
}else{
	out(_("already exists"));
}

// This has already been done in the framework upgrades but is repeated
// here until confirmed there is no path where that code may not have been
// executed.
//
$new_cols = array('noanswer_cid','busy_cid','chanunavail_cid');
foreach ($new_cols as $col) {
  outn(sprintf(_("Checking for %s field.."),$col));
  $sql = "SELECT $col FROM `users`";
  $check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
  if(DB::IsError($check)) {
    // add new field
    $sql = "ALTER TABLE `users` ADD `$col` VARCHAR( 20 ) DEFAULT '';";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
    out(_("added"));
  } else {
    out(_("already exists"));
  }
}

$new_cols = array('noanswer_dest','busy_dest','chanunavail_dest');
foreach ($new_cols as $col) {
  outn(sprintf(_("Checking for %s field.."),$col));
  $sql = "SELECT $col FROM `users`";
  $check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
  if(DB::IsError($check)) {
    // add new field
    $sql = "ALTER TABLE `users` ADD `$col` VARCHAR( 255 ) DEFAULT '';";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
    out(_("added"));
  } else {
    out(_("already exists"));
  }
}

// The following are from General Settings that may need to be migrated.
// We will first create them all, the define_conf_settings() method will
// not change the value if already set. We will update the settings
// to the currently configured values from the globals table afer defining
// them here and then remove them from the globals table.

$globals_convert['VMX_CONTEXT'] = 'from-internal';
$globals_convert['VMX_PRI'] = '1';
$globals_convert['VMX_TIMEDEST_CONTEXT'] = '';
$globals_convert['VMX_TIMEDEST_EXT'] = 'dovm';
$globals_convert['VMX_TIMEDEST_PRI'] = '1';
$globals_convert['VMX_LOOPDEST_CONTEXT'] = '';
$globals_convert['VMX_LOOPDEST_EXT'] = 'dovm';
$globals_convert['VMX_LOOPDEST_PRI'] = '1';
$globals_convert['MIXMON_DIR'] = '';
$globals_convert['MIXMON_POST'] = '';

// VMX_CONTEXT
//
$set['value'] = $globals_convert['VMX_CONTEXT'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 0;
$set['name'] = 'VMX Default Context';
$set['description'] = 'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this.';
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('VMX_CONTEXT',$set);

// VMX_PRI
//
$set['value'] = $globals_convert['VMX_PRI'];
$set['defaultval'] =& $set['value'];
$set['options'] = array(1,1000);
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 0;
$set['name'] = 'VMX Default Priority';
$set['description'] = 'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this.';
$set['type'] = CONF_TYPE_INT;
$freepbx_conf->define_conf_setting('VMX_PRI',$set);

// VMX_TIMEDEST_CONTEXT
//
$set['value'] = $globals_convert['VMX_TIMEDEST_CONTEXT'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 1;
$set['name'] = 'VMX Default Timeout Context';
$set['description'] = "Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they don't press any key (timeout) or press # which is interpreted as a timeout. Set this to 'dovm' to go to voicemail (default).";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('VMX_TIMEDEST_CONTEXT',$set);

// VMX_TIMEDEST_EXT
//
$set['value'] = $globals_convert['VMX_TIMEDEST_EXT'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 0;
$set['name'] = 'VMX Default Timeout Extension';
$set['description'] = "Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they don't press any key (timeout) or press # which is interpreted as a timeout. Set this to 'dovm' to go to voicemail (default).";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('VMX_TIMEDEST_EXT',$set);

// VMX_TIMEDEST_PRI
//
$set['value'] = $globals_convert['VMX_TIMEDEST_PRI'];
$set['defaultval'] =& $set['value'];
$set['options'] = array(1,1000);
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 0;
$set['name'] = 'VMX Default Timeout Priority';
$set['description'] = "Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they don't press any key (timeout) or press # which is interpreted as a timeout. Set this to 'dovm' to go to voicemail (default).";
$set['type'] = CONF_TYPE_INT;
$freepbx_conf->define_conf_setting('VMX_TIMEDEST_PRI',$set);

// VMX_LOOPDEST_CONTEXT
//
$set['value'] = $globals_convert['VMX_LOOPDEST_CONTEXT'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 1;
$set['name'] = 'VMX Default Loop Exceed Context';
$set['description'] = "Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they press an invalid options too many times, as defined by the Maximum Loops count.";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('VMX_LOOPDEST_CONTEXT',$set);

// VMX_LOOPDEST_EXT
//
$set['value'] = $globals_convert['VMX_LOOPDEST_EXT'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 0;
$set['name'] = 'VMX Default Loop Exceed Extension';
$set['description'] = "Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they press an invalid options too many times, as defined by the Maximum Loops count.";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('VMX_LOOPDEST_EXT',$set);

// VMX_LOOPDEST_PRI
//
$set['value'] = $globals_convert['VMX_LOOPDEST_PRI'];
$set['defaultval'] =& $set['value'];
$set['options'] = array(1,1000);
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'VmX Locater';
$set['emptyok'] = 0;
$set['name'] = 'VMX Default Loop Exceed Priority';
$set['description'] = "Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they press an invalid options too many times, as defined by the Maximum Loops count.";
$set['type'] = CONF_TYPE_INT;
$freepbx_conf->define_conf_setting('VMX_LOOPDEST_PRI',$set);

// MIXMON_DIR
//
$set['value'] = $globals_convert['MIXMON_DIR'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'Directory Layout';
$set['emptyok'] = 1;
$set['name'] = 'Override Call Recording Location';
$set['description'] = "Override the default location where asterisk will store call recordings. Be sure to set proper permissions on the directory for the asterisk user.";
$set['type'] = CONF_TYPE_DIR;
$freepbx_conf->define_conf_setting('MIXMON_DIR',$set);

// MIXMON_POST
//
$set['value'] = $globals_convert['MIXMON_POST'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'Developer and Customization';
$set['emptyok'] = 1;
$set['name'] = 'Post Call Recording Script';
$set['description'] = "An optional script to be run after the call is hangup. You can include channel and MixMon variables like \${CALLFILENAME}, \${MIXMON_FORMAT} and \${MIXMON_DIR}. To ensure that you variables are properly escaped, use the following notation: ^{MY_VAR}";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('MIXMON_POST',$set);


// Get all the globals that need to be migrated, then prepare the
// update array to set the current settings in freepbx_conf before
// deleting them.
// 
$sql = "SELECT `variable`, `value`";
$sql_where = " FROM globals WHERE `variable` IN ('".implode("','",array_keys($globals_convert))."')";
$sql .= $sql_where;
$globals = $db->getAll($sql,DB_FETCHMODE_ASSOC);
if(DB::IsError($globals)) {
  die_freepbx($globals->getMessage());
}
outn(_("Checking for General Setting migrations.."));
if (count($globals)) {
  out(_("preparing"));
  foreach ($globals as $global) {
    $update_arr[trim($global['variable'])] = $global['value'];	
    out(sprintf(_("%s prepared"),$global['variable']));
  }
  // Now set the values differently from the defaults, and commit
  $freepbx_conf->set_conf_values($update_arr,true);
} else {
  out(_("not needed"));
  // commit the previous defines if we didn't upate anything
  $freepbx_conf->commit_conf_settings();
}

// Now Delete The Globals
//
if (count($globals)) {
	out(_("General Settings migrated"));
	outn(_("Deleting migrated settings.."));
  $sql = "DELETE".$sql_where;
  $globals = $db->query($sql);
  if(DB::IsError($globals)) {
	  out(_("Fatal DB error trying to delete globals, trying to carry on"));
  } else {
	  out(_("done"));
  }
}

// It's possible that SQL, LOG_SQL values could still bein in AMPSYSLOGLEVEL if amportal.conf
// remained writable. Once changed, this will set it properly next time core is upgraded since
// Framework upgrade scripts only run based on current version.
//
$log_level = strtoupper($amp_conf['AMPSYSLOGLEVEL']);
if ($log_level == 'SQL' || $log_level == 'LOG_SQL') {
  outn(sprintf(_("Discontinued logging type %s changing to %s.."),$log_level,'FILE'));
  $freepbx_conf->set_conf_values(array('AMPSYSLOGLEVEL' => 'FILE'));
  out(_("ok"));
}
// AMPSYSLOGLEVEL
unset($set);
$set['value'] = 'FILE';
$set['options'] = 'FILE, LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG';
$freepbx_conf->define_conf_setting('AMPSYSLOGLEVEL',$set,true);

// Convert IAX notransfer to transfer (since 1.4)
//
outn(_("Converting IAX notransfer to transfer if needed.."));
$affected_rows = 0;
sql("UPDATE iax SET keyword = 'transfer', data = 'yes' WHERE keyword = 'notransfer' AND LOWER(data) = 'no'");
$affected_rows .= $db->affectedRows();
sql("UPDATE iax SET keyword = 'transfer', data = 'no' WHERE keyword = 'notransfer' AND LOWER(data) = 'yes'");
$affected_rows .= $db->affectedRows();
sql("UPDATE iax SET keyword = 'transfer' WHERE keyword = 'notransfer' AND LOWER(data) = 'mediaonly'");
$affected_rows .= $db->affectedRows();
$affected_rows ? out(sprintf(_("updated %s records"),$affected_rows)) : out(_("not needed"));
