<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

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

$freepbx_conf = freepbx_conf::create();

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

global $version;
if(version_compare($version, "12.5", "<")) {
	$fcc = new featurecode('core', 'zapbarge');
	$fcc->setDescription('ZapBarge');
	$fcc->setDefault('888');
	$fcc->setProvideDest();
	$fcc->update();
	unset($fcc);
} else {
	$fcc = new featurecode('core', 'zapbarge');
	$fcc->delete();
}

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

$fcc = new featurecode('core', 'disconnect');
$fcc->setDescription('In-Call Asterisk Disconnect Code');
$fcc->setDefault('**');
$fcc->update();
unset($fcc);

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
$globals_convert['MIXMON_FORMAT'] = 'wav';

$globals_convert['DIAL_OPTIONS'] = 'Ttr';
$globals_convert['TRUNK_OPTIONS'] = 'T';
$globals_convert['RINGTIMER'] = '15';
$globals_convert['TONEZONE'] = 'us';

// OUTBOUND_CID_UPDATE
//
$set['value'] = true;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 0;
$set['name'] = 'Display CallerID on Calling Phone';
$set['description'] = "When set to true and when CONNECTEDLINE() capabilities are configured and supported by your handset, the CID value being transmitted on this call will be updated on your handset in the CNAM field prepended with CID: so you know what is being presented to the caller if the outbound trunk supports and honors setting the transmitted CID.";
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('OUTBOUND_CID_UPDATE',$set);

// OUTBOUND_DIAL_UPDATE
//
$set['value'] = true;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 0;
$set['name'] = 'Display Dialed Number on Calling Phone';
$set['description'] = "When set to true and when CONNECTEDLINE() capabilities are configured and supported by your handset, the number actually dialled will be updated on your handset in the CNUM field. This allows you to see the final manipulation of your number after outbound route and trunk dial manipulation rules have been applied. For example, if you have configured 7 digit dialing on a North America dialplan, the ultimate 10 or 11 digit transmission will be displayed back. Any 'Outbound Dial Prefixes' configured at the trunk level will NOT be shown as these are foten analog line pauses (w) or other characters that distort the CNUM field on updates.";
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('OUTBOUND_DIAL_UPDATE',$set);

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


// MIXMON_FORMAT
$set['value'] = $globals_convert['MIXMON_FORMAT'];
$set['defaultval'] =& $set['value'];
$set['options'] = array('wav','WAV','ulaw','ulaw','alaw','sln','gsm','g729');
$set['name'] = 'Call Recording Format';
$set['description'] = "Format to save recoreded calls for most call recording unless specified differently in specific applications.";
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'System Setup';
$set['emptyok'] = 0;
$set['type'] = CONF_TYPE_SELECT;
$freepbx_conf->define_conf_setting('MIXMON_FORMAT',$set);

// DIAL_OPTIONS
//
$set['value'] = $globals_convert['DIAL_OPTIONS'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 1;
$set['name'] = 'Asterisk Dial Options';
$set['description'] = "Options to be passed to the Asterisk Dial Command when making internal calls or for calls ringing internal phones. The options are documented in Asterisk documentation, a subset of which are described here. The default options T and t allow the calling and called users to transfer a call with ##. If 'Disallow transfer features for inbound callers' is set to 'Yes' the T option is removed for inbound callers. The r option allows Asterisk to generate ringing back to the calling phones which is needed by some phones and sometimes needed in complex dialplan features that may otherwise result in silence to the caller.";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('DIAL_OPTIONS',$set);

// TRUNK_OPTIONS
//
$set['value'] = $globals_convert['TRUNK_OPTIONS'];
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 1;
$set['name'] = 'Asterisk Outbound Trunk Dial Options';
$set['description'] = "Options to be passed to the Asterisk Dial Command when making outbound calls on your trunks when not part of an Intra-Company Route. The options are documented in Asterisk documentation, a subset of which are described here. The default option T allows the calling user to transfer a call with ##. It is HIGHLY DISCOURAGED to use the r option here as this will prevent early media from being delivered from the PSTN and can result in the inability to interact with some external IVRs";
$set['type'] = CONF_TYPE_TEXT;
$freepbx_conf->define_conf_setting('TRUNK_OPTIONS',$set);

// INBOUND_NOTRANS
//
$set['value'] = true;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 0;
$set['name'] = 'Disallow transfer features for inbound callers';
$set['description'] = "Disallow transfer features (Normally ## and *2) for callers who passthrough inbound routes (Such as external callers)";
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('INBOUND_NOTRANS',$set);

// RINGTIMER
$opts = array();
for ($i=0;$i<=120;$i++) {
	$opts[]=$i;
}
$set['value'] = $globals_convert['RINGTIMER'];
$set['defaultval'] =& $set['value'];
$set['options'] = $opts;
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['name'] = 'Ringtime Default';
$set['description'] = 'Default number of seconds to ring phones before sending callers to voicemail or other extension destinations. This can be set per extension/user. Phones with no voicemail or other destination options will ring indefinitely.';
$set['type'] = CONF_TYPE_SELECT;
$freepbx_conf->define_conf_setting('RINGTIMER',$set);
unset($opts);

// CONNECTEDLINE_PRESENCESTATE
//
$set['value'] = true;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['category'] = 'Dialplan and Operational';
$set['emptyok'] = 0;
$set['name'] = 'Display Presence State of Callee';
$set['description'] = "When set to true and when CONNECTEDLINE() capabilities are configured and supported by your handset, the name displayed will include the presence state of the callee.";
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('CONNECTEDLINE_PRESENCESTATE',$set);

// TONEZONE
// This function will assure the table is there and then create/setup the Advanced Setting
//
_core_create_update_tonezones('us',false);


// AMPSYSLOGLEVEL
unset($set);
$set['value'] = 'FILE';
$set['options'] = 'FILE, LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG';
$freepbx_conf->define_conf_setting('AMPSYSLOGLEVEL',$set,true);

out(_("Migrating pickup groups to named pickup groups"));
$sql = "update sip set keyword = 'namedpickupgroup' where keyword = 'pickupgroup'";
sql($sql);
$sql = "update dahdi set keyword = 'namedpickupgroup' where keyword = 'pickupgroup'";
sql($sql);
out(_("Migrating call groups to named call groups"));
$sql = "update sip set keyword = 'namedcallgroup' where keyword = 'callgroup'";
sql($sql);
$sql = "update dahdi set keyword = 'namedcallgroup' where keyword = 'callgroup'";
sql($sql);

//FREEPBX-11259 On previous versions of Core quick create would set emergency_cid to the description. This may be a bad thing.
//We are looking at all devices to see in emergency_cid = description and that it is NOT all numeric. If so we are blanking
//Out the emergency_cid and setting an alert.

//Clean up old notifications. We will add back a unified notice if needed
$nt = \notifications::create();
$curnotif = $nt->list_critical();
foreach ($curnotif as $notif) {
	if($notif['module'] == 'core' && substr($notif['id'], 0,10) == 'core_ecid_'){
		$nt->delete('core', $notif['id']);
	}
}
outn(_("Checking for possibly invalid emergency caller id fields.."));
$sql = "select a.id, a.description from devices as a, devices as b where a.description = b.emergency_cid AND concat('',b.emergency_cid * 1) != b.emergency_cid";
$devices = $db->getAll($sql,DB_FETCHMODE_ASSOC);
if (DB::IsError($devices)) {
	return false;
}
if (count($devices)) {
	$rawname = 'core';
	outn("\n\t");
	out(_("Found what appear to be invalid emergency callerid entries."));
	$badcids = array();
	foreach ($devices as $dev) {
		$badcids[] = sprintf(_("Device %s (%s) had an invalid emergency callerid and we set it to blank"),$dev['description'],$dev['id']);
	}
	$uid = 'core_bad_ecid';
	if(!$nt->exists($rawname, $uid)) {
		$nt->add_critical($rawname, $uid, _("Emergency CID set to blank for one or more extensions"),implode(PHP_EOL, $badcids), $link="?display=extensions", true, true);
	}
	$sql = "update devices a, devices b SET b.emergency_cid = '' WHERE a.description = b.emergency_cid AND concat('',b.emergency_cid * 1) != b.emergency_cid;";
	$db->query($sql);
} else {
	out(_("none found"));
}


function _core_create_update_tonezones($tz = 'us', $commit = true) {
	global $db, $freepbx_conf;

	// If we still have a function that supplies the initial zones, spit them out and update the DB
	//
	$zonelist = _initialize_zonelist();
	$compiled = $db->prepare('REPLACE INTO `indications_zonelist` (`name`, `iso`, `conf`) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$zonelist);
	if(DB::IsError($result)) {
		out("error initializing indications_zonelist");
		return false;
	}

	// Now get what ever we have and update it in the FreePBX Settings choices in case the DB has been modified
	//
	$sql = 'SELECT `name`, `iso` FROM `indications_zonelist` ORDER BY `name`';
	$zl = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	$zlist = array();
	foreach ($zl as $z) {
		$zlist[$z['iso']] = $z['name'];
	}

	// Now define the setting for the first time if needed, if already defined the $tz won't matter but this
	// will update the options list if it has changed
	//

	// TONEZONE
	//
	$set['value'] = $tz;
	$set['defaultval'] = 'us';
	$set['options'] = $zlist;
	$set['readonly'] = 0;
	$set['hidden'] = 0;
	$set['level'] = 0;
	$set['module'] = '';
	$set['category'] = 'Dialplan and Operational';
	$set['name'] = 'Country Indication Tones';
	$set['description'] = 'Choose the country tonezone that you would like Asterisk to use when creating the different standard telephony tones such as ringing, busy, congetstion, etc.';
	$set['type'] = CONF_TYPE_FSELECT;
	$freepbx_conf->define_conf_setting('TONEZONE',$set,$commit);
	unset($zlist);
}

function _initialize_zonelist() {
        return array(
 array ( "name" => "Angola",  "iso" => "ao", "conf" => "ringcadence = 1000,5000\nbusy = 425/500,0/500\ncongestion = 500/500,0500\ndial = 425\nringing = 25/1000,0/5000\ncallwaiting = 400/1000,0/5000\n"),
 array ( "name" => "Argentina",  "iso" => "ar", "conf" => "ringcadence = 1000,4500\ndial = 425\nbusy = 425/300,0/300\nring = 425/1000,0/4500\ncongestion = 425/200,0/300\ncallwaiting = 425/200,0/9000\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425/330,0/330,425/660,0/660\nrecord = 1400/500,0/14000\ninfo = 425/100,0/100\nstutter = 425/450,0/50\n"),
 array ( "name" => "Australia",  "iso" => "au", "conf" => "ringcadence = 400,200,400,2000\ndial = 413+438\nbusy = 425/375,0/375\nring = 413+438/400,0/200,413+438/400,0/2000\ncongestion = 425/375,0/375,420/375,0/375\ncallwaiting = 425/200,0/200,425/200,0/4400\ndialrecall = 413+438\nrecord = !425/1000,!0/15000,425/360,0/15000\ninfo = 425/2500,0/500\nstd = !525/100,!0/100,!525/100,!0/100,!525/100,!0/100,!525/100,!0/100,!525/100\nfacility = 425\nstutter = 413+438/100,0/40\nringmobile = 400+450/400,0/200,400+450/400,0/2000\n"),
 array ( "name" => "Austria",  "iso" => "at", "conf" => "ringcadence = 1000,5000\ndial = 420\nbusy = 420/400,0/400\nring = 420/1000,0/5000\ncongestion = 420/200,0/200\ncallwaiting = 420/40,0/1960\ndialrecall = 420\nrecord = 1400/80,0/14920\ninfo = 950/330,1450/330,1850/330,0/1000\nstutter = 380+420\n"),
 array ( "name" => "Belgium",  "iso" => "be", "conf" => "ringcadence = 1000,3000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/3000\ncongestion = 425/167,0/167\ncallwaiting = 1400/175,0/175,1400/175,0/3500\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = 900/330,1400/330,1800/330,0/1000\nstutter = 425/1000,0/250\n"),
 array ( "name" => "Brazil",  "iso" => "br", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/4000\ncongestion = 425/250,0/250,425/750,0/250\ncallwaiting = 425/50,0/1000\ndialrecall = 350+440\nrecord = 425/250,0/250\ninfo = 950/330,1400/330,1800/330\nstutter = 350+440\n"),
 array ( "name" => "Bulgaria",  "iso" => "bg", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 425/150,0/150,425/150,0/4000\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/425,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425/1500,0/100\n"),
 array ( "name" => "Chile",  "iso" => "cl", "conf" => "ringcadence = 1000,3000\ndial = 400\nbusy = 400/500,0/500\nring = 400/1000,0/3000\ncongestion = 400/200,0/200\ncallwaiting = 400/250,0/8750\ndialrecall = !400/100,!0/100,!400/100,!0/100,!400/100,!0/100,400\nrecord = 1400/500,0/15000\ninfo = 950/333,1400/333,1800/333,0/1000\nstutter = !400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,400\n"),
 array ( "name" => "China",  "iso" => "cn", "conf" => "ringcadence = 1000,4000\ndial = 450\nbusy = 450/350,0/350\nring = 450/1000,0/4000\ncongestion = 450/700,0/700\ncallwaiting = 450/400,0/4000\ndialrecall = 450\nrecord = 950/400,0/10000\ninfo = 450/100,0/100,450/100,0/100,450/100,0/100,450/400,0/400\nstutter = 450+425\n"),
 array ( "name" => "Colombia (Republic of)", "iso" => "co", "conf" => "ringcadance = 1000,4000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/4500\ncongestion = 425/100,0/250,425/350,0/250,425/650,0/250\ncallwaiting = 400+450/300,0/6000\ndialrecall = 425\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0/1000\n"),
 array ( "name" => "Costa Rica",  "iso" => "cr", "conf" => "ringcadence = 1203,4797\ndial = 450\nbusy = 450/330,0/330\nring = 450/1200,0/4900\ncongestion = 450/330,0/330\ncallwaiting = 450/150,0/150,450/150,0/8000\dialrecall = !450/100,!0/100,!450/100,!0/100,!450/100,!0/100,450\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !450/100,!0/100,!450/100,!0/100,!450/100,!0/100,!450/100,!0/100,!42\n"),
 array ( "name" => "Czech Republic",  "iso" => "cz", "conf" => "ringcadence = 1000,4000\ndial = 425/330,0/330,425/660,0/660\nbusy = 425/330,0/330\nring = 425/1000,0/4000\ncongestion = 425/165,0/165\ncallwaiting = 425/330,0/9000\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425/330,0/330,425/660,0/660\nrecord = 1400/500,0/14000\ninfo = 950/330,0/30,1400/330,0/30,1800/330,0/1000\nstutter = 425/450,0/50\n"),
 array ( "name" => "Denmark",  "iso" => "dk", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = !425/200,!0/600,!425/200,!0/3000,!425/200,!0/200,!425/200,0\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425/450,0/50\n"),
 array ( "name" => "Estonia",  "iso" => "ee", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/300,0/300\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 950/650,0/325,950/325,0/30,1400/1300,0/2600\ndialrecall = 425/650,0/25\nrecord = 1400/500,0/15000\ninfo = 950/650,0/325,950/325,0/30,1400/1300,0/2600\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Finland",  "iso" => "fi", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/300,0/300\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/150,0/150,425/150,0/8000\ndialrecall = 425/650,0/25\nrecord = 1400/500,0/15000\ninfo = 950/650,0/325,950/325,0/30,1400/1300,0/2600\nstutter = 425/650,0/25\n"),
 array ( "name" => "France",  "iso" => "fr", "conf" => "ringcadence = 1500,3500\ndial = 440\nbusy = 440/500,0/500\nring = 440/1500,0/3500\ncongestion = 440/250,0/250\ncallwait = 440/300,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330\nstutter = !440/100,!0/100,!440/100,!0/100,!440/100,!0/100,!440/100,!0/100,!440/100,!0/100,!440/100,!0/100,440\n"),
 array ( "name" => "Germany",  "iso" => "de", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/480,0/480\nring = 425/1000,0/4000\ncongestion = 425/240,0/240\ncallwaiting = !425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,0\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425+400\n"),
 array ( "name" => "Greece",  "iso" => "gr", "conf" => "ringcadence = 1000,4000\ndial = 425/200,0/300,425/700,0/800\nbusy = 425/300,0/300\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/150,0/150,425/150,0/8000\ndialrecall = 425/650,0/25\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 425/650,0/25\n"),
 array ( "name" => "Hong Kong", "iso" => "hk", "conf" => "ringcadence = 400,200,400,3000\ndial = 350+440\nbusy = 480+620/500,0/500\nring = 440+480/400,0/200,440+480/400,0/3000\ncongestion = 480+620/250,0/250\ncallwaiting = 440/300,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Hungary",  "iso" => "hu", "conf" => "ringcadence = 1250,3750\ndial = 425\nbusy = 425/300,0/300\nring = 425/1250,0/3750\ncongestion = 425/300,0/300\ncallwaiting = 425/40,0/1960\ndialrecall = 425+450\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 350+375+400\n"),
 array ( "name" => "India",  "iso" => "in", "conf" => "ringcadence = 400,200,400,2000\ndial = 400*25\nbusy = 400/750,0/750\nring = 400*25/400,0/200,400*25/400,0/2000\ncongestion = 400/250,0/250\ncallwaiting = 400/200,0/100,400/200,0/7500\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0/1000\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Iran",  "iso" => "ir", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/240,0/240\ncallwaiting = 425/200,0/200,425/200,0/10000\ndialrecall = 425 record = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 400+425\n"),
 array ( "name" => "Ireland", "iso" => "ie", "conf" => "ringcadance = 400,200,400,2000\ndial = 425\nbusy = 425/450,0/450\nring = 400+450/400,0/200,400+450/400,0/2000\ncongestion = 425/375,0/375\ncallwaiting = 425/200,0/200,425/200,0/4000\ndialrecall = 440\nrecord = 1400/500,0/10000\ninfo = 950/330,1400/330,1800/330\n"),
 array ( "name" => "Israel",  "iso" => "il", "conf" => "ringcadence = 1000,3000\ndial = 414\nbusy = 414/500,0/500\nring = 414/1000,0/3000\ncongestion = 414/250,0/250\ncallwaiting = 414/100,0/100,414/100,0/100,414/600,0/3000 \ndialrecall = !414/100,!0/100,!414/100,!0/100,!414/100,!0/100,414\nrecord = 1400/500,0/15000\ninfo = 1000/330,1400/330,1800/330,0/1000\nstutter = !414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,414 \n"),
 array ( "name" => "Italy",  "iso" => "it", "conf" => "ringcadence = 1000,4000\ndial = 425/200,0/200,425/600,0/1000\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/400,0/100,425/250,0/100,425/150,0/14000\ndialrecall = 470/400,425/400\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 470/400,425/400\n"),
 array ( "name" => "Japan",  "iso" => "jp", "conf" => "ringcadence = 1000,2000\ndial = 400\nbusy = 400/500,0/500\nring = 400+415/1000,0/2000\ncongestion = 400/500,0/500\ncallwaiting = 400+16/500,0/8000\ndialrecall = !400/200,!0/200,!400/200,!0/200,!400/200,!0/200,400\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter =!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,400\n"),
 array ( "name" => "Kenya (Republic of)",  "iso" => "ke", "conf" => "ringcadence = 670,3000,1500,5000\nbusy = 425/200,0/600,425/200,0/600\ncongestion = 425/200,0/600\ndial = 425\nringing = 425/670,0/3000,425/1500,0/5000\ninfo = 900/750,1400/750,1800/750,0/1250\ncallwaiting = 425\n"),
 array ( "name" => "Lithuania",  "iso" => "lt", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/350,0/350\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/150,0/150,425/150,0/4000\ndialrecall = 425/500,0/50\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Macao",  "iso" => "mo", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 425/200,0/600\nrecord = 1400/400,0/15000\ninfo = 950/333,1400/333,1800/333,0/1000\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Malaysia",  "iso" => "my", "conf" => "ringcadence = 400,200,400,2000\ndial = 425\nbusy = 425/500,0/500\nring = 425/400,0/200,425/400,0/2000\ncongestion = 425/500,0/500\ncallwaiting = 425/100,0/4000\ndialrecall = 350+440\nrecord = 1400/500,0/60000\ninfo = 950/330,0/15,1400/330,0/15,1800/330,0/1000\nstutter = 450+425\n"),
 array ( "name" => "Mexico",  "iso" => "mx", "conf" => "ringcadence = 2000,4000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 425/200,0/600,425/200,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = 950/330,0/30,1400/330,0/30,1800/330,0/1000\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Netherlands",  "iso" => "nl", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 425/500,0/9500\ndialrecall = 425/500,0/50\nrecord = 1400/500,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425/500,0/50\n"),
 array ( "name" => "New Zealand",  "iso" => "nz", "conf" => "ringcadence = 400,200,400,2000\ndial = 400\nbusy = 400/250,0/250\nring = 400+450/400,0/200,400+450/400,0/2000\ncongestion = 400/375,0/375\ncallwaiting = !400/200,!0/3000,!400/200,!0/3000,!400/200,!0/3000,!400/200\ndialrecall = !400/100!0/100,!400/100,!0/100,!400/100,!0/100,400\nrecord = 1400/425,0/15000\ninfo = 400/750,0/100,400/750,0/100,400/750,0/100,400/750,0/400\nstutter = !400/100!0/100,!400/100,!0/100,!400/100,!0/100,!400/100!0/100,!400/100,!0/100,!400/100,!0/100,400\n"),
 array ( "name" => "Norway",  "iso" => "no", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/200,0/600,425/200,0/10000\ndialrecall = 470/400,425/400\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 470/400,425/400\n"),
 array ( "name" => "Pakistan",  "iso" => "pk", "conf" => "ringcadence = 400,1000,0,2000\nbusy = 400/500,0/500\nring = 400/1000,0/2000\ncongestion = 400/250,0/250\n"),
 array ( "name" => "Panama",  "iso" => "pa", "conf" => "ringcadence = 2000,4000\ndial = 425\nbusy = 425/320,0/320\nring = 425/1200,0/4650\ncongestion = 425/320,0/320\ncallwaiting = 425/180,0/180,425/180\dialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!42\n"),
 array ( "name" => "Philippines",  "iso" => "phl", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 480+620/500,0/500\nring = 425+480/1000,0/4000\ncongestion = 480+620/250,0/250\ncallwaiting = 440/300,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Poland",  "iso" => "pl", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/500,0/500\ncallwaiting = 425/150,0/150,425/150,0/4000\ndialrecall = 425/500,0/50\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Portugal",  "iso" => "pt", "conf" => "ringcadence = 1000,5000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/5000\ncongestion = 425/200,0/200\ncallwaiting = 440/300,0/10000\ndialrecall = 425/1000,0/200\nrecord = 1400/500,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Romania", "iso" => "ro", "conf" => "ringcadence = 1850,4150\ndial = 450\nbusy = 450/167,0/167\nring = 450*25/1850,0/4150\ncongestion = 450/500,0/500\ncallwaiting = 450/150,0/150,450/150,0/8000\ndialrecall = !450/100,!0/100,!450/100,!0/100,!450/100,!0/100,450\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !450/100,!0/100,!450/100,!0/100,!450/100,!0/100,!450/100,!0/100,!450/100,!0/100,!450/100,!0/100,450\nfacility = 450\nhowler = 3000\ndialout = 600\nintrusion = 450/150,0/4950\nspecialdial = 450*25/400,0/40\nunobtainable = !450/92,!0/110,!450/92,!0/110,!450/92,!0/110,450/362,0/110\n"),
 array ( "name" => "Russian Federation",  "iso" => "ru", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/350,0/350\nring = 425/800,0/3200\ncongestion = 425/350,0/350\ncallwaiting = 425/200,0/5000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\n"),
 array ( "name" => "Singapore",  "iso" => "sg", "conf" => "ringcadence = 400,200,400,2000\ndial = 425\nring = 425*24/400,0/200,425*24/400,0/2000 ; modulation should be 100%, not 90%\nbusy = 425/750,0/750\ncongestion = 425/250,0/250\ncallwaiting = 425*24/300,0/200,425*24/300,0/3200\nstutter = !425/200,!0/200,!425/600,!0/200,!425/200,!0/200,!425/600,!0/200,!425/200,!0/200,!425/600,!0/200,!425/200,!0/200,!425/600,!0/200,425\ninfo = 950/330,1400/330,1800/330,0/1000 ; not currently in use acc. to reference\ndialrecall = 425*24/500,0/500,425/500,0/2500 ; unspecified in IDA reference, use repeating Holding Tone A,B\nrecord = 1400/500,0/15000 ; unspecified in IDA reference, use 0.5s tone every 15s\nnutone = 425/2500,0/500\nintrusion = 425/250,0/2000\nwarning = 425/624,0/4376 ; end of period tone, warning\nacceptance = 425/125,0/125\nholdinga = !425*24/500,!0/500 ; followed by holdingb\nholdingb = !425/500,!0/2500\n"),
 array ( "name" => "South Africa",  "iso" => "za", "conf" => "ringcadence = 400,200,400,2000\ndial = 400*33\nbusy = 400/500,0/500\nring = 400*33/400,0/200,400*33/400,0/2000\ncongestion = 400/250,0/250\ncallwaiting = 400*33/250,0/250,400*33/250,0/250,400*33/250,0/250,400*33/250,0/250\ndialrecall = 350+440\nrecord = 1400/500,0/10000\ninfo = 950/330,1400/330,1800/330,0/330\nstutter =!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,400*33 \n"),
 array ( "name" => "Spain",  "iso" => "es", "conf" => "ringcadence = 1500,3000\ndial = 425\nbusy = 425/200,0/200\nring = 425/1500,0/3000\ncongestion = 425/200,0/200,425/200,0/200,425/200,0/600\ncallwaiting = 425/175,0/175,425/175,0/3500\ndialrecall = !425/200,!0/200,!425/200,!0/200,!425/200,!0/200,425\nrecord = 1400/500,0/15000\ninfo = 950/330,0/1000\ndialout = 500\n"),
 array ( "name" => "Sweden",  "iso" => "se", "conf" => "ringcadence = 1000,5000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/5000\ncongestion = 425/250,0/750\ncallwaiting = 425/200,0/500,425/200,0/9100\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/500,0/15000\ninfo = !950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,0\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Switzerland",  "iso" => "ch", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/200,0/200,425/200,0/4000\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425+340/1100,0/1100\n"),
 array ( "name" => "Taiwan",  "iso" => "tw", "conf" => "ringcadence = 1000,4000\ndial = 350+440\nbusy = 480+620/500,0/500\nring = 440+480/1000,0/2000\ncongestion = 480+620/250,0/250\ncallwaiting = 350+440/250,0/250,350+440/250,0/3250\ndialrecall = 300/1500,0/500\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Tanzania (United Republic of)",  "iso" => "tz", "conf" => "ringcadence = 1000,4000\nbusy = 425/1000,0/1000\ncongestion = 425/375,0/375\ndial = 425+400\nringing = 425/1000,0/4000\ninfo = 950/375,1400/375,1800/375,0/30,950/375,1400/375,1800/375,0/30,950/375,1400/375,1800/375callwaiting = 425/500,0/200\n"),
 array ( "name" => "Thailand", "iso" => "th", "conf" => "ringcadence = 1000,4000\dial = 400*50\nbusy = 400/500,0/500\nring = 420/1000,0/5000\ncongestion = 400/300,0/300\ncallwaiting = 1000/400,10000/400,1000/400\ndialrecall = 400*50/400,0/100,400*50/400,0/100\nrecord = 1400/500,0/15000\ninfo = 950/330,1400/330,1800/330\nstutter = !400/200,!0/200,!400/600,!0/200,!400/200,!0/200,!400/600,!0/200,!400/200,!0/200,!400/600,!0/200,!400/200,!0/200,!400/600,!0/200,400\n"),
 array ( "name" => "Turkey", "iso" => "tr", "conf" => "ringcadance = 2000,4000\ndial = 450\nbusy = 450/500,0/500\nring = 450/2000,0/4000\ncongestion = 450/200,0/200,450/200,0/200,450/200,0/200,450/600,0/200\ncallwaiting = 450/200,0/600,450/200,0/8000\ndialrecall = 450/1000,0/250\nrecord = 1400/500,0/15000\ninfo = !950/300,!1400/300,!1800/300,!0/1000,!950/300,!1400/300,!1800/300,!0/1000,!950/300,!1400/300,!1800/300,!0/1000,0\n"),
 array ( "name" => "Uganda (Republic of)",  "iso" => "ug", "conf" => "ringcadence = 1000,4000\nbusy = 425/500,0/500\ncongestion = 425/250,0/250\ndial = 425\nringing = 425/1000,0/4000\ncallwaiting = 425/150,0/150,425/150,0/8000\n"),
 array ( "name" => "United Kingdom",  "iso" => "uk", "conf" => "ringcadence = 400,200,400,2000\ndial = 350+440\nspecialdial = 350+440/750,440/750\nbusy = 400/375,0/375\ncongestion = 400/400,0/350,400/225,0/525\nspecialcongestion = 400/200,1004/300\nunobtainable = 400\nring = 400+450/400,0/200,400+450/400,0/2000\ncallwaiting = 400/100,0/4000\nspecialcallwaiting = 400/250,0/250,400/250,0/250,400/250,0/5000\ncreditexpired = 400/125,0/125\nconfirm = 1400\nswitching = 400/200,0/400,400/2000,0/400\ninfo = 950/330,0/15,1400/330,0/15,1800/330,0/1000\nrecord = 1400/500,0/60000\nstutter = 350+440/750,440/750\n"),
 array ( "name" => "United States / North America",  "iso" => "us", "conf" => "ringcadence = 2000,4000\ndial = 350+440\nbusy = 480+620/500,0/500\nring = 440+480/2000,0/4000\ncongestion = 480+620/250,0/250\ncallwaiting = 440/300,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "United States Circa 1950/ North America",  "iso" => "us-old", "conf" => "ringcadence = 2000,4000\ndial = 600*120\nbusy = 500*100/500,0/500\nring = 420*40/2000,0/4000\ncongestion = 500*100/250,0/250\ncallwaiting = 440/300,0/10000\ndialrecall = !600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,600*120\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,600*120\n"),
 array ( "name" => "Venezuela / South America",  "iso" => "ve", "conf" => "; Tone definition source for ve found on\n; Reference: http://www.itu.int/ITU-T/inr/forms/files/tones-0203.pdf\nringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 400+450/300,0/6000\ndialrecall = 425\nrecord =  1400/500,0/15000\ninfo = !950/330,!1440/330,!1800/330,0/1000\n"),);
}


//
// CATEGORY:  Asterisk Builtin mini-HTTP server

$httpupdate = false;
if(!$freepbx_conf->conf_setting_exists('HTTPENABLED')) {
	$httpupdate = true;
}
unset($set);
$set['module'] = '';
$set['category'] = 'Asterisk Builtin mini-HTTP server';

// HTTPENABLED
$set['value'] = false;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'Enable the mini-HTTP Server';
$set['description'] = 'Whether the Asterisk HTTP interface is enabled or not. This is for Asterisk, it is not directly related for FreePBX usage and the value of this setting is irrelevant for accessing core FreePBX settings. Default is no.';
$set['emptyok'] = 0;
$set['level'] = 1;
$set['readonly'] = 0;
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('HTTPENABLED',$set);

// HTTPENABLESTATIC
$set['value'] = false;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'Enable Static Content';
$set['description'] = 'Whether Asterisk should serve static content from http-static (HTML pages, CSS, javascript, etc.). Default is no.';
$set['emptyok'] = 0;
$set['level'] = 2;
$set['readonly'] = 0;
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('HTTPENABLESTATIC',$set);

// HTTPBINDADDRESS
$set['value'] = '::';
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'HTTP Bind Address';
$set['description'] = 'Address to bind to. Default is ::';
$set['emptyok'] = 0;
$set['type'] = CONF_TYPE_TEXT;
$set['level'] = 2;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPBINDADDRESS',$set);

// HTTPBINDPORT
if(!$freepbx_conf->conf_setting_exists('HTTPBINDPORT')) {
	$set['value'] = '8088';
	$set['defaultval'] =& $set['value'];
	$set['options'] = '';
	$set['name'] = 'HTTP Bind Port';
	$set['description'] = 'Port to bind to. Default is 8088';
	$set['emptyok'] = 0;
	$set['options'] = array(10,65536);
	$set['type'] = CONF_TYPE_INT;
	$set['level'] = 2;
	$set['readonly'] = 0;
	$freepbx_conf->define_conf_setting('HTTPBINDPORT',$set);
}

// HTTPPREFIX
$set['value'] = '';
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'HTTP Prefix';
$set['description'] = 'HTTP Prefix allows you to specify a prefix for all requests to the server. For example, if the prefix is set to "asterisk" then all requests must begin with /asterisk. If this field is blank it is akin to saying all requests must being with /, essentially no prefix';
$set['emptyok'] = 1;
$set['type'] = CONF_TYPE_TEXT;
$set['level'] = 2;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPPREFIX',$set);

// HTTPENABLED
$set['value'] = false;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'Enable TLS for the mini-HTTP Server';
$set['description'] = 'Enables listening for HTTPS connections. This is for Asterisk, it is not directly related for FreePBX usage and the value of this setting is irrelevant for accessing core FreePBX settings. Default is no.';
$set['emptyok'] = 0;
$set['level'] = 3;
$set['readonly'] = 0;
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('HTTPTLSENABLE',$set);

// HTTPBINDADDRESS
$set['value'] = '::';
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'HTTPS Bind Address';
$set['description'] = 'Address to bind to. Default is ::';
$set['emptyok'] = 0;
$set['type'] = CONF_TYPE_TEXT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPTLSBINDADDRESS',$set);

// HTTPBINDPORT
$set['value'] = '8089';
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'HTTPS Bind Port';
$set['description'] = 'Port to bind to. Default is 8089';
$set['emptyok'] = 0;
$set['options'] = array(10,65536);
$set['type'] = CONF_TYPE_INT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPTLSBINDPORT',$set);

// HTTPBINDADDRESS
$set['value'] = '';
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'HTTPS TLS Certificate Location';
$set['description'] = 'Sets the path to the HTTPS server certificate. This is required if "Enable TLS for the mini-HTTP Server" is set to yes.';
$set['emptyok'] = 1;
$set['type'] = CONF_TYPE_TEXT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPTLSCERTFILE',$set);

// HTTPBINDADDRESS
$set['value'] = '';
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'HTTPS TLS Private Key Location';
$set['description'] = 'Sets the path to the HTTPS private key. If this is not specified, the "HTTPS TLS Certificate" will be checked to see if it also contains the private key.';
$set['emptyok'] = 1;
$set['type'] = CONF_TYPE_TEXT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPTLSPRIVATEKEY',$set);

if($httpupdate) {
	if(file_exists($amp_conf['ASTETCDIR'].'/http.conf')) {
		$settings = array();
		$httpcontents = file_get_contents($amp_conf['ASTETCDIR'].'/http.conf');
		if(preg_match('/^enabled=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPENABLED'] = ($matches[1] == 'yes') ? true : false;
		}

		if(preg_match('/^bindaddr=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPBINDADDRESS'] = !empty($matches[1]) ? $matches[1] : '127.0.0.1';
		}

		if(preg_match('/^bindport=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPBINDPORT'] = !empty($matches[1]) ? $matches[1] : '8088';
		}

		if(preg_match('/^prefix=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPPREFIX'] = !empty($matches[1]) ? $matches[1] : '';
		}

		if(!empty($settings)) {
			$freepbx_conf->set_conf_values($settings,true);
		}
	}
} else {
	//For security, if the file is not symlinked then we need to keep reading the settings and applying them to advanced settings just to be safe
	if(file_exists($amp_conf['ASTETCDIR'].'/http.conf') && !is_link($amp_conf['ASTETCDIR'].'/http.conf')) {
		$settings = array();
		$httpcontents = file_get_contents($amp_conf['ASTETCDIR'].'/http.conf');
		if(preg_match('/^enabled=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPENABLED'] = ($matches[1] == 'yes') ? true : false;
		}
		if(preg_match('/^bindaddr=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPBINDADDRESS'] = !empty($matches[1]) ? $matches[1] : '127.0.0.1';
		}

		if(preg_match('/^bindport=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPBINDPORT'] = !empty($matches[1]) ? $matches[1] : '8088';
		}

		if(preg_match('/^prefix=(.*)/im',$httpcontents,$matches)) {
			$settings['HTTPPREFIX'] = !empty($matches[1]) ? $matches[1] : '';
		}

		if(!empty($settings)) {
			$freepbx_conf->set_conf_values($settings,true);
		}
	}
}

// HTTPBINDADDRESS
$set['value'] = 100;
$set['defaultval'] =& $set['value'];
$set['name'] = 'Session Limit';
$set['description'] = 'Specifies the maximum number of http sessions that will be allowed to exist at any given time.';
$set['emptyok'] = 0;
$set['options'] = array(10,65536);
$set['type'] = CONF_TYPE_INT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPSESSIONLIMIT',$set);

// HTTPBINDADDRESS
$set['value'] = 30000;
$set['defaultval'] =& $set['value'];
$set['name'] = 'Session Inactivity ';
$set['description'] = 'Specifies the number of milliseconds to wait for more data over the HTTP connection before closing it. Set to 0 to disable session inactivity.';
$set['emptyok'] = 0;
$set['options'] = array(0,65536);
$set['type'] = CONF_TYPE_INT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPSESSIONINACTIVITY',$set);

// HTTPBINDADDRESS
$set['value'] = 15000;
$set['defaultval'] =& $set['value'];
$set['name'] = 'Session Keep Alive';
$set['description'] = 'Specifies the number of milliseconds to wait for the next HTTP request over a persistent connection. Set to 0 to disable persistent HTTP connections.';
$set['emptyok'] = 1;
$set['options'] = array(0,65536);
$set['type'] = CONF_TYPE_INT;
$set['level'] = 4;
$set['readonly'] = 0;
$freepbx_conf->define_conf_setting('HTTPSESSIONKEEPALIVE',$set);

$migrate = array("sessionlimit" => "HTTPSESSIONLIMIT","session_inactivity" => "HTTPSESSIONINACTIVITY","session_keep_alive" => "HTTPSESSIONKEEPALIVE");
if(file_exists($amp_conf['ASTETCDIR'].'/http_custom.conf')) {
	$data = FreePBX::LoadConfig()->getConfig('http_custom.conf');
	$contents = file_get_contents($amp_conf['ASTETCDIR'].'/http_custom.conf');
	if(!empty($data['HEADER']) && is_array($data['HEADER'])) {
		foreach($data['HEADER'] as $key => $value) {
			if(!isset($migrate[$key])) {
				continue;
			}
			outn(sprintf(_("Migrating http setting '%s' to Advanced Settings..."),$key));
			$contents = preg_replace('/'.$key.'\s*=\s*'.$value.'.*\n/','',$contents);
			$freepbx_conf->update($migrate[$key],$value);
			out(_("Done"));
		}
	}
	file_put_contents($amp_conf['ASTETCDIR'].'/http_custom.conf',$contents);
}

// HTTPWEBSOCKETMODE
$set['value'] = 'auto';
$set['defaultval'] =& $set['value'];
$set['options'] = array('auto','sip','pjsip');
$set['name'] = 'Force WebSocket Mode';
$set['description'] = "If set to anything other than auto Asterisk will be forced to use the technology set here for all websocket communication. Asterisk can not work with both drivers at the same time!";
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 5;
$set['emptyok'] = 0;
$set['type'] = CONF_TYPE_SELECT;
$freepbx_conf->define_conf_setting('HTTPWEBSOCKETMODE',$set);

unset($set);
//
// CATEGORY: GUI Behavior
//
// SIPSECRETSIZE
//
$set['value'] = 32;
$set['defaultval'] =& $set['value'];
$set['options'] = array(6,32);
$set['readonly'] = 1;
$set['hidden'] = 0;
$set['level'] = 9;
$set['module'] = '';
$set['category'] = 'GUI Behavior';
$set['emptyok'] = 0;
$set['name'] = 'Size of generated sip secrets';
$set['description'] = 'When creating a new sip extension this sets the max size of the generated secret. This generally should be default.';
$set['type'] = CONF_TYPE_INT;
$freepbx_conf->define_conf_setting('SIPSECRETSIZE',$set);

unset($set);
$set['module'] = '';
$set['category'] = 'GUI Behavior';
// HTTPENABLED
$set['value'] = false;
$set['defaultval'] =& $set['value'];
$set['options'] = '';
$set['name'] = 'Enable The Old Style FreePBX Dial Patterns Textarea';
$set['description'] = 'If enabled the intuitive Dial Patterns boxes on the Routing and Trunks pages will be replaced with a text box which restores the functionality from FreePBX 2.7 and lower.';
$set['emptyok'] = 0;
$set['level'] = 1;
$set['readonly'] = 0;
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('ENABLEOLDDIALPATTERNS',$set);

unset($set);
// RSSFEEDS
// This is here because conf type CONF_TYPE_TEXTAREA doesnt exist
// if there is an upgrade from 2.11 to 12 until after framework is updated
$set['category'] = 'System Setup';
$set['value'] = "http://www.freepbx.org/rss.xml\nhttp://feeds.feedburner.com/InsideTheAsterisk";
$set['defaultval'] = $set['value'];
$set['name'] = 'RSS Feeds';
$set['description'] = 'RSS Feeds that are displayed in UCP and Dashboard. Separate each feed by a new line. Supported feeds are Atom 1.0 and RSS 0.91, 0.92, 1.0 and 2.0';
$set['hidden'] = 0;
$set['emptyok'] = 1;
$set['readonly'] = 0;
$set['level'] = 0;
$set['options'] = '';
$set['module'] = '';
$set['type'] = CONF_TYPE_TEXTAREA;
$freepbx_conf->define_conf_setting('RSSFEEDS',$set);

unset($set);

$INTERNALALERTINFO = $ATTTRANSALERTINFO = $BLINDTRANSALERTINFO = 'inherit';
if($freepbx_conf->conf_setting_exists("INTERNALALERTINFO")) {
	$setting = $freepbx_conf->conf_setting("INTERNALALERTINFO");
	if($setting != CONF_TYPE_CSELECT) {
		foreach(array("INTERNALALERTINFO", "ATTTRANSALERTINFO", "BLINDTRANSALERTINFO") as $key) {
			$$key = $freepbx_conf->get($key);
		}
		$freepbx_conf->remove_conf_settings(array("INTERNALALERTINFO", "ATTTRANSALERTINFO", "BLINDTRANSALERTINFO"));
	}
}
$set['category'] = 'Dialplan and Operational';
$set['value'] = $INTERNALALERTINFO;
$set['defaultval'] = $set['value'];
$set['name'] = 'Internal Alert Info';
$set['description'] = "Alert Info to use on Extension to Extension Calls. 'Inherit' will use the previously set Alert Info";
$set['hidden'] = 0;
$set['emptyok'] = 1;
$set['readonly'] = 0;
$set['level'] = 0;
$set['options'] = array("inherit","ring1","ring2","ring3","ring4","ring5");
$set['module'] = '';
$set['type'] = CONF_TYPE_CSELECT;
$freepbx_conf->define_conf_setting('INTERNALALERTINFO',$set);

$set['name'] = 'Attended Transfer Alert Info';
$set['description'] = "Alert Info to use on Attended Transfer Calls. 'Inherit' will use the previously set Alert Info. Note: Attended Transfer detection only works in Asterisk 12 or higher";
$set['value'] = $ATTTRANSALERTINFO;
$freepbx_conf->define_conf_setting('ATTTRANSALERTINFO',$set);

$set['name'] = 'Blind Transfer Alert Info';
$set['description'] = "Alert Info to use on Blind Transfer Calls. 'Inherit' will use the previously set Alert Info";
$set['value'] = $BLINDTRANSALERTINFO;
$freepbx_conf->define_conf_setting('BLINDTRANSALERTINFO',$set);

$timezone = 'UTC';
if (is_link('/etc/localtime')) {
	// Mac OS X (and older Linuxes)
	// /etc/localtime is a symlink to the
	// timezone in /usr/share/zoneinfo.
	$filename = readlink('/etc/localtime');
	if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
		$timezone = trim(substr($filename, 20));
	}
} elseif (file_exists('/etc/timezone')) {
	// Ubuntu / Debian.
	$data = file_get_contents('/etc/timezone');
	if (!empty($data)) {
		$timezone = trim($data);
	}
} elseif (file_exists('/etc/sysconfig/clock')) {
	// RHEL / CentOS
	$data = @parse_ini_file('/etc/sysconfig/clock');
	if (!empty($data['ZONE'])) {
		$timezone = trim($data['ZONE']);
	}
}

$set['category'] = 'System Setup';
$set['value'] = !empty($timezone) ? $timezone : "UTC";
$set['defaultval'] = 'UTC';
$set['name'] = 'PHP Timezone';
$set['description'] = "Timezone that should be used by PHP. Empty value will use PHP defaults. List of value Timezones: <a href='http://php.net/manual/en/timezones.php'>http://php.net/manual/en/timezones.php</a>";
$set['hidden'] = 0;
$set['emptyok'] = 1;
$set['readonly'] = 0;
$set['level'] = 0;
$set['options'] = \DateTimeZone::listIdentifiers();
$set['module'] = '';
$set['type'] = CONF_TYPE_CSELECT;
$freepbx_conf->define_conf_setting('PHPTIMEZONE',$set);

$set['value'] = false;
$set['defaultval'] = $set['value'];
$set['category'] = 'System Setup';
$set['name'] = 'Expose All Feature Codes';
$set['description'] = "Expose All Feature Codes settings in Feature Code Admin page. By default *11/*12 feature code setting will be hidden in Extension mode.";
$set['options'] = '';
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = '';
$set['emptyok'] = 0;
$set['sortorder'] = -133;
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('EXPOSE_ALL_FEATURE_CODES',$set);
$freepbx_conf->commit_conf_settings();

$mf = \module_functions::create();
$info = $mf->getinfo("core");
if(!empty($info['core']['dbversion']) && version_compare_freepbx($info['core']['dbversion'], "13.0.45" , "<=")) {
	outn(_("Migrating force_rport to the right default value..."));
	$res = $db->query("UPDATE sip SET data = 'yes' WHERE keyword = 'force_rport'");
	if (!DB::IsError($res)) {
		out(_("done"));
	} else {
		out(_("error occured"));
	}
}

if(FreePBX::Modules()->checkStatus("sysadmin")) {
	touch("/var/spool/asterisk/incron/core.logrotate");
}
