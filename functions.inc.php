<?php

/* begin page.ampusers.php functions */

function core_addAmpUser($username, $password, $extension_low, $extension_high, $deptname, $sections) {
	global $db;
	$sql = "INSERT INTO ampusers (username, password, extension_low, extension_high, deptname, sections) VALUES (";
	$sql .= "'".$username."',";
	$sql .= "'".$password."',";
	$sql .= "'".$extension_low."',";
	$sql .= "'".$extension_high."',";
	$sql .= "'".$deptname."',";
	$sql .= "'".implode(";",$sections)."');";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage().'<hr>'.$sql);
	} 
}

function core_deleteAmpUser($username) {
	global $db;
	
	$sql = "DELETE FROM ampusers WHERE username = '".$username."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
	}
}

function core_getAmpUsers() {
	global $db;
	
	$sql = "SELECT username FROM ampusers ORDER BY username";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die($results->getMessage());
	}
	return $results;
}

/* end page.ampusers.php functions */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function core_destinations() {
	//get the list of meetmes
	$results = getextens();
	
	//return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'ext-local,'.$result['0'].',1', 'description' => $result['1'].' <'.$result['0'].'>');
				$extens[] = array('destination' => 'ext-local,${VM_PREFIX}'.$result['0'].',1', 'description' => ''.$result['1'].' voicemail');
		}
	}
	
	return $extens;
}
?>