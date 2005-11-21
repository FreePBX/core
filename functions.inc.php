<?php

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function core_destinations() {
	//get the list of meetmes
	$results = core_users_list();
	
	// return an associative array with destination and description
	// core provides both users and voicemail boxes as destinations
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'ext-local,'.$result['0'].',1', 'description' => $result['1'].' <'.$result['0'].'>');
				$extens[] = array('destination' => 'ext-local,${VM_PREFIX}'.$result['0'].',1', 'description' => ''.$result['1'].' voicemail');
		}
	}
	
	return $extens;
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

function core_ampusers_delete($username) {
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
	$sql = "SELECT extension,cidnum FROM incoming";
	return sql($sql,"getAll",DB_FETCHMODE_ASSOC);
}

function core_did_get($extension="",$cidnum=""){
	$sql = "SELECT * FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
	return sql($sql,"getRow",DB_FETCHMODE_ASSOC);
}

function core_did_del($extension,$cidnum){
	$sql="DELETE FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
	sql($sql);
	
	// now delete from extensions table
	
	if(empty($extension)) {
		$extension = "s";
		$catchaccount = "_X.".(empty($cidnum)?"":"/".$cidnum);
	}
	$account = $extension.(empty($cidnum)?"":"/".$cidnum);
	
	$sql="DELETE FROM extensions WHERE context = \"ext-did\" AND extension = \"$account\"";
	sql($sql);
	
	if ($catchaccount) {
		$sql="DELETE FROM extensions WHERE context = \"ext-did\" AND extension = \"$catchaccount\"";
		sql($sql);
	}
}

function core_did_add($incoming){
	extract($incoming); // create variables from request
	$existing=getIncomingInfo($extension,$cidnum);
	if (empty($existing)) {
		$destination=$core0;
		$sql="INSERT INTO incoming (cidnum,extension,destination,faxexten,faxemail,answer,wait,privacyman) values (\"$cidnum\",\"$extension\",\"$destination\",\"$faxexten\",\"$faxemail\",\"$answer\",\"$wait\",\"$privacyman\")";
		sql($sql);
		
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
	$results = sql($sql,"getCol");
	
	//create a string 
	if (isset($results)){
		$hint = implode($results,"&");
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






?>