<?php

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function core_destinations() {
	//get the list of meetmes
	$results = core_users_list();
	
	if (isset($results)) {
		//get voicemail
		$uservm = getVoicemail();
		$vmcontexts = array_keys($uservm);
		foreach ($results as $thisext) {
			$extnum = $thisext[0];
			// search vm contexts for this extensions mailbox
			foreach ($vmcontexts as $vmcontext) {
				if(isset($uservm[$vmcontext][$extnum])){
				//	$vmname = $uservm[$vmcontext][$extnum]['name'];
				//	$vmboxes[$extnum] = array($extnum, '"' . $vmname . '" <' . $extnum . '>');
				$vmboxes[$extnum] = true;
				}
			}
		}
	}
	
	// return an associative array with destination and description
	// core provides both users and voicemail boxes as destinations
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'ext-local,'.$result['0'].',1', 'description' => $result['1'].' <'.$result['0'].'>');
				if($vmboxes[$result['0']]) {
					$extens[] = array('destination' => 'ext-local,${VM_PREFIX}'.$result['0'].',1', 'description' => 'voicemail box '.$result['0']);
				}
		}
	}
	
	return $extens;
}

/* 	Generates dialplan for "core" components (extensions & inbound routing)
	We call this with retrieve_conf
*/
function core_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	switch($engine) {
		case "asterisk":
			/* inbound routing extensions */
			foreach(core_did_list() as $item) {
				$did = core_did_get($item['extension'],$item['cidnum']);
				$exten = $did['extension'];
				$cidnum = $did['cidnum'];
				
				// destination field in 'incoming' database is backwards from what ext_goto expects
				$goto_context = strtok($did['destination'],',');
				$goto_exten = strtok(',');
				$goto_pri = strtok(',');
				
				//sub a blank extension with 's'
				$exten = (empty($exten)?"s":$exten);
				$exten = $exten.(empty($cidnum)?"":"/".$cidnum); //if a CID num is defined, add it
				$ext->add('ext-did', $exten, '', new ext_setvar('FROM_DID',$exten));
				//$ext->add('ext-did', $exten, '', new ext_goto($goto_pri,$goto_exten,$goto_context));
				
				if ($exten == "s") {  //if the exten is s, then also make a catchall for undefined DIDs
					$catchaccount = "_X.".(empty($cidnum)?"":"/".$cidnum);
					$ext->add('ext-did', $catchaccount, '', new ext_setvar('FROM_DID',$catchaccount));
					$ext->add('ext-did', $catchaccount, '', new ext_goto($goto_pri,$goto_exten,$goto_context));
				}
				
				if ($item['faxexten'] != "default") {
					$ext->add('ext-did', $exten, '', new ext_setvar('FAX_RX',$item['faxexten']));
				}
				if (!empty($item['faxemail'])) {
					$ext->add('ext-did', $exten, '', new ext_setvar('FAX_RX_EMAIL',$item['faxexten']));
				}
				if ($item['answer'] == "1") {
					$ext->add('ext-did', $exten, '', new ext_answer(''));
					$ext->add('ext-did', $exten, '', new ext_wait($item['wait']));
				}
				if ($item['privacyman'] == "1") {
					$ext->add('ext-did', $exten, '', new ext_privacymanager(''));
				}
				
				//temporary use of 'incoming calls' until a time of day module is created
				if (empty($item['destination'])) { 
					$ext->add('ext-did', $exten, '', new ext_goto('1','s','from-pstn'));
				} else {
					$ext->add('ext-did', $exten, '', new ext_goto($goto_pri,$goto_exten,$goto_context));
				}
				
			}
		break;
	}
}

/* begin page.ampusers.php functions */

function core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections) {
	$sql = "INSERT INTO ampusers (username, password, extension_low, extension_high, deptname, sections) VALUES (";
	$sql .= "'".$username."',";
	$sql .= "'".$password."',";
	$sql .= "'".$extension_low."',";
	$sql .= "'".$extension_high."',";
	$sql .= "'".$deptname."',";
	$sql .= "'".implode(";",$sections)."');";
	
	sql($sql,"query");
}

function core_ampusers_del($username) {
	$sql = "DELETE FROM ampusers WHERE username = '".$username."'";
	sql($sql,"query");
}

function core_ampusers_list() {
	$sql = "SELECT username FROM ampusers ORDER BY username";
	return sql($sql,"getAll");
}

/* end page.ampusers.php functions */









/* begin page.did.php functions */

function core_did_list(){
	$sql = "SELECT * FROM incoming";
	return sql($sql,"getAll",DB_FETCHMODE_ASSOC);
}

function core_did_get($extension="",$cidnum=""){
	$sql = "SELECT * FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
	return sql($sql,"getRow",DB_FETCHMODE_ASSOC);
}

function core_did_del($extension,$cidnum){
	$sql="DELETE FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
	sql($sql);
}

function core_did_add($incoming){
	extract($incoming); // create variables from request
	$existing=core_did_get($extension,$cidnum);
	if (empty($existing)) {
		$destination=${$goto_indicate0.'0'};
		$sql="INSERT INTO incoming (cidnum,extension,destination,faxexten,faxemail,answer,wait,privacyman) values (\"$cidnum\",\"$extension\",\"$destination\",\"$faxexten\",\"$faxemail\",\"$answer\",\"$wait\",\"$privacyman\")";
		sql($sql);
	} else {
		echo "<script>javascript:alert('"._("A route for this DID/CID already exists!")."')</script>";
	}
}

/* end page.did.php functions */







/* begin page.devices.php functions */

//get the existing devices
function core_devices_list() {
	$sql = "SELECT id,description FROM devices";
	$results = sql($sql,"getAll");

	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array($result[0],$result[1]);
		}
	}
	if (isset($extens)) sort($extens);
	return $extens;
}


function core_devices_add($id,$tech,$dial,$devicetype,$user,$description){
	global $amp_conf;
	global $currentFile;
	
	//ensure this id is not already in use
	$devices = core_devices_list();
	if (is_array($devices)) {
		foreach($devices as $device) {
			if ($device[0]==$id) {
				echo "<script>javascript:alert('"._("This device id is already in use")."');</script>";
				return false;
			}
		}
	}
	//unless defined, $dial is TECH/id
	//zap is an exception
	if (empty($dial) && strtolower($tech) == "zap")
		$dial = "ZAP/".$_REQUEST['channel'];
	if (empty($dial))
		$dial = strtoupper($tech)."/".$id;
	
	//check to see if we are requesting a new user
	if ($user == "new") {
		$user = $id;
		$jump = true;
	}
	
	//insert into devices table
	$sql="INSERT INTO devices (id,tech,dial,devicetype,user,description) values (\"$id\",\"$tech\",\"$dial\",\"$devicetype\",\"$user\",\"$description\")";
	sql($sql);
	
	//add details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		$astman->database_put("DEVICE",$id."/dial",$dial);
		$astman->database_put("DEVICE",$id."/type",$devicetype);
		$astman->database_put("DEVICE",$id."/user",$user);
		if($user != "none") {
			$existingdevices = $astman->database_get("AMPUSER",$user."/device");
			if (!empty($existingdevices)) {
					$existingdevices .= "&";
			}
			$astman->database_put("AMPUSER",$user."/device",$existingdevices.$id);
		}
		$astman->disconnect();
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//voicemail symlink
	exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
	exec("/bin/ln -s /var/spool/asterisk/voicemail/default/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
		
	//take care of sip/iax/zap config
	$funct = "core_devices_add".strtolower($tech);
	if(function_exists($funct)){
		$funct($id);
	}
	
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
	
	if($user != "none") {
		core_hint_add($user);
	}
	
	//if we are requesting a new user, let's jump to users.php
	if ($jump) {
		echo("<script language=\"JavaScript\">window.location=\"config.php?display=users&extdisplay={$id}&name={$description}\";</script>");
	}
}

function core_devices_del($account){
	global $amp_conf;
	global $currentFile;
	
	//get all info about device
	$devinfo = core_devices_get($account);
	
	//delete from devices table
	$sql="DELETE FROM devices WHERE id = \"$account\"";
	sql($sql);
	
	//delete details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		// If a user was selected, remove this device from the user
		$deviceuser = $astman->database_get("DEVICE",$account."/user");
		if ($user != "none") {
				// Remove the device record from the user's device list
				$userdevices = $astman->database_get("AMPUSER",$deviceuser."/device");
				$userdevices = str_replace($account."&", "", $userdevices."&");
				// If there was more than one device, remove the extra "&" at the end.
				if (substr($userdevices, -1, 1) == "&") {
					$userdevices = substr($userdevices, 0, -1);
				}
				if (empty($userdevices)) {
						$astman->database_del("AMPUSER",$deviceuser."/device");
				} else {
						$astman->database_put("AMPUSER",$deviceuser."/device",$userdevices);
				}
		}
		$astman->database_del("DEVICE",$account."/dial");
		$astman->database_del("DEVICE",$account."/type");
		$astman->database_del("DEVICE",$account."/user");
		$astman->disconnect();
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//voicemail symlink
	exec("rm -f /var/spool/asterisk/voicemail/device/".$account);
	
	//take care of sip/iax/zap config
	$funct = "core_devices_del".strtolower($devinfo['tech']);
	if(function_exists($funct)){
		$funct($account);
	}
	
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
	
	//take care of any hint priority
	core_hint_add($devinfo['user']);
}

function core_devices_get($account){
	//get all the variables for the meetme
	$sql = "SELECT * FROM devices WHERE id = '$account'";
	$results = sql($sql,"getRow",DB_FETCHMODE_ASSOC);
	
	//take care of sip/iax/zap config
	$funct = "get".strtolower($results['tech']);
	if(function_exists($funct)){
		$devtech = $funct($account);
		if (is_array($devtech)){
			$results = array_merge($results,$devtech);
		}
	}
	
	return $results;
}

//TODO it is current not possible to use ${variables} for a HINT extensions (ie: for adhoc devices).
//Because of this limitation, the only way to update HINTs for adhoc devices, is to make the change 
//via the amp admin, so that a dialplan rewrite $ reload can be performed.
function core_hint_add($account){
	global $currentFile;	
	//delete any existing hint for this extension
	core_hint_del($account);
	
	//determine what devices this user is associated with
	$sql = "SELECT dial from devices where user = '{$account}'";
	$results = sql($sql,"getOne");
	
	//create a string 
	if (isset($results)){
		if (strpos($results,"&"))
			$hint = implode($results,"&");
		else
			$hint = $results;
	}

	//Add 'hint' priority if passed
	if (isset($hint)) {
		$sql = "INSERT INTO extensions (context, extension, priority, application) VALUES ('ext-local', '".$account."', 'hint', '".$hint."')";
		sql($sql);
	}
	$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	exec($wScript1);
}

function core_hint_del($user) {
	global $currentFile;
	//delete from devices table
	$sql="DELETE FROM extensions WHERE extension = \"{$user}\" AND priority = \"hint\"";
	sql($sql);
	$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	exec($wScript1);
}

// this function rebuilds the astdb based on device table contents
// used on devices.php if action=resetall
function core_devices2astdb(){
	require_once('common/php-asmanager.php');
	checkAstMan();
	global $amp_conf;
	$sql = "SELECT * FROM devices";
	$devresults = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//add details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {	
		$astman->database_deltree("DEVICE");
		foreach($devresults as $dev) {
			extract($dev);	
			$astman->database_put("DEVICE",$id."/dial",$dial);
			$astman->database_put("DEVICE",$id."/type",$devicetype);
			$astman->database_put("DEVICE",$id."/user",$user);		
			// If a user is selected, add this device to the user
			if($user != "none") {
					$existingdevices = $astman->database_get("AMPUSER",$user."/device");
					if (!empty($existingdevices)) {
							$existingdevices .= "&";
					}
					$astman->database_put("AMPUSER",$user."/device",$existingdevices.$id);
			}
			
			//voicemail symlink
			exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
			exec("/bin/ln -s /var/spool/asterisk/voicemail/default/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
		}
	} else {
		echo "Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"];
	}
	return $astman->disconnect();
}

// this function rebuilds the astdb based on users table contents
// used on devices.php if action=resetall
function core_users2astdb(){
	require_once('common/php-asmanager.php');
	checkAstMan();
	global $amp_conf;
	$sql = "SELECT * FROM users";
	$userresults = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	//add details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
		$astman->database_deltree("AMPUSER");
		foreach($userresults as $usr) {
			extract($usr);
			$astman->database_put("AMPUSER",$extension."/password",$password);
			$astman->database_put("AMPUSER",$extension."/ringtimer",$ringtimer);
			$astman->database_put("AMPUSER",$extension."/noanswer",$noasnwer);
			$astman->database_put("AMPUSER",$extension."/recording",$recording);
			$astman->database_put("AMPUSER",$extension."/outboundcid","\"".$outboundcid."\"");
			$astman->database_put("AMPUSER",$extension."/cidname","\"".$name."\"");
		}	
	} else {
		echo "Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"];
	}
	return $astman->disconnect();
}

//add to sip table
function core_devices_addsip($account) {
	sipexists();
	global $db;
	global $currentFile;
	$sipfields = array(array($account,'account',$account),
	array($account,'accountcode',($_REQUEST['accountcode'])?$_REQUEST['accountcode']:''),
	array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
	array($account,'canreinvite',($_REQUEST['canreinvite'])?$_REQUEST['canreinvite']:'no'),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
	array($account,'dtmfmode',($_REQUEST['dtmfmode'])?$_REQUEST['dtmfmode']:''),
	array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:'dynamic'),
	array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:'friend'),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
	array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:$account),
	array($account,'nat',($_REQUEST['nat'])?$_REQUEST['nat']:'never'),
	array($account,'port',($_REQUEST['port'])?$_REQUEST['port']:'5060'),
	array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:'no'),
	array($account,'callgroup',($_REQUEST['callgroup'])?$_REQUEST['callgroup']:''),
	array($account,'pickupgroup',($_REQUEST['pickupgroup'])?$_REQUEST['pickupgroup']:''),
	array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
	array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'));

	$compiled = $db->prepare('INSERT INTO sip (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$sipfields);
	if(DB::IsError($result)) {
		die($result->getDebugInfo()."<br><br>".'error adding to SIP table');	
	}
		   

	//script to write sip conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';
	exec($wScript);

}

function core_devices_delsip($account) {
	global $db;
	global $currentFile;
    $sql = "DELETE FROM sip WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
	}

	//script to write sip conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_sip_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function core_devices_getsip($account) {
	global $db;
	$sql = "SELECT keyword,data FROM sip WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

//add to iax table
function core_devices_addiax2($account) {
	global $db;
	global $currentFile;
	$iaxfields = array(array($account,'account',$account),
	array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
	array($account,'notransfer',($_REQUEST['notransfer'])?$_REQUEST['notransfer']:'yes'),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
	array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:'dynamic'),
	array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:'friend'),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
	array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:$account),
	array($account,'port',($_REQUEST['iaxport'])?$_REQUEST['iaxport']:'4569'),
	array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:'no'),
	array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
	array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
	array($account,'accountcode',($_REQUEST['accountcode'])?$_REQUEST['accountcode']:''),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'));

	$compiled = $db->prepare('INSERT INTO iax (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$iaxfields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>error adding to IAX table");	
	}	


	//script to write iax2 conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function core_devices_deliax2($account) {
	global $db;
	global $currentFile;
    $sql = "DELETE FROM iax WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
	}
	
	//script to write iax2 conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_iax_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function core_devices_getiax2($account) {
	global $db;
	$sql = "SELECT keyword,data FROM iax WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

function core_devices_addzap($account) {
	zapexists();
	global $db;
	global $currentFile;
	$zapfields = array(
	array($account,'account',$account),
	array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
	array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
	array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'),
	array($account,'signalling',($_REQUEST['signalling'])?$_REQUEST['signalling']:'fxo_ks'),
	array($account,'echocancel',($_REQUEST['echocancel'])?$_REQUEST['echocancel']:'yes'),
	array($account,'echocancelwhenbridged',($_REQUEST['echocancelwhenbridged'])?$_REQUEST['echocancelwhenbridged']:'no'),
	array($account,'echotraining',($_REQUEST['echotraining'])?$_REQUEST['echotraining']:'800'),
	array($account,'busydetect',($_REQUEST['busydetect'])?$_REQUEST['busydetect']:'no'),
	array($account,'busycount',($_REQUEST['busycount'])?$_REQUEST['busycount']:'7'),
	array($account,'callprogress',($_REQUEST['callprogress'])?$_REQUEST['callprogress']:'no'),
	array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
	array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
	array($account,'channel',($_REQUEST['channel'])?$_REQUEST['channel']:''));

	$compiled = $db->prepare('INSERT INTO zap (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$zapfields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>error adding to ZAP table");	
	}	


	//script to write zap conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_zap_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function core_devices_delzap($account) {
	global $db;
	global $currentFile;
    $sql = "DELETE FROM zap WHERE id = '$account'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die($result->getMessage().$sql);
	}
	
	//script to write zap conf file from mysql
	$wScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_zap_conf_from_mysql.pl';
	exec($wScript);
	//script to write op_server.cfg file from mysql 
	$wOpScript = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_op_conf_from_mysql.pl';
	exec($wOpScript);
}

function core_devices_getzap($account) {
	global $db;
	$sql = "SELECT keyword,data FROM zap WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}
/* end page.devices.php functions */








/* begin page.users.php functions */

// get the existing extensions
// the returned arrays contain [0]:extension [1]:name
function core_users_list() {
	$results = sql("SELECT extension,name FROM users ORDER BY extension","getAll");

	//only allow extensions that are within administrator's allowed range
	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array($result[0],$result[1]);
		}
	}
	if (isset($extens)) sort($extens);
	return $extens;
}

function core_users_add($vars,$vmcontext) {
	extract($vars);
	
	global $db;
	global $amp_conf;
	//ensure this id is not already in use
	$extens = getextens();
	if(is_array($extens)) {
		foreach($extens as $exten) {
			if ($exten[0]==$extension) {
				echo "<script>javascript:alert('"._("This user extension is already in use")."');</script>";
				return false;
			}
		}
	}
	
	//build the recording variable
	$recording = "out=".$record_out."|in=".$record_in;
	
	//insert into users table
	$sql="INSERT INTO users (extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid) values (\"$extension\",\"$password\",\"$name\",\"$voicemail\",\"$ringtimer\",\"$noanswer\",\"$recording\",'$outboundcid')";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
        die($results->getMessage().$sql);
	}
	
	//write to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {	
		$astman->database_put("AMPUSER",$extension."/password",$password);
		$astman->database_put("AMPUSER",$extension."/ringtimer",$ringtimer);
		$astman->database_put("AMPUSER",$extension."/noanswer",$noasnwer);
		$astman->database_put("AMPUSER",$extension."/recording",$recording);
		$astman->database_put("AMPUSER",$extension."/outboundcid","\"".$outboundcid."\"");
		$astman->database_put("AMPUSER",$extension."/cidname","\"".$name."\"");
		$astman->disconnect();
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//write to extensions table - AMP2 will not do this
	//update ext-local context in extensions.conf
	
	//warning: as of 009 we aren't allowing a user to use any mailbox but their own 
	//This may affect some upgraders as it is possible in previous versions!
	//$mailb = ($vm == 'disabled' || $mailbox == '') ? 'novm' : $mailbox;
	$mailb = ($vm == 'disabled') ? 'novm' : $extension;
	
	addaccount($extension,$mailb);
	
	core_hint_add($extension);
	
	
	//take care of voicemail.conf if using voicemail
	$uservm = getVoicemail();
	unset($uservm[$incontext][$account]);
	
	if ($vm != 'disabled')
	{ 
		// need to check if there are any options entered in the text field
		if ($_REQUEST['options']!=''){
			$options = explode("|",$_REQUEST['options']);
			foreach($options as $option) {
				$vmoption = explode("=",$option);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			}
		}
		$vmoption = explode("=",$attach);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$vmoption = explode("=",$saycid);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$vmoption = explode("=",$envelope);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$vmoption = explode("=",$delete);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$uservm[$vmcontext][$extension] = array(
									'mailbox' => $extension, 
									'pwd' => $vmpwd,
									'name' => $name,
									'email' => $email,
									'pager' => $pager,
									'options' => $vmoptions);
	}
	saveVoicemail($uservm);
}

function core_users_get($extension){
	global $db;
	//get all the variables for the meetme
	$sql = "SELECT * FROM users WHERE extension = '$extension'";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
        die($results->getMessage().$sql);
	}
	//explode recording vars
	$recording = explode("|",$results['recording']);
	$recout = substr($recording[0],4);
	$recin = substr($recording[1],3);
	$results['record_in']=$recin;
	$results['record_out']=$recout;

	return $results;
}

function core_users_del($extension,$incontext,$uservm){
	global $db;
	global $amp_conf;
	
	//delete from devices table
	$sql="DELETE FROM users WHERE extension = \"$extension\"";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
        die($results->getMessage().$sql);
	}

	//delete details to astdb
	$astman = new AGI_AsteriskManager();
	if ($res = $astman->connect("127.0.0.1", $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {	
		$astman->database_del("AMPUSER",$extension."/password",$password);
		$astman->database_del("AMPUSER",$extension."/ringtimer",$ringtimer);
		$astman->database_del("AMPUSER",$extension."/noanswer",$noasnwer);
		$astman->database_del("AMPUSER",$extension."/recording",$recording);
		$astman->database_del("AMPUSER",$extension."/outboundcid","\"".$outboundcid."\"");
		$astman->database_del("AMPUSER",$extension."/cidname","\"".$name."\"");
		$astman->disconnect();
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//take care of voicemail.conf
	unset($uservm[$incontext][$extension]);
	saveVoicemail($uservm);
		
	//delete the extension info from extensions table
	delextensions('ext-local',$extension);
	
	//delete hint
	core_hint_del($extension);
}

/* end page.users.php functions */





/* begin page.trunks.php functions */

// we're adding ,don't require a $trunknum
function core_trunks_add($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register) {
	global $db;
	
	// find the next available ID
	$trunknum = 1;
	foreach(core_trunks_list() as $trunk) {
		if ($trunknum == ltrim($trunk[0],"OUT_")) { 
			$trunknum++;
		}
	}
	
	core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
	
	return $trunknum;
}

function core_trunks_del($trunknum, $tech = null) {
	global $db;
	
	if ($tech === null) { // in EditTrunk, we get this info anyways
		$tech = core_trunks_getTrunkTech($trunknum);
	}

	//delete from globals table
	sql("DELETE FROM globals WHERE variable LIKE '%OUT_$trunknum' OR variable IN ('OUTCID_$trunknum','OUTMAXCHANS_$trunknum','OUTPREFIX_$trunknum')");
	
	//write outids
	core_trunks_writeoutids();

	// conditionally, delete from iax or sip
	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
			sql("DELETE FROM iax WHERE id = '9999$trunknum' OR id = '99999$trunknum' OR id = '9999999$trunknum'");
		break;
		case "sip": 
			sql("DELETE FROM sip WHERE id = '9999$trunknum' OR id = '99999$trunknum' OR id = '9999999$trunknum'");
		break;
	}
}

function core_trunks_edit($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register) {
	//echo "editTrunk($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";
	$tech = core_trunks_getTrunkTech($trunknum);
	core_trunks_del($trunknum, $tech);
	core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
}

// just used internally by addTrunk() and editTrunk()
//obsolete
function core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register) {
	global $db;
	
	if  (is_null($dialoutprefix)) $dialoutprefix = ""; // can't be NULL
	
	//echo  "backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";
	
	// change iax to "iax2" (only spot we actually store iax2, since its used by Dial()..)
	$techtemp = ((strtolower($tech) == "iax") ? "iax2" : $tech);
	$outval = (($techtemp == "custom") ? "AMP:".$channelid : strtoupper($techtemp).'/'.$channelid);
	
	$glofields = array(
			array('OUT_'.$trunknum, $outval),
			array('OUTPREFIX_'.$trunknum, $dialoutprefix),
			array('OUTMAXCHANS_'.$trunknum, $maxchans),
			array('OUTCID_'.$trunknum, $outcid),
			);
			
	unset($techtemp); 
	
	$compiled = $db->prepare('INSERT INTO globals (variable, value) values (?,?)');
	$result = $db->executeMultiple($compiled,$glofields);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>".$sql);
	}
	
	core_trunks_writeoutids();
	
	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
			core_trunks_addSipOrIax($peerdetails,'iax',$channelid,$trunknum);
			if ($usercontext != ""){
				core_trunks_addSipOrIax($userconfig,'iax',$usercontext,'9'.$trunknum);
			}
			if ($register != ""){
				core_trunks_addRegister($trunknum,'iax',$register);
			}
		break;
		case "sip":
			core_trunks_addSipOrIax($peerdetails,'sip',$channelid,$trunknum);
			if ($usercontext != ""){
				core_trunks_addSipOrIax($userconfig,'sip',$usercontext,'9'.$trunknum);
			}
			if ($register != ""){
				core_trunks_addRegister($trunknum,'sip',$register);
			}
		break;
	}	
}

function core_trunks_getTrunkTech($trunknum) {

	$results = sql("SELECT value FROM globals WHERE variable = 'OUT_".$trunknum."'","getAll");
	if (!$results) {
		return false;
	}
	if(strpos($results[0][0],"AMP:") === 0) {  //custom trunks begin with AMP:
		$tech = "custom";
	} else {
		$tech = strtolower( strtok($results[0][0],'/') ); // the technology.  ie: ZAP/g0 is ZAP
		
		if ($tech == "iax2") $tech = "iax"; // same thing, here
	}
	return $tech;
}

//add trunk info to sip or iax table
function core_trunks_addSipOrIax($config,$table,$channelid,$trunknum) {
	global $db;
	
	$confitem['account'] = $channelid;
	$gimmieabreak = nl2br($config);
	$lines = split('<br />',$gimmieabreak);
	foreach ($lines as $line) {
		$line = trim($line);
		if (count(split('=',$line)) > 1) {
			$tmp = split('=',$line);
			$key=trim($tmp[0]);
			$value=trim($tmp[1]);
			if (isset($confitem[$key]) && !empty($confitem[$key]))
				$confitem[$key].="&".$value;
			else
				$confitem[$key]=$value;
		}
	}
	foreach($confitem as $k=>$v) {
		$dbconfitem[]=array($k,$v);
	}
	$compiled = $db->prepare("INSERT INTO $table (id, keyword, data) values ('9999$trunknum',?,?)");
	$result = $db->executeMultiple($compiled,$dbconfitem);
	if(DB::IsError($result)) {
		die($result->getMessage()."<br><br>INSERT INTO $table (id, keyword, data) values ('9999$trunknum',?,?)");	
	}
}

//get unique trunks
function core_trunks_list() {
	global $db;
	
	// we have to escape _ for mysql: normally a wildcard
	$unique_trunks = sql("SELECT * FROM globals WHERE variable LIKE 'OUT\\\_%' ORDER BY RIGHT( variable, LENGTH( variable ) - 4 )+0","getAll"); 

	//if no trunks have ever been defined, then create the proper variables with the default zap trunk
	if (count($unique_trunks) == 0) {
		//If all trunks have been deleted from admin, dialoutids might still exist
		sql("DELETE FROM globals WHERE variable = 'DIALOUTIDS'");
	
		$glofields = array(array('OUT_1','ZAP/g0'),
							array('DIAL_OUT_1','9'),
							array('DIALOUTIDS','1'));
	    $compiled = $db->prepare('INSERT INTO globals (variable, value) values (?,?)');
		$result = $db->executeMultiple($compiled,$glofields);
	    if(DB::IsError($result)) {
	        die($result->getMessage()."<br><br>".$sql);	
	    }
		$unique_trunks[] = array('OUT_1','ZAP/g0');
		core_trunks_addOutTrunk("1");
	}
	// asort($unique_trunks);
	return $unique_trunks;
}

//write the OUTIDS global variable (used in dialparties.agi)
function core_trunks_writeoutids() {
	// we have to escape _ for mysql: normally a wildcard
	$unique_trunks = sql("SELECT variable FROM globals WHERE variable LIKE 'OUT\\\_%'","getAll"); 

	foreach ($unique_trunks as $unique_trunk) {
		$outid = strtok($unique_trunk[0],"_");
		$outid = strtok("_");
		$outids .= $outid ."/";
	}
	
	sql("UPDATE globals SET value = '$outids' WHERE variable = 'DIALOUTIDS'");
}

//add trunk to outbound-trunks context
function core_trunks_addOutTrunk($trunknum) {

	$result = sql("INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ('outbound-trunks', '_\${DIAL_OUT_".$trunknum."}.', '1', 'Macro', 'dialout,".$trunknum.",\${EXTEN}', NULL , '0')");

    return $result;
}

function core_trunks_addTrunkRegister($trunknum,$tech,$reg) {
	sql("INSERT INTO $tech (id, keyword, data) values ('9999999$trunknum','register','$reg')");
}


function core_trunks_addDialRules($trunknum, $dialrules) {
	$values = array();
	$i = 1;
	foreach ($dialrules as $rule) {
		$values["rule".$i++] = $rule;
	}
	
	$conf = core_trunks_readDialRulesFile();
	
	// rewrite for this trunk
	$conf["trunk-".$trunknum] = $values;
	
	core_trunks_writeDialRulesFile($conf);
}

function core_trunks_readDialRulesFile() {
	global $localPrefixFile; // probably not the best way
	
	core_trunks_parse_conf($localPrefixFile, &$conf, &$section);
	
	return $conf;
}

function core_trunks_writeDialRulesFile($conf) {
	global $localPrefixFile; // probably not the best way
	
	$fd = fopen($localPrefixFile,"w");
	foreach ($conf as $section=>$values) {
		fwrite($fd, "[".$section."]\n");
		foreach ($values as $key=>$value) {
			fwrite($fd, $key."=".$value."\n");
		}
		fwrite($fd, "\n");
	}
	fclose($fd);
}

function core_trunks_parse_conf($filename, &$conf, &$section) {
	if (is_null($conf)) {
		$conf = array();
	}
	if (is_null($section)) {
		$section = "general";
	}
	
	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				$conf[$section][ $matches[1] ] = $matches[2];
			} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
				// section name
				$section = strtolower($matches[1]);
			} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
				// include another file
				
				if ($matches[1][0] == "/") {
					// absolute path
					$filename = $matches[1];
				} else {
					// relative path
					$filename =  dirname($filename)."/".$matches[1];
				}
				
				core_trunks_parse_conf($filename, $conf, $section);
			}
		}
	}
}

function core_trunks_getTrunkTrunkName($trunknum) {
	$results = sql("SELECT value FROM globals WHERE variable = 'OUT_".$trunknum."'","getAll");
	if (!$results) {
		return false;
	}
	
	if(strpos($results[0][0],"AMP:") === 0) {  //custom trunks begin with AMP:
		$tname = ltrim($results[0][0],"AMP:");
	} else {
	strtok($results[0][0],'/');
		$tname = strtok('/'); // the text _after_ technology.  ie: ZAP/g0 is g0
	}
	return $tname;
}

//get and print peer details (prefixed with 4 9's)
function core_trunks_getTrunkPeerDetails($trunknum) {
	global $db;
	
	$tech = core_trunks_getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no details
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '9999$trunknum' ORDER BY id","getAll");
	
	foreach ($results as $result) {
		if ($result[0] != 'account') {
			$confdetail .= $result[0] .'='. $result[1] . "\n";
		}
	}
	return $confdetail;
}

//get trunk user context (prefixed with 5 9's)
function core_trunks_getTrunkUserContext($trunknum) {
	$tech = core_trunks_getTrunkTech($trunknum);
	if ($tech == "zap") return ""; // zap has no account
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '99999$trunknum' ORDER BY id","getAll");

	foreach ($results as $result) {
		if ($result[0] == 'account') {
			$account = $result[1];
		}
	}
	return $account;
}

//get and print user config (prefixed with 5 9's)
function core_trunks_getTrunkUserConfig($trunknum) {
	global $db;
	
	$tech = core_trunks_getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no details
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '99999$trunknum' ORDER BY id","getAll");

	foreach ($results as $result) {
		if ($result[0] != 'account') {
			$confdetail .= $result[0] .'='. $result[1] . "\n";
		}
	}
	return $confdetail;
}

//get trunk account register string
function core_trunks_getTrunkRegister($trunknum) {
	$tech = core_trunks_getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no register
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '9999999$trunknum'","getAll");

	foreach ($results as $result) {
			$register = $result[1];
	}
	return $register;
}

function core_trunks_getDialRules($trunknum) {
	$conf = core_trunks_readDialRulesFile();
	if (isset($conf["trunk-".$trunknum])) {
		return $conf["trunk-".$trunknum];
	}
	return false;
}

//get outbound routes for a given trunk
function core_trunks_gettrunkroutes($trunknum) {
	$results = sql("SELECT DISTINCT SUBSTRING(context,7), priority FROM extensions WHERE context LIKE 'outrt-%' AND (args LIKE 'dialout-trunk,".$trunknum.",%' OR args LIKE 'dialout-enum,".$trunknum.",%')ORDER BY context ","getAll");
	
	$routes = array();
	foreach ($results as $row) {
		$routes[$row[0]] = $row[1];
	}
	
	// array(routename=>priority)
	return $routes;
}

function core_trunks_deleteDialRules($trunknum) {
	$conf = core_trunks_readDialRulesFile();
	
	// remove rules for this trunk
	unset($conf["trunk-".$trunknum]);
	
	core_trunks_writeDialRulesFile($conf);
}

/* end page.trunks.php functions */






/* begin page.routing.php functions */

//get unique outbound route names
function core_routing_getroutenames() {
	$results = sql("SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ","getAll");
	// we SUBSTRING() to remove "outrt-"

	if (count($results) == 0) {
		// see if they're still using the old dialprefix method
		$results = sql("SELECT variable,value FROM globals WHERE variable LIKE 'DIAL\\\_OUT\\\_%'","getAll");
		// we SUBSTRING() to remove "outrt-"
		
		if (count($results) > 0) {
			// yes, they are using old method, let's update
			
			// get the default trunk
			$results_def = sql("SELECT value FROM globals WHERE variable = 'OUT'","getAll");
			
			if (preg_match("/{OUT_(\d+)}/", $results_def[0][0], $matches)) {
				$def_trunk = $matches[1];
			} else {
				$def_trunk = "";
			}
			
			$default_patterns = array(	// default patterns that used to be in extensions.conf
						"NXXXXXX",
						"NXXNXXXXXX",
						"1800NXXXXXX",
						"1888NXXXXXX",
						"1877NXXXXXX",
						"1866NXXXXXX",
						"1NXXNXXXXXX",
						"011.",
						"911",
						"411",
						"311",
						);
			
			foreach ($results as $temp) {
				// temp[0] is "DIAL_OUT_1"
				// temp[1] is the dial prefix
				
				$trunknum = substr($temp[0],9);
				
				$name = "route".$trunknum;
				
				$trunks = array(1=>"OUT_".$trunknum); // only one trunk to use
				
				$patterns = array();
				foreach ($default_patterns as $pattern) {
					$patterns[] = $temp[1]."|".$pattern;
				}
				
				if ($trunknum == $def_trunk) {
					// this is the default trunk, add the patterns with no prefix
					$patterns = array_merge($patterns, $default_patterns);
				}
				
				// add this as a new route
				core_routes_add($name, $patterns, $trunks,"new");
			}
			
			
			// delete old values
			sql("DELETE FROM globals WHERE (variable LIKE 'DIAL\\\_OUT\\\_%') OR (variable = 'OUT') ");

			// we need to re-generate extensions_additional.conf
			// i'm not sure how to do this from here
			
			// re-run our query
			$results = sql("SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ","getAll");
			// we SUBSTRING() to remove "outrt-"
		}
		
	} // else, it just means they have no routes.
	
	return $results;
}

function core_routing_setroutepriority($routepriority, $reporoutedirection, $reporoutekey)
{
	global $db;
	$counter=-1;
	foreach ($routepriority as $tresult) 
	{
		$counter++;
		if (($counter==($reporoutekey-1)) && ($reporoutedirection=="up")) {
			// swap this one with the one before (move up)
			$temproute = $routepriority[$counter];
			$routepriority[ $counter ] = $routepriority[ $counter+1 ];
			$routepriority[ $counter+1 ] = $temproute;
			
		} else if (($counter==($reporoutekey)) && ($reporoutedirection=="down")) {
			// swap this one with the one after (move down)
			$temproute = $routepriority[ $counter+1 ];
			$routepriority[ $counter+1 ] = $routepriority[ $counter ];
			$routepriority[ $counter ] = $temproute;
		}
	}
	unset($temptrunk);
	$routepriority = array_values($routepriority); // resequence our numbers
	$counter=0;
	foreach ($routepriority as $tresult) 
	{
		$order=core_routing_setroutepriorityvalue($counter++);
		$sql = sprintf("Update extensions set context='outrt-%s-%s' WHERE context='outrt-%s'",$order,substr($tresult[0],4), $tresult[0]);
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage()); 
		}
	}
	// Delete and readd the outbound-allroutes entries
	$sql = "delete from  extensions WHERE context='outbound-allroutes'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        	die($result->getMessage().$sql);
	}
	$sql = "SELECT DISTINCT context FROM extensions WHERE context like 'outrt-%' ORDER BY context";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}

	$priority_loops=1;	
	foreach ($results as $row) {
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
		$sql .= "('outbound-allroutes', ";
		$sql .= "'include', ";
		$sql .= "'".$priority_loops++."', ";
		$sql .= "'".$row[0]."', ";
		$sql .= "'', ";
		$sql .= "'', ";
		$sql .= "'2')";
	
		//$sql = sprintf("Update extensions set application='outrt-%s-%s' WHERE context='outbound-allroutes' and  application='outrt-%s'",$order,substr($tresult[0],4), $tresult[0]);
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die($result->getMessage(). $sql); 
 		}
	}
	$sql = "SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ";
        // we SUBSTRING() to remove "outrt-"
        $routepriority = $db->getAll($sql);
        if(DB::IsError($routepriority))
        {
                die($routepriority->getMessage());
        }
        return ($routepriority);
	
}

function core_routing_setroutepriorityvalue($key)
{
	$key=$key+1;
	if ($key<10)
		$prefix = sprintf("00%d",$key);
	else if ((9<$key)&&($key<100))
		$prefix = sprintf("0%d",$key);
	else if ($key>100)
		$prefix = sprintf("%d",$key);
	return ($prefix);
}


function core_routing_add($name, $patterns, $trunks, $method, $pass) {
	global $db;

	$trunktech=array();

	//Retrieve each trunk tech for later lookup
	$sql="select * from globals WHERE variable LIKE 'OUT\\_%'";
        $result = $db->getAll($sql);
        if(DB::IsError($result)) {
		die($result->getMessage());
	}
	foreach($result as $tr) {
		$tech = strtok($tr[1], "/");
		$trunktech[$tr[0]]=$tech;
	}
	
 	if ($method=="new")
	{	
            $sql="select DISTINCT context FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context";
            $routepriority = $db->getAll($sql);
            if(DB::IsError($result)) {
                    die($result->getMessage());
            }
            $order=core_routing_setroutepriorityvalue(count($routepriority));
	 	$name = sprintf ("%s-%s",$order,$name);
	}
	$trunks = array_values($trunks); // probably already done, but it's important for our dialplan

	
	foreach ($patterns as $pattern) {
		
		if (false !== ($pos = strpos($pattern,"|"))) {
			// we have a | meaning to not pass the digits on
			// (ie, 9|NXXXXXX should use the pattern _9NXXXXXX but only pass NXXXXXX, not the leading 9)
			
			$pattern = str_replace("|","",$pattern); // remove all |'s
			$exten = "EXTEN:".$pos; // chop off leading digit
		} else {
			// we pass the full dialed number as-is
			$exten = "EXTEN"; 
		}
		
		if (!preg_match("/^[0-9*]+$/",$pattern)) { 
			// note # is not here, as asterisk doesn't recoginize it as a normal digit, thus it requires _ pattern matching
			
			// it's not strictly digits, so it must have patterns, so prepend a _
			$pattern = "_".$pattern;
		}
		
		foreach ($trunks as $priority => $trunk) {
			$priority += 1; // since arrays are 0-based, but we want priorities to start at 1
			
			$sql = "INSERT INTO extensions (context, extension, priority, application, args) VALUES ";
			$sql .= "('outrt-".$name."', ";
			$sql .= "'".$pattern."', ";
			$sql .= "'".$priority."', ";
			$sql .= "'Macro', ";
			if ($trunktech[$trunk] == "ENUM")
				$sql .= "'dialout-enum,".substr($trunk,4).",\${".$exten."},".$pass."'"; // cut off OUT_ from $trunk
			else
				$sql .= "'dialout-trunk,".substr($trunk,4).",\${".$exten."},".$pass."'"; // cut off OUT_ from $trunk
			$sql .= ")";
			
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
			}
			//blank pass so that it isn't added for additional trunks
			$pass = "";
		}
		
		$priority += 1;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
		$sql .= "('outrt-".$name."', ";
		$sql .= "'".$pattern."', ";
		$sql .= "'".$priority."', ";
		$sql .= "'Macro', ";
		$sql .= "'outisbusy', ";
		$sql .= "'No available circuits')";
		
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die($result->getMessage());
		}
	}

	
	// add an include=>outrt-$name  to [outbound-allroutes]:
	
	// we have to find the first available priority.. priority doesn't really matter for the include, but
	// there is a unique index on (context,extension,priority) so if we don't do this we can't put more than
	// one route in the outbound-allroutes context.
	$sql = "SELECT priority FROM extensions WHERE context = 'outbound-allroutes' AND extension = 'include'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	$priorities = array();
	foreach ($results as $row) {
		$priorities[] = $row[0];
	}
	for ($priority = 1; in_array($priority, $priorities); $priority++);
	
	// $priority should now be the lowest available number
	
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
	$sql .= "('outbound-allroutes', ";
	$sql .= "'include', ";
	$sql .= "'".$priority."', ";
	$sql .= "'outrt-".$name."', ";
	$sql .= "'', ";
	$sql .= "'', ";
	$sql .= "'2')";
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($priority.$result->getMessage());
	}
	
}

function core_routing_edit($name, $patterns, $trunks, $pass) {
	core_routing_del($name);
	core_routing_add($name, $patterns, $trunks,"edit", $pass);
}

function core_routing_del($name) {
	global $db;
	$sql = "DELETE FROM extensions WHERE context = 'outrt-".$name."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	$sql = "DELETE FROM extensions WHERE context = 'outbound-allroutes' AND application = 'outrt-".$name."' ";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	return $result;
}

function core_routing_rename($oldname, $newname) {
	global $db;

	$route_prefix=substr($oldname,0,4);
	$newname=$route_prefix.$newname;
	$sql = "SELECT context FROM extensions WHERE context = 'outrt-".$newname."'";
	$results = $db->getAll($sql);
	if (count($results) > 0) {
		// there's already a route with this name
		return false;
	}
	
	$sql = "UPDATE extensions SET context = 'outrt-".$newname."' WHERE context = 'outrt-".$oldname."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
        $mypriority=sprintf("%d",$route_prefix);	
	$sql = "UPDATE extensions SET application = 'outrt-".$newname."', priority = '$mypriority' WHERE context = 'outbound-allroutes' AND application = 'outrt-".$oldname."' ";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
	
	return true;
}

//get unique outbound route patterns for a given context
function core_routing_getroutepatterns($route) {
	global $db;
	$sql = "SELECT extension, args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk%' OR args LIKE'dialout-enum%') ORDER BY extension ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	$patterns = array();
	foreach ($results as $row) {
		if ($row[0][0] == "_") {
			// remove leading _
			$pattern = substr($row[0],1);
		} else {
			$pattern = $row[0];
		}
		
		if (preg_match("/{EXTEN:(\d+)}/", $row[1], $matches)) {
			// this has a digit offset, we need to insert a |
			$pattern = substr($pattern,0,$matches[1])."|".substr($pattern,$matches[1]);
		}
		
		$patterns[] = $pattern;
	}
	return array_unique($patterns);
}

//get unique outbound route trunks for a given context
function core_routing_getroutetrunks($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%') ORDER BY priority ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	
	$trunks = array();
	foreach ($results as $row) {
		if (preg_match('/^dialout-trunk,(\d+)/', $row[0], $matches)) {
			// check in_array -- even though we did distinct
			// we still might get ${EXTEN} and ${EXTEN:1} if they used | to split a pattern
			if (!in_array("OUT_".$matches[1], $trunks)) {
				$trunks[] = "OUT_".$matches[1];
			}
		} else if (preg_match('/^dialout-enum,(\d+)/', $row[0], $matches)) {
			if (!in_array("OUT_".$matches[1], $trunks)) {
				$trunks[] = "OUT_".$matches[1];
			}
		}
	}
	return $trunks;
}


//get password for this route
function core_routing_getroutepassword($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%') ORDER BY priority ";
	$results = $db->getOne($sql);
	if(DB::IsError($results)) {
		die($results->getMessage());
	}
	if (preg_match('/^.*,.*,.*,(\d+)/', $results, $matches)) {
		$password = $matches[1];
	} else {
		$password = "";
	}
	return $password;
	
}

/* end page.routing.php functions */
?>